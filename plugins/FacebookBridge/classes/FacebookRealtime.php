<?php
/**
 * Data class for storing Facebook-realtime queue
 *
 * PHP version 5
 *
 * @category Data
 * @package  StatusNet
 * @author   Zach Copley <zach@status.net>
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
 * Data class for Facebook realtime updates
 *
 * Note that notice_id is unique only within a single database; if you
 * want to share this data for some reason, get the notice's URI and use
 * that instead, since it's universally unique.
 *
 * @category Action
 * @package  StatusNet
 * @author   Zach Copley <zach@status.net>
 * @license  http://www.fsf.org/licensing/licenses/agpl.html AGPLv3
 * @link     http://status.net/
 *
 * @see      DB_DataObject
 */
class FacebookRealtime extends Memcached_DataObject
{
    public $__table = 'facebook_realtime'; // table name
    public $uid;                  // int(4)  primary_key not_null
    public $id;                    // varchar(255) not null
    public $changed_fields;                    // varchar(255) not null
    public $time;                    // datetime

    /**
     * Get an instance by key
     *
     * This is a utility method to get a single instance with a given key value.
     *
     * @param string $k Key to use to lookup
     * @param mixed  $v Value to lookup
     *
     * @return FacebookRealtime object found, or null for no hits
     *
     */
    function staticGet($k, $v=null)
    {
        return Memcached_DataObject::staticGet('FacebookRealtime', $k, $v);
    }

    /**
     * return table definition for DB_DataObject
     *
     * DB_DataObject needs to know something about the table to manipulate
     * instances. This method provides all the DB_DataObject needs to know.
     *
     * @return array array of column definitions
     */
    function table()
    {
        return array(
            'uid' => DB_DATAOBJECT_INT + DB_DATAOBJECT_NOTNULL,
            'id'   => DB_DATAOBJECT_INT + DB_DATAOBJECT_NOTNULL,
            'changed_fields'   => DB_DATAOBJECT_STR + DB_DATAOBJECT_NOTNULL,
            'time'   => DB_DATAOBJECT_INT + DB_DATAOBJECT_NOTNULL
        );
    }

    static function schemaDef()
    {
        return array(
						new ColumnDef('uid', 'bigint', null, false, 'PRI'),
                        new ColumnDef('id', 'bigint', null, false),
                        new ColumnDef('changed_fields', 'varchar', 255, false),
                        new ColumnDef('time', 'int', null, false),
					);
    }

    /**
     * return key definitions for DB_DataObject
     *
     * DB_DataObject needs to know about keys that the table has, since it
     * won't appear in StatusNet's own keys list. In most cases, this will
     * simply reference your keyTypes() function.
     *
     * @return array list of key field names
     */
    function keys()
    {
        return array_keys($this->keyTypes());
    }

    /**
     * return key definitions for Memcached_DataObject
     *
     * Our caching system uses the same key definitions, but uses a different
     * method to get them. This key information is used to store and clear
     * cached data, so be sure to list any key that will be used for static
     * lookups.
     *
     * @return array associative array of key definitions, field name to type:
     *         'K' for primary key: for compound keys, add an entry for each component;
     *         'U' for unique keys: compound keys are not well supported here.
     */
    function keyTypes()
    {
        return array('uid' => 'K');
    }

    /**
     * Magic formula for non-autoincrementing integer primary keys
     *
     * If a table has a single integer column as its primary key, DB_DataObject
     * assumes that the column is auto-incrementing and makes a sequence table
     * to do this incrementation. Since we don't need this for our class, we
     * overload this method and return the magic formula that DB_DataObject needs.
     *
     * @return array magic three-false array that stops auto-incrementing.
     */
    function sequenceKey()
    {
        return array(false, false, false);
    }

	/**
	 * Get realtime notification data
	 */
	static function nextRealtimeUpdate($uid, $since=null) {
		$update = new FacebookRealtime();
		$update->limit(1);
		$update->uid = $uid;
		$update->orderBy('time ASC');

		if (!$update->find()) {
			throw new Exception('FACEBOOK No more RealtimeUpdates');
		}

		$update->fetch();

		return $update;
	}

    /**
     * Save the Facebook realtime notification of changed fields
     *
     * @param integer $notice_id ID of the notice in StatusNet
     * @param integer $item_id ID of the stream item on Facebook
     *
     * @return FacebookRealtime new object for this value
     */
    static function newRealtimeUpdate($entry) {
	    $update = new FacebookRealtime();
		$update->time = date('U', $entry->time);
    	$update->uid   = $entry->uid;
		$update->id = $entry->id;
		$update->changed_fields = implode(',', $entry->changed_fields);

		try {
	        $update->insert();
		} catch (Exception $e) {
			throw new Exception('FACEBOOK realtime update or insert failed! '.$e->getMessage());
		}

        return $update;
    }
}
