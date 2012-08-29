<?php
/**
 * StatusNet - the distributed open-source microblogging tool
 * Copyright (C) 2009, StatusNet, Inc.
 *
 * Webcam avatar photo booth
 *
 * PHP version 5
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
 *
 * @author    Mikael Nordfeldth <mmn@status.net>
 * @copyright 2011 Mikael Nordfeldth
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html AGPL 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET')) {
    // This check helps protect against security problems;
    // your code file can't be executed directly from the web.
    exit(1);
}

/**
 * @category  Sample
 * @package   StatusNet
 * @author    Brion Vibber <brionv@status.net>
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2009 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html AGPL 3.0
 * @link      http://blog.mmn-o.se/dev/sn-plugins/avatarbooth/
 */
class AvatarBoothPlugin extends Plugin
{
    public $attr1 = null;
    public $attr2 = null;

    function initialize()
    {
        return true;
    }

    /**
     * Cleanup for this plugin
     *
     * Plugins overload this method to do any cleanup they need,
     * like disconnecting from remote servers or deleting temp files or so on.
     *
     * @return boolean hook value; true means continue processing, false means stop.
     */
    function cleanup()
    {
        return true;
    }

    /**
     * Load related modules when needed
     *
     * Most non-trivial plugins will require extra modules to do their work. Typically
     * these include data classes, action classes, widget classes, or external libraries.
     *
     * This method receives a class name and loads the PHP file related to that class. By
     * tradition, action classes typically have files named for the action, all lower-case.
     * Data classes are in files with the data class name, initial letter capitalized.
     *
     * Note that this method will be called for *all* overloaded classes, not just ones
     * in this plugin! So, make sure to return true by default to let other plugins, and
     * the core code, get a chance.
     *
     * @param string $cls Name of the class to be loaded
     *
     * @return boolean hook value; true means continue processing, false means stop.
     */
    function onAutoload($cls)
    {
        $dir = dirname(__FILE__);

        switch ($cls)
        {
        case 'HelloAction':
            include_once $dir . '/' . strtolower(mb_substr($cls, 0, -6)) . '.php';
            return false;
        case 'User_greeting_count':
            include_once $dir . '/'.$cls.'.php';
            return false;
        default:
            return true;
        }
    }

    function onStartAvatarFormData($action) {
		$openboothscript = <<<EOF
EOF;
		$action->out->element('script', array('type'=>'text/javascript'), $openboothscript);
    }

    function onPluginVersion(&$versions)
    {
        $versions[] = array('name' => 'AvatarBooth',
                            'version' => STATUSNET_VERSION,
                            'author' => 'Mikael Nordfeldth',
                            'homepage' => 'http://blog.mmn-o.se/dev/sn-plugins/avatarbooth',
                            'rawdescription' =>
                          // TRANS: Plugin description.
                            _m('Avatar booth for machines with webcam and Flash support'));
        return true;
    }
}
