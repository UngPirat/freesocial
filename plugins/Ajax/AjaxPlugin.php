<?php
/**
 * StatusNet - the distributed open-source microblogging tool
 * Copyright (C) 2010-2011, StatusNet, Inc.
 *
 * Converts your site to one with many features imported from Free & Social
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
 * @category  Plugin
 * @package   StatusNet
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @copyright 2012 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html AGPL 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET')) {
    exit(1);
}

class AjaxPlugin extends Plugin {
    function onAutoload($cls) {
        switch ($cls) {
		case 'Ajax':
			require_once __DIR__."/classes/$cls.php";
			return false;
		case 'AjaxAction':
			require_once __DIR__.'/actions/'.strtolower(substr($cls, 0, -6)).'.php';
			return false;
		default:
			break;
        }

		return true;
    }

    function onStartInitializeRouter($m)
    {
        $m->connect('ajax/:resource', array('action' => 'ajax', 'resource'=>'\w+\-\d+'));
		return true;
	}

    function onPluginVersion(&$versions) {
        $versions[] = array(
            'name' => 'Ajax',
            'version' => STATUSNET_VERSION,
            'author' => 'Mikael Nordfeldth',
            'homepage' => 'https://freesocial.org/',
            'rawdescription' =>
             // TRANS: Plugin description.
            _m('Activates /ajax/:resource route for easy ajax access')
        );

        return true;
    }
}
