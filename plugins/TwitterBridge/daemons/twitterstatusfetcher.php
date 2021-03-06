#!/usr/bin/env php
<?php
/**
 * StatusNet - the distributed open-source microblogging tool
 * Copyright (C) 2008-2010, StatusNet, Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.     See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.     If not, see <http://www.gnu.org/licenses/>.
 */

define('INSTALLDIR', realpath(dirname(__FILE__) . '/../../..'));

// Tune number of processes and how often to poll Twitter
// XXX: Should these things be in config.php?
define('MAXCHILDREN', 1);
define('POLL_INTERVAL', 60); // in seconds

$shortoptions = 'di::';
$longoptions = array('id::', 'debug');

$helptext = <<<END_OF_TRIM_HELP
Batch script for retrieving Twitter messages from foreign service.

  -i --id              Identity (default 'generic')
  -d --debug           Debug (lots of log output)

END_OF_TRIM_HELP;

require_once INSTALLDIR . '/scripts/commandline.inc';
require_once INSTALLDIR . '/lib/common.php';
require_once INSTALLDIR . '/lib/daemon.php';
require_once INSTALLDIR . '/plugins/TwitterBridge/twitter.php';
require_once INSTALLDIR . '/plugins/TwitterBridge/twitteroauthclient.php';

/**
 * Fetch statuses from Twitter
 *
 * Fetches statuses from Twitter and inserts them as notices
 *
 * NOTE: an Avatar path MUST be set in config.php for this
 * script to work, e.g.:
 *     $config['avatar']['path'] = $config['site']['path'] . '/avatar/';
 *
 * @todo @fixme @gar Fix the above. For some reason $_path is always empty when
 * this script is run, so the default avatar path is always set wrong in
 * default.php. Therefore it must be set explicitly in config.php. --Z
 *
 * @category Twitter
 * @package  StatusNet
 * @author   Zach Copley <zach@status.net>
 * @author   Evan Prodromou <evan@status.net>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 */
class TwitterStatusFetcher extends ParallelizingDaemon
{
    /**
     *  Constructor
     *
     * @param string  $id           the name/id of this daemon
     * @param int     $interval     sleep this long before doing everything again
     * @param int     $max_children maximum number of child processes at a time
     * @param boolean $debug        debug output flag
     *
     * @return void
     *
     **/
    function __construct($id = null, $interval = 60,
                         $max_children = 2, $debug = null)
    {
        parent::__construct($id, $interval, $max_children, $debug);
    }

    /**
     * Name of this daemon
     *
     * @return string Name of the daemon.
     */
    function name()
    {
        return ('twitterstatusfetcher.'.$this->_id);
    }

    /**
     * Find all the Twitter foreign links for users who have requested
     * importing of their friends' timelines
     *
     * @return array flinks an array of Foreign_link objects
     */
    function getObjects()
    {
        global $_DB_DATAOBJECT;
        $flink = new Foreign_link();
        $conn = &$flink->getDatabaseConnection();

        $flink->service = TWITTER_SERVICE;
        $flink->orderBy('last_noticesync');
        $flink->find();

        $flinks = array();

        while ($flink->fetch()) {

            if (($flink->noticesync & FOREIGN_NOTICE_RECV) ==
                FOREIGN_NOTICE_RECV) {
                $flinks[] = clone($flink);
                common_log(LOG_INFO, "sync: foreign id $flink->foreign_id");
            } // else nothing to sync
        }

        $flink->free();
        unset($flink);

        $conn->disconnect();
        unset($_DB_DATAOBJECT['CONNECTIONS']);

        return $flinks;
    }

    function childTask($flink) {
        // Each child ps needs its own DB connection

        // Note: DataObject::getDatabaseConnection() creates
        // a new connection if there isn't one already
        $conn = &$flink->getDatabaseConnection();

        $profile = Profile::staticGet('id', $flink->user_id);
		if (is_a($profile, 'Profile_role')) {
			common_debug("profile was a Profile_role for flink: {$flink->user_id}/{$flink->foreign_id}".print_r($profile, true));
		}
        if (empty($profile)) {
            common_debug('TWITTER user does not have a profile: '.$flink->user_id);
        } elseif ($profile->isSilenced()) {
            common_debug('TWITTER ignoring timeline for silenced user '.$profile->id);
        } else {
			try {
				foreach(array('home_timeline', 'mentions_timeline') as $timelineUri) {
		            $this->getTimeline($flink, $timelineUri);
				}
	        } catch (Exception $e) {
	            common_log(LOG_ERR, $this->name() . ' - Unable to get ' . $timelineUri . ' timeline for user ' . $flink->user_id . ' - code: ' . $e->getCode() . 'msg: ' . $e->getMessage());
				switch ($e->getCode()) {
				case 32:
					common_debug('Should we delete flink->user_id='.$flink->user_id.'?');
					break;
				case 417:
					common_debug('Twitter API Expectation failed');
					break;
				}
        	}

            $original = clone($flink);
			$flink->last_noticesync = common_sql_now();
            $flink->update($original);
        }

        $conn->disconnect();
            
        // XXX: Couldn't find a less brutal way to blow
        // away a cached connection
        global $_DB_DATAOBJECT;
        unset($_DB_DATAOBJECT['CONNECTIONS']);
    }

    function getTimeline($flink, $timelineUri = 'home_timeline')
    {
        if (empty($flink)) {
            common_log(LOG_ERR, $this->name() .
                       " - Can't retrieve Foreign_link for foreign ID $fid");
            return;
        }

        common_log(LOG_DEBUG, $this->name() . ' - Trying to get ' . $timelineUri .
                   ' timeline for Twitter user ' . $flink->foreign_id);

        $client = null;

        if (TwitterOAuthClient::isPackedToken($flink->credentials)) {
            $token = TwitterOAuthClient::unpackToken($flink->credentials);
            $client = new TwitterOAuthClient($token->key, $token->secret);
            common_log(LOG_DEBUG, $this->name() . ' - Grabbing ' . $timelineUri . ' timeline with OAuth.');
        } else {
            common_log(LOG_ERR, "Skipping " . $timelineUri . " timeline for " .
                       $flink->foreign_id . " since not OAuth.");
        }

        $timeline = null;

        $lastId = Foreign_sync_status::get_last_id($flink->foreign_id, $timelineUri, TWITTER_SERVICE);

        common_log(LOG_DEBUG, "Got lastId value '" . $lastId . "' for foreign id '" .
                   $flink->foreign_id . "' and timeline " . $timelineUri);

		// throws exception on error
        $timeline = $client->statusesTimeline($lastId, $timelineUri);

        if (empty($timeline)) {
            common_log(LOG_WARNING, $this->name() .  " - Empty '" . $timelineUri . "' timeline.");
            return;
        }

        common_debug($this->name() . ' - Retrieved ' . sizeof($timeline) . ' statuses from Twitter.');

        if (!empty($timeline)) {
            $qm = QueueManager::get();

            // Reverse to preserve order
            foreach (array_reverse($timeline) as $status) {
                $tweeter = Foreign_link::getByForeignID($status->user->id, TWITTER_SERVICE);
                if (!empty($tweeter) && ($tweeter->noticesync & FOREIGN_NOTICE_RECV) == FOREIGN_NOTICE_RECV) {
                    $tweeter = Profile::staticGet('id', $tweeter->user_id);
                } else {
                    $tweeter = TwitterImport::ensureProfile($status->user);
                }
        
                if (empty($tweeter) || $tweeter->isSilenced()) {
					continue;
                }

                $data = array(
                    'status' => $status,
                    'for_user' => $flink->foreign_id,
                );
                $qm->enqueue($data, 'tweetin');
            }

            $lastId = twitter_id($timeline[0]);
            Foreign_sync_status::set_last_id($flink->foreign_id, $timelineUri, TWITTER_SERVICE, $lastId);
            common_debug("Set lastId value '$lastId' for foreign id '{$flink->foreign_id}' and timeline '" . $timelineUri ."'");
        }

        // Okay, record the time we synced with Twitter for posterity
        $flink->last_noticesync = common_sql_now();
        $flink->update();
    }
}

$id    = null;
$debug = null;

if (have_option('i')) {
    $id = get_option_value('i');
} else if (have_option('--id')) {
    $id = get_option_value('--id');
} else if (count($args) > 0) {
    $id = $args[0];
} else {
    $id = null;
}

if (have_option('d') || have_option('debug')) {
    $debug = true;
}

$fetcher = new TwitterStatusFetcher($id, POLL_INTERVAL, MAXCHILDREN, $debug);
$fetcher->runOnce();
