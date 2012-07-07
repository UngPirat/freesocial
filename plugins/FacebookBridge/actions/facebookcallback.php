<?php
/**
 * StatusNet - the distributed open-source microblogging tool
 * Copyright (C) 2010, StatusNet, Inc.
 *
 * Facebook callback action, http://developers.facebook.com/docs/reference/api/realtime/
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
 * @author    Zach Copley <zach@status.net>
 * @copyright 2010-2011 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html AGPL 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET')) {
    exit(1);
}

class FacebookcallbackAction extends Action
{
	private $fb = null;

    function handle($args)
    {
        parent::handle($args);

		if ( !$this->fb = Facebookclient::getFacebook() ) {
			throw new Exception(_m('Facebook application not configured'));
		}
		$data = null;
		switch ($_SERVER['REQUEST_METHOD']) {
		case 'GET':
			if ($_GET['hub_mode'] == 'subscribe') {
				$this->verifyToken($_GET['hub_verify_token']);
				$this->challengeResponse($_GET['hub_challenge']);
				die;
			}
			break;
		case 'POST':
			// Sample: {"object":"user","entry":[{"uid":"100002540381705","id":"100002540381705","time":1326414487,"changed_fields":["feed"]}]}
			$data = json_decode(file_get_contents('php://input'));
/*			$headers = getallheaders(); //http_get_request_headers();
			if ($_SERVER['CONTENT_TYPE'] != 'application/json' &&
					isset($headers['X-Hub-Signature']) &&
					$data = http_get_request_body()) {
				if ( 'sha1='.hash_hmac('sha1', $data, $this->fb->secret) !== $headers['X-Hub-Signature']) {
					common_debug('FACEBOOK headers: '.print_r($headers,true));
					common_debug('FACEBOOK got bad signature for body: '.print_r($data,true));
					throw new Exception(_m('Bad signature'));
				}
			}
*/
			if ($data->object == 'user') {
				foreach((array)$data->entry as $entry) {
					$flink = Foreign_link::getByForeignID($entry->uid, FACEBOOK_SERVICE);
					if (($flink->noticesync & FOREIGN_NOTICE_RECV) == FOREIGN_NOTICE_RECV) {
						FacebookRealtime::newRealtimeUpdate($entry);
					}
				}
			}
			break;
		default:
			throw new Exception(_m('Unhandled request method'));
		}

		die;
    }

	// should be connected Facebook user id number
	private function verifyToken($token) {
		// enable to accept new subscriptions
		//$user = Foreign_user::getForeignUser($token, FACEBOOK_SERVICE);
		if (empty($user)) {
			common_debug('FACEBOOK got bad verify_token: '.print_r($token,true));
			throw new Exception('Bad token');
		}
		return !empty($user) ? $user->user_id : false;
	}
	private function challengeResponse($challenge) {
		common_debug('FACEBOOK received challenge: '.print_r($data,true));
		echo $challenge;
		die;
	}
}
