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
 * Queue handler to deal with incoming foreign service updates
 */
abstract class ForeignQueueHandler extends QueueHandler
{
	protected $service_id = null;

    abstract function transport();
    abstract static function handleItem($item);

    function handle($data)
    {
		if (is_null($this->service_id)) {
			common_log(LOG_ERR, 'Bad ForeignQueueHandler configuration: service_id===null');
			return;
		}
		foreach (array('item', 'foreign_id') as $field) {
			if (!isset($data[$field]) || empty($data[$field])) {
				common_log('LOG_ERR', 'Empty field '.$field.' for queue item transport: '.$this->transport().'. Discarding entry.');
				return true;
			}
			$$field = $data[$field];
		}
		
		$notice = $this->handleItem($item);

        if (!empty($notice)) {
            $flink = Foreign_link::getByForeignID($foreign_id, $this->service_id);
            if ($flink) {
				common_log(LOG_DEBUG, __CLASS__ . " - Got flink so add notice ".
				           $notice->id." to inbox ".$flink->user_id);
                Inbox::insertNotice($flink->user_id, $notice->id);
// maybe?				Subscription::start($flink->user_id, $notice->profile_id);
            } else {
				common_log(LOG_DEBUG, __CLASS__ . " - No flink found for foreign user ".$receiver);
			}
		}

        return true;
    }
}
