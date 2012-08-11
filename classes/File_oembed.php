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

if (!defined('STATUSNET') && !defined('LACONICA')) { exit(1); }

require_once INSTALLDIR.'/classes/Memcached_DataObject.php';
require_once INSTALLDIR.'/classes/File_redirection.php';

/**
 * Table Definition for file_oembed
 */

class File_oembed extends Managed_DataObject
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'file_oembed';                     // table name
    public $file_id;                         // int(4)  primary_key not_null
    public $version;                         // varchar(20)
    public $type;                            // varchar(20)
    public $mimetype;                        // varchar(50)
    public $provider;                        // varchar(50)
    public $provider_url;                    // varchar(255)
    public $width;                           // int(4)
    public $height;                          // int(4)
    public $html;                            // text()
    public $title;                           // varchar(255)
    public $author_name;                     // varchar(50)
    public $author_url;                      // varchar(255)
    public $url;                             // varchar(255)
    public $modified;                        // timestamp()   not_null default_CURRENT_TIMESTAMP

    /* Static get */
    function staticGet($k,$v=NULL) { return Memcached_DataObject::staticGet('File_oembed',$k,$v); }

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE

    public static function schemaDef()
    {
        return array(
            'fields' => array(
                'file_id' => array('type' => 'int', 'not null' => true, 'description' => 'oEmbed for that URL/file'),
                'version' => array('type' => 'varchar', 'length' => 20, 'description' => 'oEmbed spec. version'),
                'type' => array('type' => 'varchar', 'length' => 20, 'description' => 'oEmbed type: photo, video, link, rich'),
                'mimetype' => array('type' => 'varchar', 'length' => 50, 'description' => 'mime type of resource'),
                'provider' => array('type' => 'varchar', 'length' => 50, 'description' => 'name of this oEmbed provider'),
                'provider_url' => array('type' => 'varchar', 'length' => 255, 'description' => 'URL of this oEmbed provider'),
                'width' => array('type' => 'int', 'description' => 'width of oEmbed resource when available'),
                'height' => array('type' => 'int', 'description' => 'height of oEmbed resource when available'),
                'html' => array('type' => 'text', 'description' => 'html representation of this oEmbed resource when applicable'),
                'title' => array('type' => 'varchar', 'length' => 255, 'description' => 'title of oEmbed resource when available'),
                'author_name' => array('type' => 'varchar', 'length' => 50, 'description' => 'author name for this oEmbed resource'),
                'author_url' => array('type' => 'varchar', 'length' => 255, 'description' => 'author URL for this oEmbed resource'),
                'url' => array('type' => 'varchar', 'length' => 255, 'description' => 'URL for this oEmbed resource when applicable (photo, link)'),
                'modified' => array('type' => 'timestamp', 'not null' => true, 'description' => 'date this record was modified'),
            ),
            'primary key' => array('file_id'),
            'foreign keys' => array(
                'file_oembed_file_id_fkey' => array('file', array('file_id' => 'id')),
            ),
        );
    }

    function _getOembed($url) {
        $parameters = array(
            'maxwidth' => common_config('attachments', 'thumb_width'),
            'maxheight' => common_config('attachments', 'thumb_height'),
        );
        try {
            return oEmbedHelper::getObject($url, $parameters);
        } catch (Exception $e) {
            common_log(LOG_ERR, "Error during oembed lookup for $url - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Save embedding info for a new file.
     *
     * @param object $data Services_oEmbed_Object_*
     * @param int $file_id
     */
    function saveNew($data, $file_id) {
        $file_oembed = new File_oembed;
        $file_oembed->file_id = $file_id;
        $file_oembed->version = $data->version;
        $file_oembed->type = $data->type;

        if (!empty($data->provider_name)) {
            $file_oembed->provider = $data->provider_name;	// in case $data->provider is empty
        }
        $fields = array('provider', 'provider_url', 'width', 'height', 'html', 'title', 'author_name', 'author_url');
        foreach ($fields as $field) :
            if (!empty($data->$field)) {
                $file_oembed->$field = $data->$field;
            }
        endforeach;

        if (!empty($data->url)){
            $file_oembed->url = $data->url;
            $given_url = File_redirection::_canonUrl($file_oembed->url);
            if (! empty($given_url)){
                $file = File::staticGet('url', $given_url);
                if (empty($file)) {
                    $file_redir = File_redirection::staticGet('url', $given_url);
                    if (empty($file_redir)) {
                        $redir_data = File_redirection::where($given_url);
                        $file_oembed->mimetype = $redir_data['type'];
                    } else {
                        $file_id = $file_redir->file_id;
                    }
                } else {
                    $file_oembed->mimetype=$file->mimetype;
                }
            }
        }
        $file_oembed->insert();
        if (!empty($data->thumbnail_url) || ($data->type == 'photo')) {
            $ft = File_thumbnail::staticGet('file_id', $file_id);
            if (!empty($ft)) {
                common_log(LOG_WARNING, "Strangely, a File_thumbnail object exists for new file $file_id",
                           __FILE__);
            } else {
                File_thumbnail::saveNew($data, $file_id);
            }
        }
    }
}
