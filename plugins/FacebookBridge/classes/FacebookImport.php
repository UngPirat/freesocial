<?php
/**
 * StatusNet, the distributed open-source microblogging tool
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
 * @author    Zach Copley <zach@status.net>
 * @author    Julien C <chaumond@gmail.com>
 * @author    Brion Vibber <brion@status.net>
 * @copyright 2009-2010 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET')) {
    exit(1);
}

require_once INSTALLDIR . '/plugins/FacebookBridge/lib/facebookclient.php';

define('FACEBOOK__TO_EVERYONE', 'EVERYONE');
define('FACEBOOK__TO_NETWORKS_FRIENDS', 'NETWORKS_FRIENDS');
define('FACEBOOK__TO_ALL_FRIENDS', 'ALL_FRIENDS');
define('FACEBOOK__TO_CUSTOM', 'CUSTOM');    //sets 'friends' => 'SOME_FRIENDS' for example

define('FACEBOOK__GROUP_OPEN', 'OPEN');
define('FACEBOOK__GROUP_SECRET', 'SECRET');

/**
 * Encapsulation of the Facebook update -> notice incoming bridge import.
 * Is used by both the polling facebookstatusfetcher.php daemon, and the
 * in-progress streaming import.
 *
 * @category Plugin
 * @package  StatusNet
 * @author   Mikael Nordfeldth <mmn@hethane.se>
 * @author   Zach Copley <zach@status.net>
 * @author   Julien C <chaumond@gmail.com>
 * @author   Brion Vibber <brion@status.net>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 */
class FacebookImport
{
    protected $scope = 0;
    protected $groups = array();
    protected $client = null;
    protected $facebook = null;
    protected $flink = null;

    function __construct($foreign_id=null) {
        $this->groups = array();
		if (!is_null($foreign_id)) {
			$this->flink = Foreign_link::getByForeignID($foreign_id, FACEBOOK_SERVICE);
			if (empty($this->flink) || empty($this->flink->credentials)) {
				throw new Exception('Empty or invalid Foreign_link');
			}
		}
		$profile = (!is_null($this->flink)) ? Profile::staticGet('id', $this->flink->user_id) : null;
		$this->client   = new Facebookclient(null, $profile);
        $this->facebook = Facebookclient::getFacebook();
    }

    public function getGroups() {
        return $this->groups;
    }

    function name()
    {
        return get_class($this);
    }

    function getAccessToken() {
        return (!is_null($this->flink) && !empty($this->flink->credentials))
                ? $this->flink->credentials
                : $this->facebook->getAccessToken();
    }

    public function importUpdates($field, array $apiArgs=array()) {
        if ($this->flink === null) {
            throw new Exception('No foreign link');
        }

        // Iterate the loop backwards <=7 pages
		$args = array();
        $args['feed'] = $this->flink->foreign_id."/$field";
        $args['max_loops'] = 7;
		$args['api'] = $apiArgs;
        $args['api']['access_token'] = $this->flink->credentials;
		common_debug('FBDBG importing updates for '.$this->flink->foreign_id);
        $this->client->apiLoop('/me/'.urlencode($field), array($this, 'importThread'), $args);

        // Okay, record the time we synced with Facebook for posterity
        $original = clone($this->flink);
        $this->flink->last_noticesync = common_sql_now();
        $this->flink->update($original);
    }

    public function importPage($object)
    {
        if ($this->flink === null) {
            throw new Exception('No foreign link');
        }

        common_debug('FBDBG: getting foreign page '.$object->foreign_id.' because it is local group '.$object->group_id);
        // get this page's feed
        $this->client->apiLoop(sprintf('/me/feed', $object->foreign_id), array($this, 'importThread'), array('callback'=>array('scope'=>Notice::PUBLIC_SCOPE)));
    }

    public function importGroup($group, $limit=6)
    {
        common_debug('FBDBG: getting foreign group '.$group->foreign_id.' because it is local group '.$group->group_id);
        // get this group's feed
		$args = array(
					'callback'=>array(
						'group'=>$group->foreign_id,
						'scope'=>Notice::PUBLIC_SCOPE,
						),
					'api'=>array('limit'=>$limit),
					);
        $this->client->apiLoop(sprintf('/%s/feed', $group->foreign_id), array($this, 'importThread'), $args);
    }
	protected function getComments($thread_id) {
		if (!preg_match('/^\d+_\d+/', $thread_id)) {
			throw new Exception('Bad thread id: '.$thread_id);
		}
		$thread_id = preg_replace('/^(\d+_\d+)(_.+)?$/', '\\1', $thread_id);
		$data = $this->facebook->api("/$thread_id", 'get', $this->getAccessToken());
		file_put_contents('/tmp/facebook-getComments', print_r($data,true));
		return $data['comments'];
	}

    public function importThread(array $args)
    {
        $update = $args['entry'];
        if (!isset($update['message'])) {
		    return false;
		}
		try {
			// see if profile disallows importing etc.
			self::checkNoticeImport($update);
		} catch (Exception $e) {
			return false;
		}

		// GAAAHHHH, Facebook can reference posts by group OR user_id in the first part of the post ID
		if (isset($args['group'])) {
			common_debug('FACEBOOK fetching from group, replacing '.$update['id'].' with '.preg_replace("/^{$args['group']}_/", $update['from']['id'].'_', $update['id']));
			$update['id'] = preg_replace("/^{$args['group']}_/", $update['from']['id'].'_', $update['id']);
		}
		$foreign_id = (!is_null($this->flink))
							? $this->flink->foreign_id
							: null;

		$scope = isset($args['scope']) ? $args['scope'] : Notice::ADDRESSEE_SCOPE|Notice::FOLLOWER_SCOPE;
        if (isset($update['privacy']['value'])) {
			$scope = self::getPrivacyScope($update['privacy']['value']);
		}

        $qm = QueueManager::get();
        // Add more as we understand them
        switch ($update['type']) {
        case 'status':
        case 'link':
        case 'photo':
        case 'video':
			$comments = array();
			if (isset($update['comments'])) {
				$comments = $update['comments'];
				unset($update['comments']); // ...if we were to enqueue $update
			}
			// We should already have the notice in our foreign_notice_map, so it won't be imported if that's the case.
			// If we don't want it - delete it at both places!
	        //$source = mb_strtolower(common_config('integration', 'source'));
    	    //if (!isset($update['application']) || !preg_match("/$source/", mb_strtolower($update['application']['name']))) {
			$notice = $this->saveUpdate($update, $scope);
			if (empty($notice)) {	// this shouldn't happen, saveUpdate should always throw an exception if failed
				common_debug('FBDBG: saveUpdate did not return a Notice but did not throw exception!');
				return false;
			}
			//}
			$conversation = Conversation::staticGet('id', $notice->conversation);
			if (empty($conversation)) {
				throw new Exception('Conversation could not be fetched');
			}
			if (strtotime($update['updated_time']) < strtotime($conversation->modified)) {
				return false;
			}

			// Enqueue the comments
            if (!empty($comments) && $comments['count']>0) {
                if (!isset($comments['data'])) { 
                    common_debug('FACEBOOK comment thread empty for '.$update['id'].'. TODO: fetch from API!'); 
					$comments = $this->getComments($update['id']);
                }
				common_debug('FACEBOOK enqueueing '.count($comments['data']).' comments for '.$update['id'].' #'.$notice->id);
                foreach ($comments['data'] as $comment) :
    				try {
    					// see if profile disallows importing etc.
    					self::checkNoticeImport($comment);
    				} catch (Exception $e) {
    					continue;
    				}
    				try {
    					// see if notice is already imported
    					Foreign_notice_map::get_notice_id($comment['id'], FACEBOOK_SERVICE);
    					continue;
    				} catch (Exception $e) {
    					$data = array(
       	                'receiver' => $foreign_id,
           	            'scope'    => $scope,
               	        'update'   => $comment,
                   	    );
    					$qm->enqueue($data, 'facebookin');
    				}
                endforeach;
    		}
			$original = clone($conversation);
			$conversation->modified = common_sql_now();
			$conversation->update($original);
			return true;
            break;
        default:
            common_debug('FACEBOOK unknown update type: '.$update['type'].' for '.$update['id']);
        }

        return false; // no new posts found (then we'd been returned above)
    }

    static function getPrivacyScope($privacy) {
        switch ($privacy) {
        case FACEBOOK__TO_CUSTOM:
            // FIXME: ...how do we know who are the custom SOME_FRIENDS?
            return Notice::ADDRESSEE_SCOPE;
        case FACEBOOK__TO_NETWORKS_FRIENDS:
        case FACEBOOK__TO_ALL_FRIENDS:
               // FIXME: save it but treat it as private
            return Notice::FOLLOWER_SCOPE;
        case FACEBOOK__TO_EVERYONE:
            return Notice::PUBLIC_SCOPE;
        default:
            throw new Exception('Unknown FACEBOOK privacy state: '.$privacy);
        }
    }

    static function checkNoticeImport(array $update) {
        $profile = null;
        $flink = Foreign_link::getByForeignID($update['from']['id'], FACEBOOK_SERVICE);
        if (!empty($flink) && ($flink->noticesync & FOREIGN_NOTICE_RECV) == FOREIGN_NOTICE_RECV) {
            $profile = Profile::staticGet('id', $flink->user_id);
            $flink->free();
            unset($flink);
        } elseif (!empty($flink)) {
            // this is actually odd behaviour, but reasonable as users must be able to decide not to be imported
            throw new Exception('Foreign link for '.$flink->user_id.' ('.$flink->foreign_id.') disallows importing');
        }
        return $profile;
    }

    function saveUpdate($update, $scope=0)
    {
        $profile = self::checkNoticeImport($update);    // possibly set $profile to local profile
        $local   = !empty($profile);

        try {
            $notice = Foreign_notice_map::get_foreign_notice($update['id'], FACEBOOK_SERVICE);
            return $notice;
        } catch (Exception $e) {
			common_debug('FACEBOOK could not find foreign notice map for: '.$update['id']);
            $noticeOpts = array();
        }

        if (empty($profile)) {
            $profile = $this->ensureProfile($update['from']['id']);
        }

        if (empty($profile)) {
            common_log(LOG_ERR, $this->name() .
                ' - Problem saving notice. No associated Profile.');
            throw new Exception('Profile not available');
        } elseif ($profile->isSilenced()) {
            throw new Exception('Profile is silenced');
        }


        $noticeOpts['content']    = html_entity_decode($update['message'], ENT_QUOTES, 'UTF-8');
        $noticeOpts['scope']      = $scope;
        $noticeOpts['source']     = 'facebook';
        $noticeOpts['profile_id'] = $profile->id;
        $noticeOpts['created']    = strftime(
            '%Y-%m-%d %H:%M:%S',
            strtotime($update['created_time'])
        );

        try {
            $replyToId = $this->getReplyToId($update['id']);
            $reply = Foreign_notice_map::get_foreign_notice($replyToId, FACEBOOK_SERVICE);
            $rprofile = $reply->getProfile();
            $noticeOpts['reply_to']     = $reply->id;
            $noticeOpts['scope']        = $reply->scope;
            $noticeOpts['conversation'] = $reply->conversation;
        } catch (Exception $e) {
            $reply = null;
            // either this is the original post or the reply-to post has not been imported/mapped
        }

        if ($local) {
            $noticeOpts['is_local'] = ($scope == Notice::PUBLIC_SCOPE ? Notice::LOCAL_PUBLIC : Notice::LOCAL_NONPUBLIC);
        } else {
            $noticeOpts['is_local'] = Notice::GATEWAY;
			$original_poster = (!is_null($reply) ? $rprofile->id : $profile->id);
			if ($original_poster_flink = Foreign_link::getByUserID($original_poster, FACEBOOK_SERVICE)) {
				$fuser = $original_poster_flink->getForeignUser();
				$nickname = !empty($fuser) ? $fuser->nickname : null;
			} else {
				$nickname = $original_poster->nickname;
			}
            $noticeOpts['uri']      = $this->makeUpdateURI($update['id'], $nickname);
        }

        $notice  = Notice::saveNew($noticeOpts['profile_id'], $noticeOpts['content'], $noticeOpts['source'], $noticeOpts);
        Foreign_notice_map::saveNew($notice->id, $update['id'], FACEBOOK_SERVICE);
		
        if (!isset($update['type'])) {
            $update['type'] = 'status';
        }

        switch ($update['type']) {
        case 'link':
            File::processNew($update['link'], $notice->id);
            break;
        }

        if (isset($update['likes'])) {
            try {
                $this->client->apiLoop(sprintf('/%s/likes', $update['id']), array($this, 'saveLike'), array('callback'=>array('notice'=>$notice)));
            } catch (Exception $e) {
            }
        }
        $this->saveUpdateMentions($notice, $update);

        try {
            $this->saveUpdateAttachments($notice, $update, $profile);
        } catch (Exception $e) {
            common_debug('FBDBG file download failed for notice '.$notice->id);
        }

        if (!$local && isset($this->flink)) {    // otherwise foreign users' posts won't get put in the home feed
            common_debug('Inserting Notice('.$notice->id.') into Inbox('.$this->flink->user_id.', FB:'.$this->flink->foreign_id.')');
            Inbox::insertNotice($this->flink->user_id, $notice->id);
        }

        $notice->blowOnInsert();

        $this->updates++;
        return $notice;
    }

    function saveLike(array $args)
    {
        $notice = $args['notice'];
        $entry  = $args['entry'];
        
        $profile = $this->ensureProfile($entry['id']);
        common_debug('FBDBG adding favorite from FB user '.$entry['id'].' (profile '.$profile->id.') for notice '.$notice->id);
        if (!$profile->hasFave($notice)) {
            Fave::addNew($profile, $notice);
        }
    }

    protected function saveUpdateAttachments($notice, $update, $profile) {
        if (!isset($update['type']) || !common_config('attachments', 'process_links')) {
            return false;
        }
        switch ($update['type']) {
        case 'link':
            $file = File::processNew($update['link'], $notice->id);
			common_debug('FACEBOOK storing link as file: '.$file->id);
			break;
        case 'video':
            common_debug('FACEBOOK VIDEO: '.print_r($update,true));
            $description = $update['description'];
            File::processNew($update['link'], $notice->id);
            break;
        case 'photo':
            $url = preg_replace('/\_s\.jpg$/', '_n.jpg', $update['picture']);    // _n is a bigger version
            $filename = 'Facebook_'.urlencode($update['id']).'-original-'.urlencode(basename($url));

            FacebookImport::fetchRemoteUrl($url, File::path($filename));
//            $attachment = File::processNew(File::url($filename), $notice->id);
            $mediafile = new MediaFile($profile, $filename, MediaFile::getUploadedFileType(File::path($filename)));
            $mediafile->attachToNotice($notice);
    
            common_debug('FACEBOOK imported File '.$mediafile->fileRecord->id.' by URL to notice '.$notice->id);
            return true;
            break;
            break;
        default:
            return false;
        }
    }

    function saveUpdateMentions($notice, $update)
    {
        $mentions = array();

        if (isset($update['message_tags'])) {
            foreach ($update['message_tags'] as $tag) {
                switch ($tag['type']) {
                case 'user':
                    $mentions[$tag['id']] = $tag;
                    break;
                case 'group':
                    $mentions[$tag['id']] = $tag;
                    break;
                default:
                    common_debug(__METHOD__.' has not implemented message_tags type '.$tag['type'].': '.print_r($tag,true));
                }
            }
        }

        if (isset($update['to']['data'])) {
            foreach ($update['to']['data'] as $rcpt) {
                if (!isset($mentions[$rcpt['id']])) {
                    $mentions[$rcpt['id']] = $rcpt;
                }
            }
        }
           if (empty($mentions)) {
            return null;
        }

        $destinations = array();
        foreach ($mentions as $foreign_id=>$group) {
            common_debug('FBDBG: trying '.$foreign_id.' as Foreign_group');
            try {
                $destinations[] = Foreign_group::getGroupID($foreign_id, FACEBOOK_SERVICE);
                unset($mentions[$foreign_id]);    // it's a group so don't process as user below

            } catch (Exception $e) {
                common_debug('FBDBG: '.$e->getMessage());
                continue;
            }
        }
        common_debug('FBDBG: adding '.$notice->id.' to groups '.implode(',', $destinations));
        $notice->saveKnownGroups($destinations);

        $destinations = array();
        foreach ($mentions as $foreign_id=>$user) {
            try {
                // deliver locally linked users if they exist
                $fetch = Foreign_link::getByForeignID($foreign_id, FACEBOOK_SERVICE);

                if (empty($fetch)) {
                    continue;
                }
                $fetch = Profile::staticGet($fetch->user_id);    // flink found

                $destinations[] = $fetch->profileurl;    // profile found/created
                $fetch->free();
            } catch (Exception $e) {
                continue;
            }
        }
        common_debug('FBDBG: replying '.$notice->id.' to profiles '.implode(' ', $destinations));
        $notice->saveKnownMentions($destinations);

        return true;
    }

    /**
     * Make an URI for an update.
     *
     * @param object $status status object
     *
     * @return string URI
     */
    function makeUpdateURI($id,$nickname=null)
    {
        if (!is_null($nickname)) {
            $id = preg_replace('/^(\d+)_/', $nickname.'_', $id);
        }
        return sprintf('http://facebook.com/%s', preg_replace(array('/_/','/_/'), array('/posts/','?comment_id='), urlencode($id), 1));
    }

    static function getReplyToId($id) {
        if (!preg_match('/^(\d+_\d+)_\d+$/', $id, $matches)) {
            throw new Exception('Not a recognized Facebook comment id');
        }

        return $matches[1];
    }
    static function getOriginalId($id) {
        try {
            $original = FacebookImport::getReplyToId($id);
            return $original;
        } catch (Exception $e) {
            return $id;
        }
    }

    /**
     * Look up a Profile by profileurl field.  Profile::staticGet() was
                 * not working consistently.
     *
     * @param string $nickname   local nickname of the Facebook user
     * @param string $profileurl the profile url
     *
     * @return mixed value the first Profile with that url, or null
     */
    function getProfileByForeignUser($fuser)
    {
        $profile = new Profile();
        $profile->profileurl = $fuser->uri;
        $profile->limit(1);

        if ($profile->find()) {
            $profile->fetch();
            return $profile;
        }

        // this updates the profile in case old facebook urls are stored
        $profile->profileurl = 'https://facebook.com/'.$fuser->id;
        if ($profile->find()) {
            common_debug('OLD FACEBOOK USER FOUND: '.$profile->nickname.' profileurl: '.$profile->profileurl.' replacing with: '.$fuser->uri);
            $profile->fetch();
                // update to new profile link
            $original = clone($profile);
            $profile->profileurl = $fuser->uri;
            $profile->nickname = $fuser->nickname;
            $profile->update($original);
            return $profile;
        }

        throw new Exception('No profile found');
    }

    function ensureProfile($foreign_id)
    {
        $fsrv = new FacebookService();
        $fuser = $fsrv->addForeignUser($foreign_id, $this->getAccessToken());
        // addForeignUser throws an exception if it can't get all necessary data, thus $user is always complete
        try {
            $profile = $this->getProfileByForeignUser($fuser);
        } catch (Exception $e) {    // no profile found, let's create one!
            $profile = $this->createForeignUserProfile($fuser);
        }

        if (!$profile->isSilenced()) {
            try {
                FacebookImport::checkAvatar($profile->id, $foreign_id);
            } catch (Exception $e) {
            }
        }
        return $profile;
    }

    function createForeignUserProfile($foreign_user) {
        $user = $this->facebook->api(sprintf('/%s', $foreign_user->id), 'get');

        $profile = new Profile();
        $profile->query("BEGIN");

        $profile->nickname = $foreign_user->nickname;
           $profile->fullname = $user['name'];
        $profile->profileurl = $foreign_user->uri;
        $profile->created = common_sql_now();

        $id = $profile->insert();

        if (empty($id)) {
            common_log_db_error($profile, 'INSERT', __FILE__);
            $profile->query("ROLLBACK");
            throw new Exception('FACEBOOK foreign user profile already exists? See log_db_error');
        }

        $profile->query("COMMIT");

        if (!$profile) {
            throw new Exception('FACEBOOK profile transaction failed');
        }

        return $profile;
    }

    static function getAvatarUrl($uid, $type='large') {
        // http://developers.facebook.com/docs/reference/api/#pictures
        $url = 'http://graph.facebook.com/'.urlencode($uid).'/picture?type='.urlencode($type);
        $headers = get_headers($url, 1);
        return (isset($headers['Location']) ? $headers['Location'] : $url);
    }

    static function checkAvatar($profile_id, $foreign_id, $url='')
    {
        if (empty($url)) {
            $url = FacebookImport::getAvatarUrl($foreign_id);
        }

        $path_parts = pathinfo($url);
        $ext = isset($path_parts['extension']) ? '.'.$path_parts['extension'] : '';
        $img_root = basename($path_parts['basename'], "_n{$ext}");    // q stands for square
        $filename = "Facebook_{$foreign_id}-original-{$img_root}{$ext}";

        try {
            $avatar = Avatar::getOriginal($profile_id);
            $oldname = $avatar->filename;
        } catch (Exception $e) {
            $oldname = null;
        }

        if ($filename == $oldname) {
            return $avatar;
        }

        if (FacebookImport::fetchRemoteUrl($url, Avatar::path($filename))) {
            try {
                Avatar::deleteFromProfile($profile_id);
            } catch (Exception $e) {
                common_debug('FACEBOOK no avatars to delete');
            }

            FacebookImport::newAvatar($profile_id, 180, $filename);
        }
    }

    static function newAvatar($profile_id, $size=180, $filename)
    {
        $imagefile = new ImageFile($profile_id, Avatar::path($filename));
        $x = floor($imagefile->width/2)-floor($size/2);
        $y = floor($imagefile->height/2)-floor($size/2);
        $result = $imagefile->resizeTo(Avatar::path($filename), $size, $size, $x, $y, $size, $size);    // overwrite with cropped image

        if (!$result) {
            common_debug('FACEBOOK avatar cannot resizeTo '.Avatar::path($filename));
            @unlink(Avatar::path($filename));
        }

        $avatar = new Avatar();
        $avatar->profile_id = $profile_id;
        $avatar->original = 1; // Let's make this the original/base avatar
        $avatar->width  = $size;
        $avatar->height = $size;

        $avatar->mediatype = 'image/jpeg';
        $avatar->filename = $filename;
        $avatar->url = Avatar::url($filename);

        $avatar->created = common_sql_now();

        try {
            $id = $avatar->insert();
        } catch (Exception $e) {
            common_log(LOG_WARNING, 'FacebookImport::newAvatar .  Couldn\'t insert avatar - ' . $e->getMessage());
        }

        if (empty($id)) {
            common_log_db_error($avatar, 'INSERT', __FILE__);
            return null;
        }

        return $id;
    }

    /**
     * Fetch a remote avatar image and save to local storage.
     *
     * @param string $url avatar source URL
     * @param string $filename bare local filename for download
     * @return bool true on success, false on failure
     */
    static function fetchRemoteUrl($url, $filename)
    {
        $request = HTTPClient::start();
        $response = $request->get($url);
        if ($response->isOk()) {
            $ok = file_put_contents($filename, $response->getBody());
            if (!$ok) {
                throw new Exception('FacebookImport::fetchRemoteUrl file_put_contents failed for filename '.$filename);
            }
        } else {
            throw new Exception('FacebookImport::fetchRemoteUrl HTTPClient got bad response for '.$url);
        }
        return true;
    }
}
