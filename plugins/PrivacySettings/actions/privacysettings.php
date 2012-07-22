<?php
/**
 * StatusNet, the distributed open-source microblogging tool
 *
 * Settings for privacy related stuff
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
 * @category  Settings
 * @package   StatusNet
 * @author    Evan Prodromou <evan@status.net>
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @copyright 2012 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET')) {
    exit(1);
}

require_once INSTALLDIR.'/plugins/OpenID/openid.php';

/**
 * Settings for privacy related stuff
 *
 * @category Settings
 * @package  StatusNet
 * @author   Evan Prodromou <evan@status.net>
 * @author   Mikael Nordfeldth <mmn@hethane.se>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 */
class PrivacysettingsAction extends SettingsAction
{
    /**
     * Title of the page
     *
     * @return string Page title
     */
    function title()
    {
        // TRANS: Title of Privacy settings page for a user.
        return _m('TITLE','Privacy settings');
    }

    /**
     * Instructions for use
     *
     * @return string Instructions for use
     */
    function getInstructions()
    {
        // TRANS: Form instructions for Privacy settings.
        // TRANS: This message contains Markdown links in the form [description](link).
        return _m('Settings related to your online privacy.');
    }

    /**
     * Show the form for Privacy settings
     *
     * We have one form with a few different submit buttons to do different things.
     *
     * @return void
     */
    function showContent()
    {
        $user = common_current_user();
        $settings = array();
        $this->elementStart('form', array('method' => 'post',
                                          'id' => 'form_settings_privacy',
                                          'class' => 'form_settings',
                                          'action' =>
                                          common_local_url('privacysettings')));
        $this->elementStart('ul', 'form_data');

        Event::handle('StartPrivacySettingsForm', array($this, $user));
        // Every plugin with privacy relevant settings hooks onto this form
        Event::handle('EndPrivacySettingsForm', array($this, $user));

        $this->elementEnd('ul');
        $this->elementEnd('form');
    }

    /**
     * Handle a POST request
     *
     * Muxes to different sub-functions based on which button was pushed
     *
     * @return void
     */
    function handlePost()
    {
        // CSRF protection
        $token = $this->trimmed('token');
        if (!$token || $token != common_session_token()) {
            // TRANS: Client error displayed when the session token does not match or is not given.
            $this->showForm(_m('There was a problem with your session token. '.
                              'Try again, please.'));
            return;
        }

        if (Event::handle('StartPrivacySettingsHandlePost', array($this, $user))) :

            Event::handle('EndPrivacySettingsHandlePost', array($this, $user));
        endif;
    }

}
