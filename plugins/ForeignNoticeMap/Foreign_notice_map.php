<?php
/**
 * Data class for remembering foreign notice mappings
 *
 * PHP version 5
 *
 * @category Data
 * @package  StatusNet
 * @author   Evan Prodromou <evan@status.net>
 * @author   Mikael Nordfeldth <mmn@hethane.se>
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

require_once INSTALLDIR . '/classes/Managed_DataObject.php';

/**
 * Data class for mapping local notices to foreign ids
 *
 * Notices flow back and forth between StatusNet and other services. We
 * use this table to remember which StatusNet notice corresponds to which
 * foreign status.
 *
 * Note that notice_id is unique only within your own database; if you
 * want to share this data for some reason, get the notice's URI and use
 * that instead, since it's universally unique.
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

class Foreign_notice_map extends Managed_DataObject
{
	const FOREIGN_NOTICE_RECV_IMPORT = 8;

    public $__table = 'foreign_notice_map'; // table name
    public $notice_id;                      // int(4)  not_null
    public $foreign_id;                      // varchar  primary_key not_null
    public $service_id;                      // int(4)  not_null
    public $created;                        // datetime

    function staticGet($k,$v=null) {
        return DB_DataObject::staticGet('Foreign_notice_map',$k,$v);
    }

    function is_foreign_notice($notice_id, $service_id) {
        try {
			Foreign_notice_map::get_foreign_id($notice_id, $service_id);
		} catch (Exception $e) {
			return false;
		}
        return true;
    }
    function get_notice_id($foreign_id, $service_id) {
        $fnmap = new Foreign_notice_map();
        $fnmap->foreign_id = $foreign_id;
        $fnmap->service_id = $service_id;
        if (!$fnmap->find()) {
            throw new Exception('No notice found for service #'.$service_id.' and foreign_id='.$foreign_id);
        }
        $fnmap->fetch();
        return $fnmap->notice_id;
    }
    function get_foreign_id($notice_id, $service_id) {
        $fnmap = new Foreign_notice_map();
        $fnmap->notice_id = $notice_id;
        $fnmap->service_id = $service_id;
        if (!$fnmap->find()) {
            throw new Exception('No foreign notice found for service #'.$service_id.' and notice_id='.$notice_id);
        }
        $fnmap->fetch();
        return $fnmap->foreign_id;
    }
    function get_foreign_notice($foreign_id, $service_id) {
        $notice_id = Foreign_notice_map::get_notice_id($foreign_id, $service_id);	// throws exception on failure

        $result = Notice::staticGet('id', $notice_id);
        return $result;
    }
    function delete_notice_mapping($notice_id, $service_id) {
        $fnmap = new Foreign_notice_map();
        $fnmap->notice_id = $notice_id;
        $fnmap->service_id = $service_id;
        $fnmap->find();
        if (!$fnmap->find()) {
            throw new Exception('No foreign id found for service #'.$service_id.' and notice_id='.$foreign_id);
        }
        $fnmap->fetch();
        return $fnmap->delete();
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
                    'foreign_id' => DB_DATAOBJECT_STR + DB_DATAOBJECT_NOTNULL,
                    'notice_id'   => DB_DATAOBJECT_INT + DB_DATAOBJECT_NOTNULL,
                    'service_id' => DB_DATAOBJECT_INT + DB_DATAOBJECT_NOTNULL,
                    'created'    => DB_DATAOBJECT_STR + DB_DATAOBJECT_DATE + DB_DATAOBJECT_TIME + DB_DATAOBJECT_NOTNULL);
    }

    static function schemaDef()
    {  
        return array(
            new ColumnDef('foreign_id', 'varchar', 255, null, 'PRI'),
            new ColumnDef('notice_id', 'integer', null, false),
            new ColumnDef('service_id', 'integer', null, false),
            new ColumnDef('created', 'datetime',  null, false)
        );
    }

    /**
     * return key definitions for Managed_DataObject
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
        return array('foreign_id' => 'K');
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
     * Save a mapping between a local notice and the foreign id
     * Warning: foreign_id values may not fit in 32-bit integers.
     *
     * @param integer $notice_id ID of the notice in StatusNet
     * @param integer $foreign_id ID of the status in your foreign service
     * @param integer $service_id ID of the foreign service in foreign_service
     *
     * @return ForeignNotice new object for this value
     */
    static function saveNew($notice_id, $foreign_id, $service_id)
    {
        if (empty($notice_id) || empty($foreign_id) || empty($service_id)) {
            throw new Exception("saveNew invalid parameters ($notice_id, $foreign_id, $service_id)");
        }
        $service = Foreign_service::staticGet('id', $service_id);
        if (empty($service)) {
            throw new Exception("Unknown service_id $service_id");
        }

        if (Foreign_notice_map::is_foreign_notice($notice_id, $service_id)) {
            throw new Exception(_('Foreign notice already mapped'));
        }

        $fnmap = new Foreign_notice_map();
        $fnmap->notice_id = $notice_id;
        $fnmap->service_id = $service_id;
        $fnmap->foreign_id = $foreign_id;
        $fnmap->created = common_sql_now();
        $fnmap->insert();
    
        return $fnmap;
    }
}
