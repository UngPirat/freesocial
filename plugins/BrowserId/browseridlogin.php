<?php
/**
 * StatusNet, the distributed open-source microblogging tool
 *
 * 'Sign in with Twitter' login page
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
 * @author    Julien Chaumond <chaumond@gmail.com>
 * @author    Zach Copley <zach@status.net>
 * @copyright 2010 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

/**
 * Page for logging in with Twitter
 *
 * @category Login
 * @package  StatusNet
 * @author   Julien Chaumond <chaumond@gmail.com>
 * @author   Zach Copley <zach@status.net>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 *
 * @see      SettingsAction
 */
class BrowseridloginAction extends Action
{
    function handle($args)
    {
        parent::handle($args);

        if (common_is_real_login() && $returnto = common_get_returnto()) {
            // TRANS: Client error displayed when trying to log in using Twitter while already logged in to StatusNet.
            header('Location: '.$returnto);
			die;
        } elseif ($this->isPost()) {
			$this->handlePost();
		}

        $this->showPage();
    }

    function title()
    {
        // TRANS: Title for login using Twitter page.
        return _m('TITLE','BrowserID Login');
    }

    function getInstructions()
    {
        // TRANS: Instructions for login using Twitter page.
        return _m('Login with your BrowserID');
    }

    function showPageNotice()
    {
        $instr = $this->getInstructions();
        $output = common_markup_to_html($instr);
        $this->elementStart('div', 'instructions');
        $this->raw($output);
        $this->elementEnd('div');
    }

    function showContent()
    {
        $this->elementStart('a', array('href' => '#', 'onclick' => 'browserid_for_statusnet();'));
        $this->element('img', array('src' => Plugin::staticPath('BrowserId', 'sign_in.png'),
                                    // TRANS: Alternative text for "sign in with Twitter" image.
                                    'alt' => _m('Sign in with BrowserID')));
        $this->elementEnd('a');
    }

    function showLocalNav()
    {
        $nav = new LoginGroupNav($this);
        $nav->show();
    }

	function handlePost() {
		$assertion = $this->trimmed('assertion');
		if (!empty($assertion)) {
            $request = HTTPClient::start();
			$response = $request->post('https://verifier.login.persona.org/verify', array(),
									array(
										'assertion'=>$assertion,
										'audience'=>common_local_url('public').':'.$_SERVER['SERVER_PORT'])
									);
			if (!$response->isOk()) {
				throw new ServerException(_m('Bad assertion data'));
			}
			$data = json_decode($response->getBody());
			if (!is_object($data)) {
				throw new ServerException(_m('Got bad data from provider'));
			}
			$user = User::staticGet('email', $data->email);
			if (empty($user)) {
				throw new ClientException(_m('No user with that email found'));
			}
			common_set_user($user);
			common_real_login();
		} else {
			throw new ServerException(_m('No assertion data'));
		}
		die;
	}
}
