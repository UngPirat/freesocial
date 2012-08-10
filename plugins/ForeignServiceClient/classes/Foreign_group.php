<?php
/**
 * Data class for remembering foreign group mappings
 *
 * PHP version 5
 *
 * @category Data
 * @package  StatusNet
 * @author   Mikael Nordfeldth <mmn@hethane.se>
 * @license  http://www.fsf.org/licensing/licenses/agpl.html AGPLv3
 * @link     http://status.net/
 *
 * StatusNet - the distributed open-source microblogging tool
 * Copyright (C) 2010-2012, StatusNet, Inc.
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

/**
 * Data class for mapping local groups to foreign ids
 *
 * This is for synchronising groups between StatusNet and foreign services
 * such as the gated community Facebook.
 *
 * @category Plugin
 * @package  StatusNet
 * @author   Mikael Nordfeldth <mmn@hethane.se>
 * @license  http://www.fsf.org/licensing/licenses/agpl.html AGPLv3
 * @link     http://status.net/
 *
 * @see      DB_DataObject
 */

class Foreign_group extends Managed_DataObject
{
    public $__table = 'foreign_group'; // table name
    public $group_id;                      // int(4)  not_null
    public $foreign_id;                     // varchar  primary_key not_null
    public $service_id;                     // int(4)  not_null
    public $created;                        // datetime

    static function schemaDef()
    {  
        return array(
            'fields' => array(
                'foreign_id' => array('type' => 'integer',
                                      'not null' => true),
                'group_id'  => array('type' => 'integer',
                                      'not null' => true),
                'service_id' => array('type' => 'integer',
                                      'not null' => true),
                'created'    => array('type' => 'datetime',
                                      'not null' => true),
            ),
            'primary key' => array('foreign_id', 'service_id'),
            'unique keys' => array(
                'foreign_group_group_id_idx' => array('group_id', 'service_id'),
            ),
            'foreign keys' => array(
                'foreign_group_user_group_id_fkey' => array('local_group', array('group_id' => 'group_id')),
                'foreign_group_service_id_fkey' => array('foreign_service', array('service_id' => 'id')),
            ),
        );
    }

    function staticGet($k,$v=null) {
        return parent::staticGet(__CLASS__,$k,$v);	// does __CLASS__ work here?
    }

    static function is_foreign_group($group_id, $service_id) {
        try {
            Foreign_group::get_foreign_id($group_id, $service_id);
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    static function get_group_id($foreign_id, $service_id) {
        $foreign = new Foreign_group();
        $foreign->foreign_id = $foreign_id;
        $foreign->service_id = $service_id;
        if (!$foreign->find()) {
            throw new Exception('No local group found');
        }
        $foreign->fetch();
        return $foreign->group_id;
    }

    static function get_foreign_id($group_id, $service_id) {
        $foreign = new Foreign_group();
        $foreign->group_id = $group_id;
        $foreign->service_id = $service_id;
        if (!$foreign->find()) {
            throw new Exception('No foreign group found');
        }
        $foreign->fetch();
        return $foreign->foreign_id;
    }
    static function get_foreign_group($foreign_id, $service_id) {
        $group_id = Foreign_group::get_group_id($foreign_id, $service_id);    // throws exception on failure
        $result = User_group::staticGet('id', $group_id);
        return $result;
    }
    static function delete_group_mapping($group_id, $service_id) {
        $foreign = new Foreign_group();
        $foreign->group_id = $group_id;
        $foreign->service_id = $service_id;
        if (!$foreign->find()) {
            throw new Exception(_('No foreign group mapping to delete'));
        }
        $foreign->fetch();
        return $foreign->delete();
    }

    /**
     * Save a mapping between a local group and the foreign group id
     * Warning: foreign_id values may not fit in 32-bit integers.
     *
     * @param integer $group_id ID of the User_group in StatusNet
     * @param integer $foreign_id ID of the group in your foreign service
     * @param integer $service_id ID of the foreign service in foreign_service
     *
     * @return Foreign_group new object for this value
     */
    static function saveNew($group_id, $foreign_id, $service_id)
    {
        if (!Foreign_service::staticGet('id', $service_id)) {
            throw new Exception('Unknown service_id');
        }

        if (!Local_group::staticGet('group_id', $group_id)) {
            throw new Exception('Local group does not exist');
        }

        if (Foreign_group::is_foreign_group($group_id, $service_id)) {
            throw new Exception('Foreign group already mapped');
        }

        $foreign = new Foreign_group();
        $foreign->group_id = $group_id;
        $foreign->service_id = $service_id;
        $foreign->foreign_id = $foreign_id;
        $foreign->created = common_sql_now();
        $foreign->insert();
    
        return $foreign;
    }
}
