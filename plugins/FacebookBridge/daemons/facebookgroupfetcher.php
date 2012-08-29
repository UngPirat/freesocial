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

define('MAXCHILDREN', 1);
define('POLL_INTERVAL', 60); // in seconds

$shortoptions = 'di::';
$longoptions = array('id::', 'debug');

$helptext = <<<END_OF_TRIM_HELP
Batch script for retrieving Facebook messages from foreign service.

  -i --id              Identity (default 'generic')
  -d --debug           Debug (lots of log output)

END_OF_TRIM_HELP;

require_once INSTALLDIR . '/scripts/commandline.inc';
require_once INSTALLDIR . '/lib/common.php';
require_once INSTALLDIR . '/lib/daemon.php';
require_once INSTALLDIR . '/plugins/FacebookBridge/lib/facebookclient.php';

/**
 * Fetch group statuses from Facebook
 *
 * @category Facebook
 * @package  StatusNet
 * @author   Zach Copley <zach@status.net>
 * @author   Evan Prodromou <evan@status.net>
 * @author   Mikael Nordfeldth <mmn@hethane.se>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 */
class FacebookGroupFetcher extends ParallelizingDaemon
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
        return ('facebookgroupfetcher.'.$this->_id);
    }

    /**
     * Find all the Facebook foreign links for users who have requested
     * importing of their friends' timelines
     *
     * @return array flinks an array of Foreign_link objects
     */
    function getObjects()
    {
        global $_DB_DATAOBJECT;
        $foreign = new Foreign_group();
        $conn = &$foreign->getDatabaseConnection();

        $foreign->service = FACEBOOK_SERVICE;
        $foreign->orderBy('last_noticesync');
        $foreign->find();

        $groups = array();

        while ($foreign->fetch()) {
            if (Local_group::staticGet('group_id', $foreign->group_id)) {
                $groups[] = clone($foreign);
            }
        }

        $foreign->free();
        unset($foreign);

        $conn->disconnect();
        unset($_DB_DATAOBJECT['CONNECTIONS']);

        return $groups;
    }

    function childTask($group) {
        // Each child ps needs its own DB connection

        // Note: DataObject::getDatabaseConnection() creates
        // a new connection if there isn't one already
        $conn = &$group->getDatabaseConnection();

        if (time()-strtotime($group->last_noticesync) > 120) {
		    $importer = new FacebookImport();
            try {
                $importer->importGroup($group);
                $original = clone($group);
                $group->last_noticesync = common_sql_now();
                $group->update($original);
            } catch (Exception $e) {
            }
		}


        $conn->disconnect();

        // XXX: Couldn't find a less brutal way to blow
        // away a cached connection
        global $_DB_DATAOBJECT;
        unset($_DB_DATAOBJECT['CONNECTIONS']);
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

$fetcher = new FacebookGroupFetcher($id, POLL_INTERVAL, MAXCHILDREN, $debug);
$fetcher->runOnce();
