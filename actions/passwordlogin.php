<?php
/**
 * StatusNet, the distributed open-source microblogging tool
 *
 * Login form
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
 * @category  Login
 * @package   StatusNet
 * @author    Evan Prodromou <evan@status.net>
 * @author    Sarven Capadisli <csarven@status.net>
 * @copyright 2008-2009 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

/**
 * Login form
 *
 * @category Personal
 * @package  StatusNet
 * @author   Evan Prodromou <evan@status.net>
 * @author   Sarven Capadisli <csarven@status.net>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 */
class PasswordloginAction extends LoginAction
{
    /**
     * Handle input, produce output
     *
     * Switches on request method; either shows the form or handles its input.
     *
     * @param array $args $_REQUEST data
     *
     * @return void
     */
    function handle($args)
    {
        parent::handle($args);

        if (common_is_real_login()) {
            $user = common_current_user();
            common_redirect(common_local_url('timeline', array('nickname' => $user->nickname)), 307);
        } else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->checkLogin();
        } else {
            common_ensure_session();
            $this->showForm();
        }
    }

    /**
     * Store an error and show the page
     *
     * This used to show the whole page; now, it's just a wrapper
     * that stores the error in an attribute.
     *
     * @param string $error error, if any.
     *
     * @return void
     */
    function showForm($error=null)
    {
        $this->error = $error;
        $this->showPage();
    }

    /**
     * Check the login data
     *
     * Determines if the login data is valid. If so, logs the user
     * in, and redirects to the 'with friends' page, or to the stored
     * return-to URL.
     *
     * @return void
     */
    function checkLogin($user_id=null, $token=null)
    {
        // XXX: login throttle

        $nickname = $this->trimmed('nickname');
        $password = $this->arg('password');

        $user = common_check_user($nickname, $password);

        if (!$user) {
            // TRANS: Form validation error displayed when trying to log in with incorrect credentials.
            $this->showForm(_('Incorrect username or password.'));
            return;
        }

        // success!
        if (!common_set_user($user)) {
            // TRANS: Server error displayed when during login a server error occurs.
            $this->serverError(_('Error setting user. You are probably not authorized.'));
            return;
        }

        common_real_login(true);

        if ($this->boolean('rememberme')) {
            common_rememberme($user);
        }

        $url = common_get_returnto();

        if ($url) {
            // We don't have to return to it again
            common_set_returnto(null);
	    $url = common_inject_session($url);
        } else {
            $url = common_local_url('timeline',
                                    array('nickname' =>
                                          $user->nickname));
        }

        common_redirect($url, 303);
    }

    function showScripts()
    {
        parent::showScripts();
        $this->autofocus('nickname');
    }

    /**
     * Title of the page
     *
     * @return string title of the page
     */
    function title()
    {
        // TRANS: Page title for login page.
        return _('Password login');
    }

    /**
     * Core of the display code
     *
     * Shows the login form.
     *
     * @return void
     */
    function showContent()
    {
        $this->elementStart('form', array('method' => 'post',
                                          'id' => 'form_login',
                                          'class' => 'form_settings',
                                          'action' => common_local_url('passwordlogin')));
        $this->elementStart('fieldset');
        // TRANS: Form legend on login page.
        $this->element('legend', null, _('Login to site'));
        $this->elementStart('ul', 'form_data');
        $this->elementStart('li');
        // TRANS: Field label on login page.
        $this->input('nickname', _('Username'));
        $this->elementEnd('li');
        $this->elementStart('li');
        // TRANS: Field label on login page.
        $this->password('password', _('Password'));
        $this->elementEnd('li');
        $this->elementStart('li');
        // TRANS: Checkbox label label on login page.
        $this->checkbox('rememberme', _('Remember me'), false,
                        // TRANS: Checkbox title on login page.
                        _('Automatically login in the future; ' .
                          'not for shared computers!'));
        $this->elementEnd('li');
        $this->elementEnd('ul');
        // TRANS: Button text for log in on login page.
        $this->submit('submit', _m('BUTTON','Login'));
        $this->elementEnd('fieldset');
        $this->elementEnd('form');
        $this->elementStart('p');
        $this->element('a', array('href' => common_local_url('recoverpassword')),
                       // TRANS: Link text for link to "reset password" on login page.
                       _('Lost or forgotten password?'));
        $this->elementEnd('p');
    }

    /**
     * Instructions for using the form
     *
     * For "remembered" logins, we make the user re-login when they
     * try to change settings. Different instructions for this case.
     *
     * @return void
     */
    function getInstructions()
    {
        if (common_logged_in() && !common_is_real_login() &&
            common_get_returnto()) {
            // rememberme logins have to reauthenticate before
            // changing any profile settings (cookie-stealing protection)
            // TRANS: Form instructions on login page before being able to change user settings.
            return _('For security reasons, please re-enter your ' .
                     'user name and password ' .
                     'before changing your settings.');
        } else {
            // TRANS: Form instructions on login page.
            $prompt = _('Login with your username and password.');
            if (!common_config('site', 'closed') && !common_config('site', 'inviteonly')) {
                $prompt .= ' ';
                // TRANS: Form instructions on login page. This message contains Markdown links in the form [Link text](Link).
                // TRANS: %%action.register%% is a link to the registration page.
                $prompt .= _('Don\'t have a username yet? ' .
                             '[Register](%%action.register%%) a new account.');
            }
            return $prompt;
        }
    }
}
