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
    protected $imports = 0;
    protected $facebook = null;
    protected $flink = null;

    function __construct($flink) {
        $this->imports = 0;
        $this->flink = $flink;
        $this->facebook = Facebookclient::getFacebook();
    }

    public function getImports() {
        return $this->imports;
    }

    function name()
    {
        return get_class($this);
    }

    function importUpdates($field, $args=array()) {
        $loops = 0;
        $this->flink->last_noticesync = common_sql_now();

        // I think it's ok to go backwards since oldest threads end up first anyway if newly commented
        do {
            if (!isset($args['access_token'])) {
                $args['access_token'] = $this->flink->credentials;
            }
            try {
                $result = $this->facebook->api(sprintf('/%s/', $this->flink->foreign_id).urlencode($field), 'get', $args);
            } catch (Exception $e) {
                common_debug('FACEBOOK returned error for https://graph.facebook.com/'.$this->flink->foreign_id.'/'.$field.'?'.http_build_query($args).': '.$e->getMessage());
                return 0;
            }

            if (empty($result['data'])) {
                common_debug('FACEBOOK Data empty in loop '.$loops.' for foreign_id '.$this->flink->foreign_id);
                break;
            }

            $n = 0;    // number of new, imported posts
            foreach (array_reverse($result['data']) as $update) {
                try {
                    $n += $this->importThread($update);
                } catch (Exception $e) {
                }
            }

            if (isset($result['paging']['next'])) {
                $next = parse_url($result['paging']['next'], PHP_URL_QUERY);
                parse_str($next, $args);    // overwrite with data that makes us go back in time
            }
            $loops++;
        } while ($n > 0 && $loops < 7);

        // Okay, record the time we synced with Facebook for posterity
        $this->flink->update();
        return $this->getImports();    // number of imported posts
    }

    protected function importThread($update)
    {
        $oldImports = $this->getImports();

        $scope = (isset($update['privacy']['value'])
                    ? FacebookImport::getPrivacyScope($update['privacy']['value'])
                    : Notice::PUBLIC_SCOPE);

        // Add more as we understand them
        switch ($update['type']) {
        case 'status':
        case 'link':
        case 'photo':
            if (isset($update['message'])) :
                common_debug('FACEBOOK importing '.$update['id'].' with scope '.$scope);
                // Hacktastic: filter out stuff coming from this StatusNet
                $source = mb_strtolower(common_config('integration', 'source'));
                if ( !isset($update['application']) || !preg_match("/$source/", mb_strtolower($update['application']['name'])) ) {
                    $notice = $this->saveUpdate($update, $scope);
                }
                if ($update['comments']['count']>0) {	// TODO: Check if notice has data?
                    if (isset($update['comments']['data'])) {
                        common_debug('FACEBOOK importing '.$update['comments']['count'].' comments for '.$update['id']);
                        foreach ($update['comments']['data'] as $comment) :
                            common_debug($this->name()." - Importing comment {$comment['id']} from {$comment['from']['id']}");
                            $notice = $this->saveUpdate($comment, $scope);
                        endforeach;
					} else {
						common_debug('FACEBOOK comment thread empty for '.$update['id'].'. TODO: fetch from API!');
					}
                } else {
                    common_debug('FACEBOOK found '.$update['comments']['count'].' comments for '.$update['id']);
                }
            //} elseif (isset($update['story'])) {
            else :
                common_debug('FACEBOOK update '.$update['id'].' with scope '.$scope.' does not have a message set: '.print_r($update,true));
            endif;
            break;
        default:
            common_debug('FACEBOOK unknown update type: '.$update['type'].' for '.$update['id']);
        }

        return $this->getImports() - $oldImports;    // new posts this round
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

    protected function saveUpdate($update, $scope=0)
    {
        try {
            $notice = Foreign_notice_map::get_foreign_notice($update['id'], FACEBOOK_SERVICE);
            //common_debug($this->name()." - Ignoring duplicate import: {$update['id']}  notice id {$notice->id}");
            return $notice;
        } catch (Exception $e) {
            $notice = new Notice();
        }

        $doNoticeImport = false;    // whether to convert it to a local notice

        common_debug('FACEBOOK importing ('.$update['id'].'): '.$update['message']);
        $flink = Foreign_link::getByForeignID($update['from']['id'], FACEBOOK_SERVICE);
        if (!empty($flink) && ($flink->noticesync & Foreign_notice_map::FOREIGN_NOTICE_RECV_IMPORT == Foreign_notice_map::FOREIGN_NOTICE_RECV_IMPORT)) {
            $doNoticeImport = true;
            $profile = Profile::staticGet('id', $flink->user_id);
            unset($flink);
        } else {
            $profile = $this->ensureProfile($update['from']['id']);
        }

        if (empty($profile)) {
            common_log(LOG_ERR, $this->name() .
                ' - Problem saving notice. No associated Profile.');
            throw new Exception('FACEBOOK has no associated profile with '.$update['from']['id'].'/'.$update['from']['name']);
        }

        $notice->scope        = $scope;
        $notice->source     = 'facebook';
        $notice->url        = $this->makeUpdateURI($update['id']);
        $notice->profile_id = $profile->id;
        $notice->created    = strftime(
            '%Y-%m-%d %H:%M:%S',
            strtotime($update['created_time'])
        );

        try {
            $replyToId = $this->getReplyToId($update['id']);
            $reply = Foreign_notice_map::get_foreign_notice($replyToId, FACEBOOK_SERVICE);
            $notice->reply_to     = $reply->id;
            $notice->conversation = $reply->conversation;
        } catch (Exception $e) {
            // either this is the original post or the reply-to post has not been imported/mapped
        }

        $message = $update['message'];
        $notice->content  = html_entity_decode($message, ENT_QUOTES, 'UTF-8');

        if ($doNoticeImport) {
            $noticeOptions = (array)$notice;    // Notice::saveNew should accept a Notice object
            $notice->is_local   = ($scope == Notice::PUBLIC_SCOPE ? Notice::LOCAL_PUBLIC : Notice::LOCAL_NONPUBLIC);
            $notice = Notice::saveNew($notice->profile_id, $notice->content, $notice->source, $noticeOptions);
        } else {
            $notice->is_local   = Notice::GATEWAY;
            $notice->uri        = $notice->url;
               $notice->rendered = common_render_content($message, $notice);
    
            if (empty($notice->conversation)) {
                $conv = Conversation::create();
                $notice->conversation = $conv->id;
                common_log(LOG_INFO, "No known conversation for update {$update['id']} so made a new one {$conv->id}.");
            }

            if (Event::handle('StartNoticeSave', array(&$notice))) {
    
                $id = $notice->insert();

                if (!$id) {
                    common_log_db_error($notice, 'INSERT', __FILE__);
                    common_log(LOG_ERR, $this->name() .
                        ' - Problem saving notice.');
                } else {
                    common_debug('FACEBOOK inserting comment '.$notice->id.' into inbox for '.$this->flink->user_id);
                    Inbox::insertNotice($this->flink->user_id, $notice->id);
                }
    
                Event::handle('EndNoticeSave', array($notice));
            }
        }

        Foreign_notice_map::saveNew($notice->id, $update['id'], FACEBOOK_SERVICE);

        $this->saveUpdateMentions($notice, $update);

        try {
            $this->saveUpdateAttachments($notice, $update, $profile);
        } catch (Exception $e) {
            common_debug('FACEBOOK file download failed for notice '.$notice->id);
        }

        $notice->blowOnInsert();

        $this->imports++;
        return $notice;
    }

    protected function saveUpdateAttachments($notice, $update, $profile) {
        if (!isset($update['type']) || !common_config('attachments', 'process_links')) {
            return false;
        }
        switch ($update['type']) {
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
        default:
            return false;
        }
    }

    function saveUpdateMentions($notice, $update)
    {
        $users = array();

        if (isset($update['message_tags'])) {
            foreach ($update['message_tags'] as $tag) {
				switch ($tag['type']) {
                case 'user':
                    $users[$tag['id']] = $tag;
                    break;
                default:
                    common_debug(__METHOD__.' has not implemented message_tags type '.$tag['type'].': '.print_r($tag,true));
                }
            }
        }

        if (isset($update['to']['data'])) {
            foreach ($update['to']['data'] as $rcpt) {
                if (!isset($users[$rcpt['id']])) {
                    $users[$rcpt['id']] = $rcpt;
                }
            }
        }
           if (empty($users)) {
            return null;
        }

        $reply = new Reply();
        foreach ($users as $foreign_id=>$user) {
            try {
                $profile = Foreign_link::getByForeignID($foreign_id, FACEBOOK_SERVICE);
                if (!empty($profile)) {
                    $profile = $profile->user_id;
                } else {
                    $profile = $this->ensureProfile($foreign_id);
                    $profile = $profile->id;
                }
                $reply->profile_id = $profile;
            } catch (Exception $e) {
                continue;
            }
            $reply->notice_id  = $notice->id;
            $reply->modified   = $notice->created;
               common_log(LOG_INFO, "FACEBOOK saving reply: notice {$notice->id} to profile {$profile}");
            $reply->insert();
        }
    }

    /**
     * Make an URI for an update.
     *
     * @param object $status status object
     *
     * @return string URI
     */
    function makeUpdateURI($id)
    {
        return sprintf('https://facebook.com/%s', preg_replace(array('/_/','/_/'), array('/posts/','?comment_id='), urlencode($id), 1));
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
    function getProfileByForeignId($foreign_id)
    {
		$fuser = Foreign_user::pkeyGet('Foreign_user', array('id'=>$foreign_id, 'service'=>FACEBOOK_SERVICE));
		if (empty($fuser)) {
			throw new Exception('No foreign user found');
		}

        $profile = new Profile();
        $profile->profileurl = $fuser->uri;
        $profile->limit(1);

        if ($profile->find()) {
            $profile->fetch();
            return $profile;
        }

		// backup profileurl
        $profile->profileurl = 'https://facebook.com/'.$foreign_id;
        if ($profile->find()) {
            $profile->fetch();
				// update to new profile link
			common_debug('FACEBOOK updating profile for foreign_id='.$foreign_id.' to profileurl: '.$fuser->uri.' for new user nick '.$fuser->nickname);
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

        // check to see if there's already a profile for this user
        try {
			$profile = $this->getProfileByForeignId($foreign_id);
            common_debug($this->name() . " - Profile for $profile->nickname found.");
		} catch (Exception $e) {	// no profile found, let's create one!
            common_debug($this->name() . " - Adding profile for Facebook user: ".$foreign_id);
            $foreign_user = Facebookclient::saveForeignUser($foreign_id, $this->flink);
            $profile = $this->createForeignUserProfile($foreign_user);
        }

        try {
            FacebookImport::checkAvatar($profile->id, $foreign_id);
        } catch (Exception $e) {
            common_debug('AVATAR GENERATION GONE WRONG:' .$e->getMessage());
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

    static function getAvatarUrl($uid, $type='square') {
        // http://developers.facebook.com/docs/reference/api/#pictures
        $url = 'https://graph.facebook.com/'.urlencode($uid).'/picture?type='.urlencode($type).'&return_ssl_resources=1';
        $headers = get_headers($url, 1);
        common_debug('FACEBOOK avatar location for '.$uid.': '.$headers['Location']);
        return (isset($headers['Location']) ? $headers['Location'] : $url);
    }

    static function checkAvatar($profile_id, $foreign_id, $url='')
    {
        if (empty($url)) {
            $url = FacebookImport::getAvatarUrl($foreign_id);
        }

        $path_parts = pathinfo($url);
        $ext = isset($path_parts['extension']) ? '.'.$path_parts['extension'] : '';
        $img_root = basename($path_parts['basename'], "_q{$ext}");    // q stands for square
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

        /* make this several sizes */
        if (FacebookImport::fetchRemoteUrl($url, Avatar::path($filename))) {
            try {
                Avatar::deleteFromProfile($profile_id);
            } catch (Exception $e) {
                common_debug('FACEBOOK no avatars to delete');
            }

            FacebookImport::newAvatar($profile_id, 'square', $filename);
        }
    }

    static function newAvatar($profile_id, $size, $filename)
    {
        $avatar = new Avatar();
        $avatar->profile_id = $profile_id;

        switch($size) {
        case 'square':
            $avatar->width  = 50;
            $avatar->height = 50;
            $avatar->original = 1; // Let's say this is the original...
            break;
        default:
            throw new Exception('FACEBOOK illegal avatar size: '.$size);
        }

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
        common_debug("FacebookImport::fetchRemoteUrl - Fetching FACEBOOK avatar: $url");
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
