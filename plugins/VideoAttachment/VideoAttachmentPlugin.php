<?php
/**
 * StatusNet, the distributed open-source microblogging tool
 *
 * Plugin to enable flexible, fallback HTML5/Flash video player selector
 *
 * PHP version 5
 *
 * LICENCE: This program is free software: you can redistribute it and/or modify
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
 *
 * @category  Plugin
 * @package   StatusNet
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @copyright 2011 Free Software Foundation http://fsf.org
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

class VideoAttachmentPlugin extends Plugin
{
    function __construct()
    {
        parent::__construct();
    }

    /**
     * Automatically load the PHP file for our class
     *
     * @param Class $cls the class
     *
     * @return boolean hook return
     *
     */
    function onAutoload($cls)
    {
        $dir = dirname(__FILE__);

        switch ($cls) {
        case 'VideoAttachment':
            require_once $dir . '/' . strtolower($cls) . '.php';
            return false;
        default:
            return true;
        }
    }

    function onPluginVersion(&$versions)
    {
        $versions[] = array('name' => 'VideoAttachment',
                            'version' => STATUSNET_VERSION,
                            'author' => 'Mikael Nordfeldth',
                            'homepage' => 'http://status.net/wiki/Plugin:VideoAttachment',
                            'rawdescription' =>
                            // TRANS: Plugin description.
                            _m('HTML5 media attachment support'));
        return true;
    }
}
