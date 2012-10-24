<?php
/**
 * StatusNet, the distributed open-source microblogging tool
 *
 * Class for communicating with Facebook
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
 * @author    Zach Copley <zach@status.net>
 * @copyright 2009-2011 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET')) {
    exit(1);
}

/**
 * Class for communication with Facebook
 *
 * @category Plugin
 * @package  StatusNet
 * @author   Zach Copley <zach@status.net>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 */
class Facebookclient
{
    protected $facebook      = null; // Facebook Graph client obj
    protected $flink         = null; // Foreign_link StatusNet -> Facebook
    protected $notice        = null; // The user's notice
    protected $user          = null; // Sender of the notice

    function __construct($notice, $profile=null) {
        $this->facebook = self::getFacebook();

        if (empty($this->facebook)) {
            throw new FacebookApiException(
                "Could not create Facebook client! Bad application ID or secret?"
            );
        }

        $this->notice = $notice;

		if (!empty($notice) || !empty($profile)) {
	        $profile_id = $profile ? $profile->id : $notice->profile_id;
    	    $this->flink = Foreign_link::getByUserID(
        	    $profile_id,
	            FACEBOOK_SERVICE
    	    );
		}

        if (!empty($this->flink)) {
            $this->user = $this->flink->getUser();
        }
    }

    /*
     * Get an instance of the Facebook Graph SDK object
     *
     * @param string $appId     Application
     * @param string $secret    Facebook API secret
     *
     * @return Facebook A Facebook SDK obj
     */
    static function getFacebook($appId = null, $secret = null)
    {
        // Check defaults and configuration for application ID and secret
        if (empty($appId)) {
            $appId = common_config('facebook', 'appid');
        }

        if (empty($secret)) {
            $secret = common_config('facebook', 'secret');
        }

        // If there's no app ID and secret set in the local config, look
        // for a global one
        if (empty($appId) || empty($secret)) {
            $appId  = common_config('facebook', 'global_appid');
            $secret = common_config('facebook', 'global_secret');
        }

        if (empty($appId)) {
            common_log(
                LOG_WARNING,
                "Couldn't find Facebook application ID!",
                __FILE__
            );
        }

        if (empty($secret)) {
            common_log(
                LOG_WARNING,
                "Couldn't find Facebook application ID!",
                __FILE__
            );
        }

        return new Facebook(
            array(
               'appId'  => $appId,
               'secret' => $secret,
               'cookie' => true
            )
        );
    }

    function apiLoop($path, $callback, array $args=array()) {
		if (isset($args['feed'])) {
			common_debug('FBDBG importing updates in feed '.$args['feed']);
		}

        $loops = 0;
        $max_loops = isset($args['max_loops']) ? $args['max_loops'] : 0;
		if (!isset($args['api'])) {
			$args['api'] = array();
		}
		if (!isset($args['callback'])) {
			$args['callback'] = array();
		}

        do {
            try {
                $result = $this->facebook->api($path, 'get', $args['api']);
            } catch (FacebookApiException $e) {
				return $this->handleFacebookError($e);
            }

            if (empty($result['data'])) {
                common_debug('FBDBG Data empty in loop '.$loops.' for path '.$path);
                break;
            }
        
            $foundnew = false;    // number of new, imported posts
            foreach (array_reverse($result['data']) as $entry) {
                try {
                    // entry post will be overwritten on merge
                    $callbackArgs = array_merge($args['callback'], array('entry'=>$entry));
                    $foundnew = call_user_func($callback, $callbackArgs) || $foundnew;
                } catch (Exception $e) {
                }
            }

            if (isset($result['paging']['next'])) {
                $next = parse_url($result['paging']['next'], PHP_URL_QUERY);
                parse_str($next, $args['api']);    // overwrite with data that makes us go back in time
            }
            $loops++;
        } while ($foundnew==true && ($max_loops == 0 || $loops <= $max_loops));
    }

    /*
     * Broadcast a notice to Facebook
     *
     * @param Notice $notice    the notice to send
     */
    static function facebookBroadcastNotice($notice)
    {
        $client = new Facebookclient($notice);
        return $client->sendNotice();
    }

    /*
     * Should the notice go to Facebook?
     */
    function isFacebookBound() {

        if (empty($this->flink)) {
            // User hasn't setup bridging
            return false;
        }

        // Avoid a loop
        if (mb_strtolower($this->notice->source) == 'facebook') {
            common_log(
                LOG_INFO,
                sprintf(
                    'Skipping notice %d because its source is '.$this->notice->source,
                    $this->notice->id
                ),
                __FILE__
            );
            return false;
        }
    
        // Don't send activity activities (at least for now)
        if (ActivityUtils::compareObjectTypes($this->notice->object_type, ActivityObject::ACTIVITY)) {
            return false;
        }
        $allowedVerbs = array(ActivityVerb::POST, ActivityVerb::SHARE);
        // Don't send things that aren't posts or repeats (at least for now)
        if (!ActivityUtils::compareObjectTypes($this->notice->verb, $allowedVerbs)) {
            return false;
        }

        // If the user does not want to broadcast to Facebook, move along
        if (!(($this->flink->noticesync & FOREIGN_NOTICE_SEND) == FOREIGN_NOTICE_SEND)) {
            common_log(
                LOG_INFO,
                sprintf(
                    'Skipping notice %d because user has FOREIGN_NOTICE_SEND bit off.',
                    $this->notice->id
                ),
                __FILE__
            );
            return false;
        }

        // If it's not a reply, or if the user WANTS to send @-mentions,
		// or if it's a reply to a Facebook post,
        // then, yeah, it can go to Facebook.
        if (($this->flink->noticesync & FOREIGN_NOTICE_SEND_REPLY) == FOREIGN_NOTICE_SEND_REPLY ||
			$this->isForeignNotice($this->notice->reply_to)) {
            return true;
        }

        return false;
    }

	function isForeignNotice($notice_id) {
		return Foreign_notice_map::is_foreign_notice($notice_id, FACEBOOK_SERVICE);
	}
	function getForeignId($notice_id) {
		try {
			$foreign_id = Foreign_notice_map::get_foreign_id($notice_id, FACEBOOK_SERVICE);
		} catch (Exception $e) {
			return null;
		}
		return $foreign_id;
	}
	function deleteNoticeMapping($notice_id) {
		return Foreign_notice_map::delete_notice_mapping($notice_id, FACEBOOK_SERVICE);
	}


    /*
     * Send a notice to Facebook using the Graph API
     */
    function sendNotice()
    {
        if (!$this->isFacebookBound() || empty($this->flink->credentials)) {
            return true;	// dequeue
        }

        try {
            $params = array(
                'access_token' => $this->flink->credentials,
                // XXX: Need to worrry about length of the message?
                'message'      => $this->notice->content
            );

			common_debug('FBDBG: should I comment? reply_to: '.$this->notice->reply_to.' original id= '.FacebookImport::getOriginalId($this->getForeignId($this->notice->reply_to)));
			if (!empty($this->notice->reply_to) && $fb_id = FacebookImport::getOriginalId($this->getForeignId($this->notice->reply_to))) {
				common_debug("FBDBG: attempting to comment with {$this->notice->id} on FACEBOOK post: $fb_id which is parent of ".$this->getForeignId($this->notice->reply_to).' with url: https://graph.facebook.com/'.$fb_id.'/comments?'.http_build_query($params));
				$result = $this->facebook->api(sprintf('/%s/comments', $fb_id), 'post', $params);
				// attachments not supported for comments in Facebook.
                Foreign_notice_map::saveNew($this->notice->id, $result['id'], FACEBOOK_SERVICE);
                return true;
			}

            $attachments = $this->notice->attachments();
    	    if (!empty($attachments)) {
        	    // We can only send one attachment with the Graph API :(
    	        $first = array_shift($attachments);
                if (substr($first->mimetype, 0, 6) == 'image/'
        	                || in_array($first->mimetype, array('application/x-shockwave-flash', 'audio/mpeg' ))) {
	                   $params['picture'] = $first->url;
    	               $params['caption'] = 'Click for full size';
        	           $params['source']  = $first->url;
                }
            }

            $foreign_id = null;
            $gis = Memcached_DataObject::listGet('Group_inbox', 'notice_id', array($this->notice->id));
            foreach ($gis[$this->notice->id] as $gi) {
                try {
                    $foreign_id = Foreign_group::getForeignID($gi->group_id, FACEBOOK_SERVICE);
                    break;
                } catch (Exception $e) {
                    continue;
                }
            }
            unset($gis);

            if (empty($foreign_id)) {
                $foreign_id = 'me';	// references the user whose access_token is passed
            }
            $result = $this->facebook->api(sprintf('/%s/feed', $foreign_id), 'post', $params);

            // Save a mapping
            Foreign_notice_map::saveNew($this->notice->id, $result['id'], FACEBOOK_SERVICE);

        } catch (FacebookApiException $e) {
            return $this->handleFacebookError($e);
        }

        return true;
    }

// this must be run to get a subscription. not necessary per user!
//		Facebookclient::subscribeToRealtime();
	static function subscribeToRealtime() {
        $appsecret = common_config('facebook', 'appsecret');
        if (empty($appsecret)) {
			throw new Exception('You have to set the Facebook appsecret in config');
        }
		$facebook = Facebookclient::getFacebook();
		$params = array(
			'access_token' => $appsecret,
			'object' => 'user',
			//'callback_url' => common_local_url('facebookcallback'),
			'callback_url' => common_config('facebook', 'callback_url'),
			'fields' => 'feed,likes',
			'verify_token' => common_config('facebook', 'callback_token'),
			);
		$result = $facebook->api(sprintf('/%s/subscriptions', $facebook->getAppId()), 'post', $params);
		common_debug('FACEBOOK subscription result: '.print_r($result,true));
		return $result;
	}


    /*
     * Handle a Facebook API Exception
     *
     * @param FacebookApiException $e the exception
     *
     */
    function handleFacebookError($e)
    {
        $fbuid  = (!is_null($this->flink)) ? $this->flink->foreign_id : 'APP_CALL';
        $errmsg = $e->getMessage();
        $code   = $e->getCode();

		common_debug('FBDBG got FacebookError ['.$e->getCode().']:'.$e->getMessage().' dump:'.print_r($e, true));

        // The Facebook PHP SDK seems to always set the code attribute
        // of the Exception to 0; they put the real error code in
        // the message. Gar!
        if ($code == 0 && preg_match('/^\(#(?<code>\d+)\)/', $errmsg, $matches)) {
            $code = $matches['code'];
        } elseif ($code == 0 && preg_match('/^Error validating access token/', $errmsg)) {
			$code = 190;
		}

        // XXX: Check for any others?
        switch($code) {
         case 100: // Invalid parameter
            $msg = 'Facebook claims notice %d was posted with an invalid '
                 . 'parameter (error code 100 - %s) Notice details: '
                 . '[nickname=%s, user id=%d, fbuid=%d, content="%s"]. '
                 . 'Dequeing.';
            common_log(
                LOG_ERR, sprintf(
                    $msg,
                    $this->notice->id,
                    $errmsg,
                    $this->user->nickname,
                    $this->user->id,
                    $fbuid,
                    $this->notice->content
                ),
                __FILE__
            );
            return true;
            break;
		case 190:
			// this will only happen on flink'd API calls
            $original = clone($this->flink);
            $this->flink->credentials = '';
            $this->flink->update($original);
			common_debug('FBDBG emailing about expired credentials for flink '.$this->flink->foreign_id.' and message '.$e->getMessage());
            self::emailExpiredCredentials($flink->getUser(), $e->getMessage());
			return true;
			break;
         case 200: // Permissions error
         case 250: // Updating status requires the extended permission status_update
            $this->disconnect();
            return true; // dequeue
            break;
         case 341: // Feed action request limit reached
                $msg = '%s (userid=%d, fbuid=%d) has exceeded his/her limit '
                     . 'for posting notices to Facebook today. Dequeuing '
                     . 'notice %d';
                common_log(
                    LOG_INFO, sprintf(
                        $msg,
                        $user->nickname,
                        $user->id,
                        $fbuid,
                        $this->notice->id
                    ),
                    __FILE__
                );
            // @todo FIXME: We want to rety at a later time when the throttling has expired
            // instead of just giving up.
            return true;
            break;
         default:
            $msg = 'Facebook returned an error we don\'t know how to deal with. '
                 . 'Error code: %d, error message: "%s"'
                 . ' Notice details: [nickname=%s, user id=%d, fbuid=%d. Dequeueing.';
            common_log(
                LOG_ERR, sprintf(
                    $msg,
                    $code,
                    $errmsg,
                    $this->user->nickname,
                    $this->user->id,
                    $fbuid
                ),
                __FILE__
            );
            return true; // dequeue
            break;
        }
    }

    /*
     * Format the text message of a stream item so it's appropriate for
     * sending to Facebook. If the notice is too long, truncate it, and
     * add a linkback to the original notice at the end.
     *
     * @return String $txt the formated message
     */
    function formatMessage()
    {
        // Start with the plaintext source of this notice...
        $txt = $this->notice->content;

        // Facebook has a 420-char hardcoded max.
        if (mb_strlen($statustxt) > 420) {
            $noticeUrl = common_shorten_url($this->notice->uri);
            $urlLen = mb_strlen($noticeUrl);
            $txt = mb_substr($statustxt, 0, 420 - ($urlLen + 3)) . ' … ' . $noticeUrl;
        }

        return $txt;
    }

    /*
     * Format attachments for the old REST API stream.publish method
     *
     * Note: Old REST API supports multiple attachments per post
     *
     */
    function formatAttachments()
    {
        $attachments = $this->notice->attachments();

        $fbattachment          = array();
        $fbattachment['media'] = array();

        foreach($attachments as $attachment)
        {
            if($enclosure = $attachment->getEnclosure()){
                $fbmedia = $this->getFacebookMedia($enclosure);
            }else{
                $fbmedia = $this->getFacebookMedia($attachment);
            }
            if($fbmedia){
                $fbattachment['media'][]=$fbmedia;
            }else{
                $fbattachment['name'] = ($attachment->title ?
                                      $attachment->title : $attachment->url);
                $fbattachment['href'] = $attachment->url;
            }
        }
        if(count($fbattachment['media'])>0){
            unset($fbattachment['name']);
            unset($fbattachment['href']);
        }
        return $fbattachment;
    }

    /**
     * given a File objects, returns an associative array suitable for Facebook media
     */
    function getFacebookMedia($attachment)
    {
        $fbmedia    = array();

        if (strncmp($attachment->mimetype, 'image/', strlen('image/')) == 0) {
            $fbmedia['type']         = 'image';
            $fbmedia['src']          = $attachment->url;
            $fbmedia['href']         = $attachment->url;
        } else if ($attachment->mimetype == 'audio/mpeg') {
            $fbmedia['type']         = 'mp3';
            $fbmedia['src']          = $attachment->url;
        }else if ($attachment->mimetype == 'application/x-shockwave-flash') {
            $fbmedia['type']         = 'flash';

            // http://wiki.developers.facebook.com/index.php/Attachment_%28Streams%29
            // says that imgsrc is required... but we have no value to put in it
            // $fbmedia['imgsrc']='';

            $fbmedia['swfsrc']       = $attachment->url;
        }else{
            return false;
        }
        return $fbmedia;
    }

    /*
     * Disconnect a user from Facebook by deleting his Foreign_link.
     * Notifies the user his account has been disconnected by email.
     */
    function disconnect()
    {
		if (empty($this->flink->credentials)) {
			return false;
		}
        $fbuid = $this->flink->foreign_id;

        common_log(
            LOG_INFO,
            sprintf(
                'Removing Facebook link for %s (%d), fbuid %d',
                $this->user->nickname,
                $this->user->id,
                $fbuid
            ),
            __FILE__
        );

        $original = clone($this->flink);
        $this->flink->credentials = '';
		$this->flink->update($original);

        // Notify the user that we are removing their Facebook link
        if (!empty($this->user->email)) {
            $result = $this->mailFacebookDisconnect();

            if (!$result) {
                $msg = 'Unable to send email to notify %s (%d), fbuid %d '
                     . 'about his/her Facebook link being removed.';

                common_log(
                    LOG_WARNING,
                    sprintf(
                        $msg,
                        $this->user->nickname,
                        $this->user->id,
                        $fbuid
                    ),
                    __FILE__
                );
            }
        } else {
            $msg = 'Unable to send email to notify %s (%d), fbuid %d '
                 . 'about his/her Facebook link being removed because the '
                 . 'user has not set an email address.';

            common_log(
                LOG_WARNING,
                sprintf(
                    $msg,
                    $this->user->nickname,
                    $this->user->id,
                    $fbuid
                ),
                __FILE__
            );
        }
    }

    /**
     * Send a mail message to notify a user that her Facebook link
     * has been terminated.
     *
     * @return boolean success flag
     */
    function mailFacebookDisconnect()
    {
        $profile = $this->user->getProfile();

        $siteName = common_config('site', 'name');

        common_switch_locale($this->user->language);

        // TRANS: E-mail subject.
        $subject = _m('Your Facebook connection has been removed');

        // TRANS: E-mail body. %1$s is a username, %2$s is the StatusNet sitename.
        $msg = _m("Hi %1\$s,\n\n".
                  "We are sorry to inform you we are unable to publish your notice to\n".
                  "Facebook, and have removed the connection between your %2\$s account and\n".
                  "Facebook.\n\n".
                  "This may have happened because you have removed permission for %2\$s\n".
                  "to post on your behalf, or perhaps you have deactivated your Facebook\n".
                  "account. You can reconnect your %2\$s account to Facebook at any time by\n".
                  "logging in with Facebook again.\n\n".
                  "Sincerely,\n\n".
                  "%2\$s\n");

        $body = sprintf(
            $msg,
            $this->user->nickname,
            $siteName
        );

        common_switch_locale();

        $result = mail_to_user($this->user, $subject, $body);

        if (empty($this->user->password)) {
            $result = self::emailWarn($this->user);
        }

        return $result;
    }

    static function emailExpiredCredentials($user, $reason='unknown') {
        $profile = $user->getProfile();

        $siteName  = common_config('site', 'name');
        $siteEmail = common_config('site', 'email');

        if (empty($siteEmail)) {
            common_log(
                LOG_WARNING,
                    "No site email address configured. Please set one."
            );
        }

        common_switch_locale($user->language);

        // TRANS: E-mail subject. %s is the StatusNet sitename.
        $subject = sprintf(_m('%1$s Facebook session token expired'), $siteName);

        // TRANS: E-mail body. %1$s is a username,
        // TRANS: %2$s is the StatusNet sitename, %3$s is the site contact e-mail address.
        $msg = _m("Hi %1\$s,\n\n".
                  "We have received information that your Facebook session for %2\$s has become invalid: '%3\$s'\n\n".
                  "This means that if you want to keep your account connected you must login with Facebook again to establish a new session. You can do this by going to:\n".
                  "%4\$s\n\n".
                  "Sincerely,\n".
                  "%2\$s\n");

        $body = sprintf(
            $msg,
            $user->nickname,
            $siteName,
            $reason,
            common_local_url('facebooklogin')
        );

        common_switch_locale();

        if (mail_to_user($user, $subject, $body)) {
            common_log(
                LOG_INFO,
                sprintf(
                    'Sent Facebook expiration information to %s (%d)',
                    $user->nickname,
                    $user->id
                ),
                __FILE__
            );
        } else {
            common_log(
                LOG_WARNING,
                sprintf(
                    'Unable to send Facebook expiration information to %s (%d)',
                    $user->nickname,
                    $user->id
                ),
                __FILE__
            );
        }
    }
    
    /*
     * Send the user an email warning that their account has been
     * disconnected and he/she has no way to login and must contact
     * the site administrator for help.
     *
     * @param User $user the deauthorizing user
     *
     */
    static function emailWarn($user)
    {
        $profile = $user->getProfile();

        $siteName  = common_config('site', 'name');
        $siteEmail = common_config('site', 'email');

        if (empty($siteEmail)) {
            common_log(
                LOG_WARNING,
                    "No site email address configured. Please set one."
            );
        }

        common_switch_locale($user->language);

        // TRANS: E-mail subject. %s is the StatusNet sitename.
        $subject = _m('Contact the %s administrator to retrieve your account');

        // TRANS: E-mail body. %1$s is a username,
        // TRANS: %2$s is the StatusNet sitename, %3$s is the site contact e-mail address.
        $msg = _m("Hi %1\$s,\n\n".
                  "We have noticed you have deauthorized the Facebook connection for your\n".
                  "%2\$s account.  You have not set a password for your %2\$s account yet, so\n".
                  "you will not be able to login. If you wish to continue using your %2\$s\n".
                  "account, please contact the site administrator (%3\$s) to set a password.\n\n".
                  "Sincerely,\n\n".
                  "%2\$s\n");

        $body = sprintf(
            $msg,
            $user->nickname,
            $siteName,
            $siteEmail
        );

        common_switch_locale();

        if (mail_to_user($user, $subject, $body)) {
            common_log(
                LOG_INFO,
                sprintf(
                    'Sent account lockout warning to %s (%d)',
                    $user->nickname,
                    $user->id
                ),
                __FILE__
            );
        } else {
            common_log(
                LOG_WARNING,
                sprintf(
                    'Unable to send account lockout warning to %s (%d)',
                    $user->nickname,
                    $user->id
                ),
                __FILE__
            );
        }
    }

    /*
     * Check to see if we have a mapping to a copy of this notice
     * on Facebook
     *
     * @param Notice $notice the notice to check
     *
     * @return mixed null if it can't find one, or the id of the Facebook
     *               stream item
     */
    static function facebookStatusId($notice)
    {
        return $this->getForeignId($notice->id);
    }


    /*
     * Get the Foreign_user object for a facebook uid
     * 
     * @param string $fb_id Facebook user id
     *
     * @return Foreign_user object
     */
    static function getFacebookUser($foreign_id) {
        $fuser = Foreign_user::pkeyGet('Foreign_user', array('id'=>$foreign_id, 'service'=>FACEBOOK_SERVICE));
        if (empty($fuser)) {
            throw new Exception('No such foreign user found: '.$foreign_id);
        }
        return $fuser;
    }


    /*
     * Remove an item from a Facebook user's feed if we have a mapping
     * for it.
     */
    function streamRemove()
    {
        $foreign_id = $this->getForeignId($this->notice->id);

        if (!empty($this->flink) && !empty($foreign_id)) {
            try {
                $result = $this->facebook->api(
                    array(
                		'access_token' => $this->flink->credentials,
                        'method'  => 'stream.remove',
                        'post_id' => $foreign_id,
                        'uid'     => $this->flink->foreign_id
                    )
                );

                if (!empty($result) && $result == true) {
                    common_log(
                      LOG_INFO,
                        sprintf(
                            'Deleted Facebook item: %s for %s (%d), fbuid %d',
                            $foreign_id,
                            $this->user->nickname,
                            $this->user->id,
                            $this->flink->foreign_id
                        ),
                        __FILE__
                    );

                    $foreign_id->delete();

                } else {
                    throw new FaceboookApiException(var_export($result, true));
                }
            } catch (FacebookApiException $e) {
                common_log(
                  LOG_WARNING,
                    sprintf(
                        'Could not deleted Facebook item: %s for %s (%d), '
                            . 'fbuid %d - (API error: %s) item already deleted '
                            . 'on Facebook? ',
                        $foreign_id,
                        $this->user->nickname,
                        $this->user->id,
                        $this->flink->foreign_id,
                        $e
                    ),
                    __FILE__
                );
            }

        $this->deleteNoticeMapping($this->notice->id);

        }
    }

    /*
     * Like an item in a Facebook user's feed if we have a mapping
     * for it.
     */
    function like()
    {
        $foreign_id = $this->getForeignId($this->notice->id);

        if (!empty($this->flink) && !empty($foreign_id)) {
            try {
	            $params = array(
    	            'access_token' => $this->flink->credentials,
	            );
				$result = $this->facebook->api(sprintf('/%s/likes', $foreign_id), 'post', $params);

                if (!empty($result) && $result == true) {
                    common_log(
                      LOG_INFO,
                        sprintf(
                            'Added like for item: %s for %s (%d), fbuid %d',
                            $foreign_id,
                            $this->user->nickname,
                            $this->user->id,
                            $this->flink->foreign_id
                        ),
                        __FILE__
                    );
                } else {
                    throw new FacebookApiException(var_export($result, true));
                }
            } catch (FacebookApiException $e) {
                common_log(
                  LOG_WARNING,
                    sprintf(
                        'Could not like Facebook item: %s for %s (%d), '
                            . 'fbuid %d (API error: %s)',
                        $foreign_id,
                        $this->user->nickname,
                        $this->user->id,
                        $this->flink->foreign_id,
                        $e
                    ),
                    __FILE__
                );
            }
        }
    }

    /*
     * Unlike an item in a Facebook user's feed if we have a mapping
     * for it.
     */
    function unLike()
    {
        $foreign_id = $this->getForeignId($this->notice->id);

        if (!empty($this->flink) && !empty($foreign_id)) {
            try {
	            $params = array(
    	            'access_token' => $this->flink->credentials,
	            );
				$result = $this->facebook->api(sprintf('/%s/likes', $foreign_id), 'delete', $params);

                if (!empty($result) && $result == true) {
                    common_log(
                      LOG_INFO,
                        sprintf(
                            'Removed like for item: %s for %s (%d), fbuid %d',
                            $foreign_id,
                            $this->user->nickname,
                            $this->user->id,
                            $this->flink->foreign_id
                        ),
                        __FILE__
                    );

                } else {
                    throw new FacebookApiException(var_export($result, true));
                }
            } catch (FacebookApiException $e) {
                  common_log(
                  LOG_WARNING,
                    sprintf(
                        'Could not remove like for Facebook item: %s for %s '
                          . '(%d), fbuid %d (API error: %s)',
                        $foreign_id,
                        $this->user->nickname,
                        $this->user->id,
                        $this->flink->foreign_id,
                        $e
                    ),
                    __FILE__
                );
            }
        }
    }
}
