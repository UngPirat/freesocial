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

 /*
    Parts of this code is licensed under the Apache License:
	https://github.com/scottmac/opengraph

 Copyright 2010 Scott MacVicar

 Licensed under the Apache License, Version 2.0 (the "License");
 you may not use this file except in compliance with the License.
 You may obtain a copy of the License at

 http://www.apache.org/licenses/LICENSE-2.0

 Unless required by applicable law or agreed to in writing, software
 distributed under the License is distributed on an "AS IS" BASIS,
 WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 See the License for the specific language governing permissions and
 limitations under the License.
 */

if (!defined('STATUSNET') && !defined('LACONICA')) { exit(1); }

require_once INSTALLDIR.'/classes/Memcached_DataObject.php';
require_once INSTALLDIR.'/classes/File_redirection.php';

/**
 * Table Definition for file_opengraph
 */

class File_opengraph extends Managed_DataObject
{
    public $__table = 'file_opengraph';      // table name
    public $file_id;                         // int(4)  primary_key not_null
    public $type;                            // varchar(20)
    public $site_name;                       // varchar(50)
    public $image;                           // varchar(255)
    public $title;                           // varchar(255)
    public $description;                     // varchar(255)
    public $url;                             // varchar(255)
    public $modified;                        // timestamp()   not_null default_CURRENT_TIMESTAMP

    /* Static get */
    function staticGet($k,$v=NULL) { return Memcached_DataObject::staticGet('File_opengraph',$k,$v); }

    public static function schemaDef()
    {
        return array(
            'fields' => array(
                'file_id' => array('type' => 'int', 'not null' => true, 'description' => 'file id'),
                'type' => array('type' => 'varchar', 'length' => 20, 'description' => 'open graph object type'),
                'site_name' => array('type' => 'varchar', 'length' => 50, 'description' => 'name of webservice'),
                'image' => array('type' => 'varchar', 'length' => 255, 'description' => 'image for the object'),
                'title' => array('type' => 'varchar', 'length' => 255, 'description' => 'title of open graph object'),
                'description' => array('type' => 'varchar', 'length' => 255, 'description' => 'description of object'),
                'url' => array('type' => 'varchar', 'length' => 255, 'description' => 'url where this was fetched from'),
                'modified' => array('type' => 'timestamp', 'not null' => true, 'description' => 'date this record was modified'),
            ),
            'primary key' => array('file_id'),
            'foreign keys' => array(
                'file_opengraph_file_id_fkey' => array('file', array('file_id' => 'id')),
            ),
        );
    }

  /**
   * Fetches a URI and parses it for Open Graph data, returns
   * false on error.
   *
   * @param $URI    URI to page to parse for Open Graph data
   * @return OpenGraph
   */
    static public function fetch($URI) {
        return self::parse(file_get_contents($URI));
    }

  /**
   * Parses HTML and extracts Open Graph data, this assumes
   * the document is at least well formed.
   *
   * @param $HTML    HTML to parse
   * @return OpenGraph
   */
    static public function parse($HTML) {
        $old_libxml_error = libxml_use_internal_errors(true);

        $doc = new DOMDocument();
        $doc->loadHTML($HTML);

        libxml_use_internal_errors($old_libxml_error);

        $tags = $doc->getElementsByTagName('meta');
        if (!$tags || $tags->length === 0) {
            return false;
        }

        $page = new stdClass();

        foreach ($tags as $tag) {
            if ($tag->hasAttribute('property') &&
                strpos($tag->getAttribute('property'), 'og:') === 0) {
                $key = preg_replace('/(^\w)/', '\1', substr($tag->getAttribute('property'), 3));
                $page->$key = $tag->getAttribute('content');
            }
        }

        if (!count(get_class_vars($page))) { return false; }

        return $page;
    }


    /**
     * Save embedding info for a new file.
     *
     * @param object $data Services_oEmbed_Object_*
     * @param int $file_id
     */
    function saveNew($data, $file_id) {
        $file_og = new File_opengraph;
        $file_og->file_id = $file_id;
        $file_og->type = $data->type;

        $fields = array('site_name', 'image', 'title', 'description', 'url');
        foreach ($fields as $field) :
            if (!empty($data->$field)) {
                $file_og->$field = $data->$field;
            }
        endforeach;

		//TODO: error handle this $file_og->insert();
    }
}
