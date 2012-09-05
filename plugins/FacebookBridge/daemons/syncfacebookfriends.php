#!/usr/bin/env php
<?php
/*
 * StatusNet - the distributed open-source microblogging tool
 * Copyright (C) 2008, 2009, StatusNet, Inc.
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

$shortoptions = 'di::';
$longoptions = array('id::', 'debug');

$helptext = <<<END_OF_TRIM_HELP
Batch script for synching local friends with Facebook friends.
  -i --id              Identity (default 'generic')
  -d --debug           Debug (lots of log output)

END_OF_TRIM_HELP;

require_once INSTALLDIR . '/scripts/commandline.inc';
require_once INSTALLDIR . '/lib/parallelizingdaemon.php';
require_once INSTALLDIR . '/plugins/FacebookBridge/lib/facebookclient.php';

/**
 * Daemon to sync local friends with Facebook friends
 *
 * @category Facebook
 * @package  StatusNet
 * @author   Zach Copley <zach@status.net>
 * @author   Evan Prodromou <evan@status.net>
 * @author   Mikael Nordfeldth <mmn@hethane.se>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 */
class SyncFacebookFriendsDaemon extends ParallelizingDaemon
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
        return ('syncfacebookfriends.' . $this->_id);
    }

    /**
     * Find all the Facebook foreign links for users who have requested
     * automatically subscribing to their Facebook friends locally.
     *
     * @return array flinks an array of Foreign_link objects
     */
    function getObjects()
    {
        $flinks = array();
        $flink = new Foreign_link();

        $conn = &$flink->getDatabaseConnection();

        $flink->service = FACEBOOK_SERVICE;
        $flink->orderBy('last_friendsync');
        $flink->limit(25);  // sync this many users during this run
        $flink->find();

        while ($flink->fetch()) {
            if (($flink->friendsync & FOREIGN_FRIEND_RECV) == FOREIGN_FRIEND_RECV
                    && !empty($flink->credentials)) {
                $flinks[] = clone($flink);
            }
        }

        $conn->disconnect();

        global $_DB_DATAOBJECT;
        unset($_DB_DATAOBJECT['CONNECTIONS']);

        return $flinks;
    }

    function childTask($flink) {
        // Each child ps needs its own DB connection

        // Note: DataObject::getDatabaseConnection() creates
        // a new connection if there isn't one already
        $conn = &$flink->getDatabaseConnection();

        try {
            $this->subscribeFacebookFriends($flink);
            $original = clone($flink);
            $flink->last_friendsync = common_sql_now();
            $flink->update($original);
        } catch (Exception $e) {
             common_debug('Facebook error when fetching friends: '.$e->getMessage());
        }

        $conn->disconnect();

        // XXX: Couldn't find a less brutal way to blow
        // away a cached connection
        global $_DB_DATAOBJECT;
        unset($_DB_DATAOBJECT['CONNECTIONS']);
    }

    function fetchFacebookFriends($flink)
    {
        $friends = array();
        $client = Facebookclient::getFacebook();
		$args = array();
        $result = array();
        
		do {
            if (!isset($args['access_token'])) {
                $args['access_token'] = $flink->credentials;
            }
	        try {
				$result = $client->api('/me/friends', 'get', $args);
				$friends = array_merge($friends, $result['data']);
	        } catch (FacebookApiException $e) {
                $r = $e->getResult();
                if ($r['error']['code']==190) {
                    switch($r['error']['error_subcode']) {
                    case 458:	//User %i has not authorized application %i.
                    case 460:	//The session has been invalidated because the user has changed the password.
                    case 463:	//Session has expired at unix time %i. The current unix time is %i.
                        Facebookclient::emailExpiredCredentials($flink->getUser(), $e->getMessage());
                        $original = clone($flink);
                        $flink->credentials = null;
                        $flink->update($original);
                        common_debug('Nulled expired credentials for '.$flink->foreign_id.' due to api error: ['.$r['error']['code'].'/'.$r['error']['error_subcode'].'] '.$e->getMessage());
                        throw new Exception($e->getMessage());
                        break;
                    default:
                        common_debug('Unhandled error: ['.$r['error']['code'].'/'.$r['error']['error_subcode'].'] '.$e->getMessage());
                    }
                } else {
                    common_log(LOG_WARNING, $this->name() . ' - error getting FACEBOOK friends ['.$r['error']['code'].'/'.$r['error']['error_subcode'].'] ' . $e->getMessage());
                }
    	        return $friends;
        	}
            if (isset($result['paging']['next'])) {
                $next = parse_url($result['paging']['next'], PHP_URL_QUERY);
                parse_str($next, $args);    // overwrite with data that makes us traverse the list
            }
		} while (isset($result['paging']['next']));

        file_put_contents('/tmp/facebook_friends-'.$flink->foreign_id, print_r($friends,true));
        return $friends;
    }

    function subscribeFacebookFriends($flink)
    {
        $friends = $this->fetchFacebookFriends($flink);

        if (empty($friends)) {
            common_debug($this->name() .
                         ' - Couldn\'t get friends from Facebook for ' .
                         "Facebook user $flink->foreign_id.");
            return false;
        }

        $user = $flink->getUser();

        $fsrv = new FacebookService();

        foreach ($friends as $friend) {
            if (empty($friend['id']) || empty($friend['name'])) {
                $tempnam = tempnam('/tmp', 'ffdebug-');
                common_debug('WARNING: empty friend entry, dumping list to '.$tempnam);
				file_put_contents($tempnam, print_r($friends, true));
                continue;
            }

            // Check to see if there's a related local user
            $friend_flink = Foreign_link::getByForeignID($friend['id'], FACEBOOK_SERVICE);
            if (empty($friend_flink)) {
                continue;
            }

            try {
                // Updates or creates the Foreign_user record for the relevant entry
	    		$fsrv->addForeignUser($friend['id'], $flink->credentials);
		    } catch (Exception $e) {
                common_log(LOG_WARNING, $this->name() . " - Couldn't save {$user->nickname}'s FACEBOOK friend, {$friend['name']}: ".$e->getMessage());
                continue;
            }

            // Get associated user and subscribe her
            $friend_user = $friend_flink->getUser();
            if (empty($friend_user)) {
                continue;
            }

            $result = subs_subscribe_to($user, $friend_user);
            if ($result === true) {
                common_log(LOG_INFO,
                           $this->name() . ' - Subscribed ' .
                           "$friend_user->nickname to $user->nickname.");
            } else {
                common_debug($this->name() .
                             ' - Tried subscribing ' .
                             "$friend_user->nickname to $user->nickname - " .
                             $result);
            }
        }

        return true;
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

$syncer = new SyncFacebookFriendsDaemon($id, 600, 1, $debug);
$syncer->runOnce();
