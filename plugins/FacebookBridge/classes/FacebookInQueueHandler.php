<?php
/*
 * StatusNet - the distributed open-source microblogging tool
 * Copyright (C) 2010, StatusNet, Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

if (!defined('STATUSNET')) { exit(1); }

/**
 * Queue handler to deal with incoming Twitter status updates, as retrieved by
 * TwitterDaemon (twitterdaemon.php).
 *
 * The queue handler passes the status through TwitterImporter for import into the
 * local database (if necessary), then adds the imported notice to the local inbox
 * of the attached Twitter user.
 *
 * Warning: the way we do inbox distribution manually means that realtime, XMPP, etc
 * don't work on Twitter-borne messages. When TwitterImporter is changed to handle
 * that correctly, we'll only need to do this once...?
 */
class FacebookInQueueHandler extends QueueHandler
{
    function transport()
    {
        return 'facebookin';
    }

    function handle($data)
    {
        foreach(array('receiver', 'scope', 'update') as $key) {
			$$key = $data[$key];
		}

        $importer = new FacebookImport($receiver);
        try {
			common_debug('Trying to saveUpdate '.$update['id'].' for '.$receiver);
			$importer->saveUpdate($update, $scope);
		} catch (Exception $e) {
			common_debug('Could not saveUpdate '.$update['id'].' for '.$receiver.': '.$e->getMessage());
			return true;	//discard it if we couldn't import it
		}

        return true;
    }
}
