<?php
/**
 * StatusNet, the distributed open-source microblogging tool
 *
 * Plugin that uses the email address as a username, and checks the password as normal
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
 * @author    Craig Andrews <candrews@integralblue.com>
 * @copyright 2009 Free Software Foundation, Inc http://www.fsf.org
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

class LoginWithEmailPlugin extends Plugin
{
    // notice the referenced $nickname variable
    function onStartCheckPassword(&$nickname, $password, &$authenticatedUser)
    {
        if (common_is_email($nickname)) {
            $user = User::staticGet('email',$nickname);
            if(!empty($user)) {
                $nickname = $user->nickname;
            }
        }
    }

    function onPluginVersion(&$versions)
    {
        $versions[] = array('name' => 'Login with Email as nickname',
                            'version' => STATUSNET_VERSION,
                            'author' => 'Mikael Nordfeldth',
                            'homepage' => 'http://status.net/wiki/Plugin:LoginWithEmail',
                            'rawdescription' =>
                            // TRANS: Plugin description.
                            _m('This plugin identifies a login by email address and sets the nickname thereafter.'));
        return true;
    }
}
