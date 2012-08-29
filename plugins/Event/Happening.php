<?php
/**
 * Data class for happenings
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
 * Copyright (C) 2011, StatusNet, Inc.
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
 * Data class for happenings
 *
 * There's already an Event class in lib/event.php, so we couldn't
 * call this an Event without causing a hole in space-time.
 *
 * "Happening" seemed good enough.
 *
 * @category Event
 * @package  StatusNet
 * @author   Evan Prodromou <evan@status.net>
 * @license  http://www.fsf.org/licensing/licenses/agpl.html AGPLv3
 * @link     http://status.net/
 *
 * @see      Managed_DataObject
 */
class Happening extends Managed_DataObject
{
    const OBJECT_TYPE = 'event';

    public $__table = 'happening'; // table name
    public $id;                    // varchar(36) UUID
    public $uri;                   // varchar(255)
    public $profile_id;            // int
    public $start_time;            // datetime
    public $end_time;              // datetime
    public $title;                 // varchar(255)
    public $location;              // varchar(255)
    public $url;                   // varchar(255)
    public $description;           // text
    public $created;               // datetime

    /**
     * Get an instance by key
     *
     * @param string $k Key to use to lookup (usually 'id' for this class)
     * @param mixed  $v Value to lookup
     *
     * @return Happening object found, or null for no hits
     *
     */
    function staticGet($k, $v=null)
    {
        return Memcached_DataObject::staticGet('Happening', $k, $v);
    }

    /**
     * The One True Thingy that must be defined and declared.
     */
    public static function schemaDef()
    {
        return array(
            'description' => 'A real-world happening',
            'fields' => array(
                'id' => array('type' => 'char',
                              'length' => 36,
                              'not null' => true,
                              'description' => 'UUID'),
                'uri' => array('type' => 'varchar',
                               'length' => 255,
                               'not null' => true),
                'profile_id' => array('type' => 'int', 'not null' => true),
                'start_time' => array('type' => 'datetime', 'not null' => true),
                'end_time' => array('type' => 'datetime', 'not null' => true),
                'title' => array('type' => 'varchar',
                                 'length' => 255,
                                 'not null' => true),
                'location' => array('type' => 'varchar',
                                    'length' => 255),
                'url' => array('type' => 'varchar',
                               'length' => 255),
                'description' => array('type' => 'text'),
                'created' => array('type' => 'datetime',
                                   'not null' => true),
            ),
            'primary key' => array('id'),
            'unique keys' => array(
                'happening_uri_key' => array('uri'),
            ),
            'foreign keys' => array('happening_profile_id__key' => array('profile', array('profile_id' => 'id'))),
            'indexes' => array('happening_created_idx' => array('created'),
                               'happening_start_end_idx' => array('start_time', 'end_time')),
        );
    }

    static function saveNew($profile, $start_time, $end_time, $title, $location, $description, $url, $options=array())
    {
        if (array_key_exists('uri', $options)) {
            $other = Happening::staticGet('uri', $options['uri']);
            if (!empty($other)) {
                // TRANS: Client exception thrown when trying to create an event that already exists.
                throw new ClientException(_m('Event already exists.'));
            }
        }

        $ev = new Happening();

        $ev->id          = UUID::gen();
        $ev->profile_id  = $profile->id;
        $ev->start_time  = common_sql_date($start_time);
        $ev->end_time    = common_sql_date($end_time);
        $ev->title       = $title;
        $ev->location    = $location;
        $ev->description = $description;
        $ev->url         = $url;

        if (array_key_exists('created', $options)) {
            $ev->created = $options['created'];
        } else {
            $ev->created = common_sql_now();
        }

        if (array_key_exists('uri', $options)) {
            $ev->uri = $options['uri'];
        } else {
            $ev->uri = common_local_url('showevent',
                                        array('id' => $ev->id));
        }

        $ev->insert();

        // XXX: does this get truncated?

        // TRANS: Event description. %1$s is a title, %2$s is start time, %3$s is end time,
        // TRANS: %4$s is location, %5$s is a description.
        $content = sprintf(_m('"%1$s" %2$s - %3$s (%4$s): %5$s'),
                           $title,
                           common_exact_date($ev->start_time),
                           common_exact_date($ev->end_time),
                           $location,
                           $description);

        // TRANS: Rendered event description. %1$s is a title, %2$s is start time, %3$s is start time,
        // TRANS: %4$s is end time, %5$s is end time, %6$s is location, %7$s is description.
        // TRANS: Class names should not be translated.
        $rendered = sprintf(_m('<span class="vevent">'.
                              '<span class="summary">%1$s</span> '.
                              '<abbr class="dtstart" title="%2$s">%3$s</a> - '.
                              '<abbr class="dtend" title="%4$s">%5$s</a> '.
                              '(<span class="location">%6$s</span>): '.
                              '<span class="description">%7$s</span> '.
                              '</span>'),
                            htmlspecialchars($title),
                            htmlspecialchars(common_date_iso8601($ev->start_time)),
                            htmlspecialchars(common_exact_date($ev->start_time)),
                            htmlspecialchars(common_date_iso8601($ev->end_time)),
                            htmlspecialchars(common_exact_date($ev->end_time)),
                            htmlspecialchars($location),
                            htmlspecialchars($description));

        $options = array_merge(array('object_type' => Happening::OBJECT_TYPE),
                               $options);

        if (!array_key_exists('uri', $options)) {
            $options['uri'] = $ev->uri;
        }

        if (!empty($url)) {
            $options['urls'] = array($url);
        }

        $saved = Notice::saveNew($profile->id,
                                 $content,
                                 array_key_exists('source', $options) ?
                                 $options['source'] : 'web',
                                 $options);

        return $saved;
    }

    function getNotice()
    {
        return Notice::staticGet('uri', $this->uri);
    }

    static function fromNotice($notice)
    {
        return Happening::staticGet('uri', $notice->uri);
    }

    function getRSVPs()
    {
        return RSVP::forEvent($this);
    }

    function getRSVP($profile)
    {
        return RSVP::pkeyGet(array('profile_id' => $profile->id,
                                   'event_id' => $this->id));
    }
}
