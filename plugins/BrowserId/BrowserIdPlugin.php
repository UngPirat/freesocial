<?php
/**
 * StatusNet, the distributed open-source microblogging tool
 *
 * Plugin to authenticate using BrowserID
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
 * Mollom is a bayesian spam checker, wrapped into a webservice
 * This plugin is based on the Drupal Mollom module
 *
 * @category  Plugin
 * @package   StatusNet
 * @author    Mikael Nordfeldth
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 *
 */

if (!defined('STATUSNET')) {
    exit(1);
}

class BrowserIdPlugin extends Plugin
{
	function onEndShowStatusNetStyles($action) {
        $action->script($this->path('js/include.js'));
        $action->script($this->path('js/browserid_for_statusnet.js'));
	}

    /*
     * Add a login tab for 'Sign in with BrowserID'
     *
     * @param Action $action the current action
     *
     * @return void
     */
    function onEndLoginGroupNav($action) {
        $action->menuItem(
            common_local_url('browseridlogin'),
            // TRANS: Menu item in login navigation.
            _m('MENU','BrowserID'),
            // TRANS: Title for menu item in login navigation.
            _m('Login or register using BrowserID.')
        );

		return true;
    }

    function onAutoload($cls)
    {
        $dir = dirname(__FILE__);

        switch ($cls) {
        case 'BrowseridloginAction':
            include_once $dir . '/' . strtolower(mb_substr($cls, 0, -6)) . '.php';
            return false;
		default:
			return true;
		}
	}
    function onRouterInitialized($m) {
        $m->connect(
                    'main/browseridlogin',
                    array('action' => 'browseridlogin')
                );
        return true;
    }
}
