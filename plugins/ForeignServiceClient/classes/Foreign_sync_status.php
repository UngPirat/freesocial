<?php
/**
 * Store last-touched ID for various timelines
 *
 * PHP version 5
 *
 * @category Data
 * @package  StatusNet
 * @author   Evan Prodromou <evan@status.net>
 * @license  http://www.fsf.org/licensing/licenses/agpl.html AGPLv3
 * @link     http://status.net/
 *
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.     See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

if (!defined('STATUSNET')) {
    exit(1);
}

require_once INSTALLDIR . '/classes/Memcached_DataObject.php';

/**
 * Store various timeline data
 *
 * We don't want to keep re-fetching the same statuses and direct messages from Twitter.
 * So, we store the last ID we see from a timeline, and store it. Next time
 * around, we use that ID in the since_id parameter.
 *
 * @category Action
 * @package  StatusNet
 * @author   Evan Prodromou <evan@status.net>
 * @author   Mikael Nordfeldth <mmn@hethane.se>
 * @license  http://www.fsf.org/licensing/licenses/agpl.html AGPLv3
 * @link     http://status.net/
 *
 * @see      DB_DataObject
 */
class Foreign_sync_status extends Managed_DataObject
{
    public $__table = 'foreign_sync_status'; // table name
    public $foreign_id;                      // int(4)  primary_key not_null
    public $timeline;                        // varchar(255)  primary_key not_null
    public $last_id;                         // bigint not_null
    public $created;                         // datetime not_null
    public $modified;                        // datetime not_null

    /**
     * Get an instance by key
     *
     * @param string $k Key to use to lookup (usually 'foreign_id' for this class)
     * @param mixed  $v Value to lookup
     *
     * @return nothing, exception is always thrown!
     */
    function staticGet($k, $v=null)
    {
        throw new Exception("Use pkeyGet() for this class.");
    }

    /**
     * Get an instance by compound primary key
     *
     * @param array $kv key-value pair array
     *
     * @return Twitter_synch_status object found, or null for no hits
     *
     */
    function pkeyGet($kv)
    {
        return Memcached_DataObject::pkeyGet('Foreign_sync_status', $kv);
    }

	public static function schemaDef()
	{
		return array(
			'fields' => array(
				'foreign_id' => array('type' => 'varchar',
									'length' => 255,
									'not null' => true),
				'service_id' => array('type' => 'integer', 'not null' => true),
				'timeline'   => array('type' => 'varchar',
									'length' => 255,
									'not null' => true),
				'last_id'    => array('type' => 'varchar',
									'length' => 255,
									'not null' => true),
				'created'    => array('type' => 'datetime', 'not null' => true),
				'modified'   => array ('type' => 'timestamp', 'not null' => true),
			),
			'primary_key' => array('foreign_id', 'timeline', 'service_id'),
			'indexes' => array(
				'foreign_sync_status_last_id_idx' => array('last_id'),
			),
		);
	}

    static function get_last_id($foreign_id, $timeline, $service_id)
    {
        $tss = self::pkeyGet(array('foreign_id' => $foreign_id,
                                   'timeline'   => $timeline,
								   'service_id' => $service_id));

        if (empty($tss)) {
            return null;
        } else {
            return $tss->last_id;
        }
    }

    static function set_last_id($foreign_id, $timeline, $service_id, $last_id)
    {
        $tss = self::pkeyGet(array('foreign_id' => $foreign_id,
                                   'timeline'   => $timeline,
								   'service_id' => $service_id));

        if (empty($tss)) {
            $tss = new Foreign_sync_status();

            $tss->foreign_id = $foreign_id;
            $tss->timeline   = $timeline;
            $tss->service_id = $service_id;
            $tss->last_id    = $last_id;
            $tss->created    = common_sql_now();
            $tss->modified   = $tss->created;

            $tss->insert();

            return true;
        } else {
            $orig = clone($tss);

            $tss->last_id  = $last_id;
            $tss->modified = common_sql_now();

            $tss->update();

            return true;
        }
    }
}
