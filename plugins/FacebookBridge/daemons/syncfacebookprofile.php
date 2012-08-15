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
Batch script for synching local profile with Facebook service.
  -i --id              Identity (default 'generic')
  -d --debug           Debug (lots of log output)

END_OF_TRIM_HELP;

require_once INSTALLDIR . '/scripts/commandline.inc';
require_once INSTALLDIR . '/lib/parallelizingdaemon.php';
require_once INSTALLDIR . '/plugins/FacebookBridge/lib/facebookclient.php';

/**
 * Daemon to sync local profile with Facebook profile
 *
 * @category Facebook
 * @package  StatusNet
 * @author   Zach Copley <zach@status.net>
 * @author   Evan Prodromou <evan@status.net>
 * @author   Mikael Nordfeldth <mmn@hethane.se>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 */
class SyncFacebookProfileDaemon extends ParallelizingDaemon
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
        return ('syncfacebookprofile.' . $this->_id);
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
        $flink->orderBy('last_friendsync');	// TODO: must add last_profilesync!
        $flink->limit(25);  // sync this many users during this run
        $flink->find();

        while ($flink->fetch()) {
            if (($flink->profilesync & FOREIGN_PROFILE_SYNC) == FOREIGN_PROFILE_SYNC
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

        $this->syncForeignProfile($flink);

        $original = clone($flink);
        $flink->last_friendsync = common_sql_now();	//TODO: Must get a last_profilesync there!
        $flink->update($original);

        $conn->disconnect();

        // XXX: Couldn't find a less brutal way to blow
        // away a cached connection
        global $_DB_DATAOBJECT;
        unset($_DB_DATAOBJECT['CONNECTIONS']);
    }

    function syncForeignProfile($flink)
    {
        $fsrv = new FacebookService();
		try {
            $fbuser = $fsrv->fetchUserData('me', $flink->credentials, array('name','picture','website','location'));
        } catch (Exception $e) {
            common_debug('Error connecting to Facebook Graph API: '.$e->getMessage());
            return false;
        }
        $profile = $flink->getUser()->getProfile();

        $original = clone($profile);
        $profile->fullname = isset($fbuser['name']) ? $fbuser['name'] : $profile->fullname;
        $profile->location = isset($fbuser['location']['name']) ? $fbuser['location']['name'] : $profile->location;
        $profile->homepage = isset($fbuser['website']) ? $fbuser['website'] : $profile->homepage;
        $profile->update($original);
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

$syncer = new SyncFacebookProfileDaemon($id, 600, 1, $debug);
$syncer->runOnce();
