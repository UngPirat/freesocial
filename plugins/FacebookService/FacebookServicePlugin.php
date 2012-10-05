<?php
/**
 * StatusNet - the distributed open-source microblogging tool
 * Copyright (C) 2010-2011, StatusNet, Inc.
 *
 * A plugin for integrating Facebook with StatusNet. Includes single-sign-on
 * and publishing notices to Facebook using Facebook's Graph API.
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
 * @copyright 2011 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html AGPL 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET')) {
    exit(1);
}

if (!defined('FACEBOOK_SERVICE')) define("FACEBOOK_SERVICE", 2);

/**
 * Main class for Facebook Service plugin
 *
 * @category  Plugin
 * @package   StatusNet
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @copyright 2012 Anyone
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html AGPL 3.0
 * @link      http://status.net/
 */
class FacebookServicePlugin extends Plugin
{
    // remember to add loading of classes, such as Facebookclient

    /*
     * Add version info for this plugin
     *
     * @param array &$versions    plugin version descriptions
     */
    function onPluginVersion(&$versions)
    {
        $versions[] = array(
            'name' => 'Facebook foreign service',
            'version' => STATUSNET_VERSION,
            'author' => 'Mikael Nordfeldth',
            'homepage' => 'http://status.net/wiki/Plugin:FacebookService',
            'rawdescription' =>
             // TRANS: Plugin description.
            _m('A plugin for integrating StatusNet with Facebook.')
        );

        return true;
    }

    function onEndInitializeQueueManager($manager)
    {
        //$manager->connect(FacebookService::TRANSPORT, 'FacebookQueueHandler');
        return true;
    }

	function onStartSubscribe($subscriber, $other) {
        if (!preg_match('/^https?:\/\/(www\.)?facebook\.com\//', $other->profileurl)) {
            return true;	// not a Twitter subscription, continue ordinary subscribe process
        }

		$flink = Foreign_link::getByUserID($subscriber->id, FACEBOOK_SERVICE);
		if ( empty($flink) ) {
			return false;	// no link, impossible to subscribe
		}

        // following through statusnet is not possible yet
        return false;

        return true;
	}

	function onStartUnsubscribe($subscriber, $other) {
        if (!preg_match('/^https?:\/\/(www\.)?facebook\.com\//', $other->profileurl)) {
            return true;	// not a Twitter subscription, continue ordinary subscribe process
        }

		$flink = Foreign_link::getByUserID($subscriber->id, FACEBOOK_SERVICE);
		if ( empty($flink) ) {
			return true;	// no link, but let unsubscribe do its magic
		}

        //unfollowing through statusnet is not possible yet

        return true;
	}
}

class FacebookService extends ForeignServiceClient {
    function __construct(array $params=array())
    {
        $params['service_id'] = FACEBOOK_SERVICE;	// sneak it in there
        parent::__construct($params);	// calls $this->prepare($params);
    }
    function prepare(array $params=array())
    {
        $required = array('appId', 'secret');
        foreach($required as $param) {
            ${$param} = !empty($params[$param]) ? $params[$param] : common_config('facebook', $param);
            if (empty(${$param})) {
            }
        }
        $cookie = isset($cookie) ? $cookie : true;

        $this->client = new Facebook(array(
                                      'appId' => $appId,
                                      'secret' => $secret,
                                      'cookie' => $cookie,
                                    ));
    }

    function addForeignUser($foreign_id, $credentials='', $update=false)
    {
        try {
            $fuser = $this->getForeignUser($foreign_id);
            if (!$update) {
               return $fuser;
            }
        } catch (Exception $e) {
            // no Foreign_user, so we must create one
            $fuser = new Foreign_user();
        }
        $original = clone($fuser);

        $fbuser = $this->fetchUserData($foreign_id, $credentials, array('username','link','id'), true);	// verify is true, so throws exception if not all fields are set

        $fuser->nickname = $fbuser['username'];
        $fuser->uri      = $fbuser['link'];

        if ($update) {
            $fuser->update($original);
        } else {
            $fuser->id       = $fbuser['id'];
            $fuser->service  = FACEBOOK_SERVICE;
            $fuser->created  = common_sql_now();
            $result = $fuser->insert();
            if (empty($result)) {
                common_log_db_error($fuser, 'INSERT', __FILE__);
    			throw new Exception('FACEBOOK foreign user add failed: '.$fbuser->username);
            }
        }

        return $fuser;
    }

    function fetchUserData($foreign_id, $credentials='', array $fields=array(), $verify=false)
    {
        $params = array('access_token' => $credentials, 'fields' => implode(',', $fields));
        // will throw exception on error
        $result = $this->client->api(sprintf('/%s', $foreign_id), 'get', $params);
        if ($verify) : foreach ($fields as $field) {
            if (empty($result[$field])) {
				if ($field=='username' && !empty($result['id'])) {
					$result[$field] = $result['id'];
					continue;
				}
                throw new Exception('Field not returned from API: '.$field);
            }
        } endif;
        return $result;
    }

    function setExtendedAccessToken()
	{
		return $this->client->setExtendedAccessToken();
	}
    function setAccessToken($token)
	{
		return $this->client->setAccessToken($token);
	}
    function getAccessToken()
    {
        return $this->client->getAccessToken();
    }

    static function handleForeignError($e, $flink) {
        $r = $e->getResult();
        if ($r['error']['code']==190) {
            switch($r['error']['error_subcode']) {
            case 458:	//User %i has not authorized application %i.
            case 460:	//The session has been invalidated because the user has changed the password.
            case 463:	//Session has expired at unix time %i. The current unix time is %i.
                self::emailExpiredCredentials($flink->getUser(), $e->getMessage());
                $flink->credentials = '';
                $flink->update();
                break;
            case 2500:
                common_debug('OAuthException: '.$e->getMessage());
                break;
            default:
                common_debug('Unhandled error: ['.$r['error']['code'].'/'.$r['error']['error_subcode'].'] '.$e->getMessage());
            }
        } else {
            common_log(LOG_WARNING, 'FACEBOOK error: ['.$r['error']['code'].'/'.$r['error']['error_subcode'].']: ' . $e->getMessage());
        }
    }
}
