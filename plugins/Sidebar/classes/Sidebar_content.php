<?php
/*
 * StatusNet - the distributed open-source microblogging tool
 * Copyright (C) 2008-2012, StatusNet, Inc.
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

if (!defined('STATUSNET') && !defined('LACONICA')) { exit(1); }

/**
 * Table Definition for profile
 */
require_once INSTALLDIR.'/classes/Memcached_DataObject.php';

class Sidebar_content extends Managed_DataObject
{
    public $__table = 'sidebar_content';     // table name
    public $id;                              // int(4)  primary_key not_null
    public $name;                            // varchar(255)  multiple_key
    public $created;                         // datetime()   not_null
    public $modified;                        // timestamp()   not_null default_CURRENT_TIMESTAMP

    /* Static get */
    function staticGet($k,$v=NULL) {
        return Memcached_DataObject::staticGet('Sidebar_content',$k,$v);
    }

    public static function schemaDef()
    {
        $def = array(
            'description' => 'local and remote users have profiles',
            'fields' => array(
                'id' => array('type' => 'serial', 'not null' => true, 'description' => 'unique identifier'),
                'sidebar' => array('type' => 'varchar', 'length' => 255, 'not null' => true, 'description' => 'sidebar name', 'collate' => 'utf8_general_ci'),
                'widget' => array('type' => 'varchar', 'length' => 32, 'not null' => true, 'description' => 'widget class', 'collate' => 'utf8_general_ci'),
                'created' => array('type' => 'datetime', 'not null' => true, 'description' => 'date this record was created'),
                'modified' => array('type' => 'timestamp', 'not null' => true, 'description' => 'date this record was modified'),
            ),
            'primary key' => array('id'),
            'indexes' => array(
                'sidebar_content_idx' => array('sidebar'),
            )
        );

        return $def;
    }
}
