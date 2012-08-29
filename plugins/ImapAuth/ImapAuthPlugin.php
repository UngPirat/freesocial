<?php
/**
 * StatusNet, the distributed open-source microblogging tool
 *
 * IMAP plugin to allow StatusNet to grab incoming emails and handle them as new user posts
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
 * @copyright 2009 StatusNet, Inc.
 * @copyright 2009 Free Software Foundation, Inc http://www.fsf.org
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET')) {
    exit(1);
}

/**
 * IMAP plugin to allow StatusNet to grab incoming emails and handle them as new user posts
 *
 * @category Plugin
 * @package  StatusNet
 * @author   Mikael Nordfeldth <mmn@hethane.se>
 * @copyright 2009 Free Software Foundation, Inc http://www.fsf.org
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 */
class ImapAuthPlugin extends Plugin
{
    public $mailbox;
    public $decisive;

    function initialize() {
        if(!isset($this->mailbox)){
            // TRANS: Exception thrown when configuration of the IMAP plugin is incorrect.
            throw new Exception(_m('A mailbox must be specified.'));
        }
        if (!isset($this->decisive)) {
            $this->decisive = false;
        }
        return true;
    }

    function onStartCheckPassword($nickname, $password, &$authenticatedUser) {
        $authenticatedUser = $this->check_imap_login($nickname, $password);

        return empty($authenticatedUser) && empty($this->decisive);
    }

    function check_imap_login($nickname, $password) {
    common_debug('ImapAuth check_imap_login');
        if (common_is_email($nickname)) {
            $user = User::staticGet('email', common_canonical_email($nickname));
        } else {
            $user = User::staticGet('nickname', common_canonical_nickname($nickname));
        }

        if (empty($user)) {
            return false;
        }

        $imapuser = $user->nickname.'@'.common_config('site', 'server');
        $conn = imap_open($this->mailbox, $imapuser, $password, OP_HALFOPEN, 0);
    common_debug('ImapAuth against '.$this->mailbox.' with '.$imapuser.' / '.$password);
        if ($conn === FALSE) {
            $authenticatedUser = false;
        } else {
            imap_close($conn);
    	    $authenticatedUser = $user;
        }
    common_debug('ImapAuth result: '.print_r($authenticatedUser, true));

        return $authenticatedUser;
    }

    function onPluginVersion(&$versions)
    {
        $versions[] = array('name' => 'ImapAuth',
                            'version' => STATUSNET_VERSION,
                            'author' => 'Mikael Nordfeldth',
                            'homepage' => 'http://status.net/wiki/Plugin:ImapAuth',
                            'rawdescription' =>
                            // TRANS: Plugin description.
                            _m('Allows a StatusNet server to authenticate against Imap logins.'));
        return true;
    }
}
