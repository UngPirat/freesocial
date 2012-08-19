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
    protected $updates = 0;
    protected $scope = 0;
    protected $groups = array();
    protected $facebook = null;
    protected $flink = null;

    function __construct(Foreign_link $flink=null) {
        $this->updates = 0;
        $this->groups = array();
        $this->facebook = Facebookclient::getFacebook();
        $this->flink = $flink;
    }

    public function getGroups() {
        return $this->groups;
    }

    public function getUpdates() {
        return $this->updates;
    }

    function name()
    {
        return get_class($this);
    }

    function getAccessToken() {
        return !empty($this->flink->credentials)
                ? $this->flink->credentials
                : $this->facebook->getAccessToken();
    }

    function apiLoop($path, $callback, $args=array(), $max_loops=0) {
        $loops = 0;
        do {
            if (!isset($args['access_token']) || empty($args['access_token'])) {
                $args['access_token'] = $this->getAccessToken();
            }
            try {
                $result = $this->facebook->api($path, 'get', $args);
            } catch (Exception $e) {
                return 0;
            }

            if (empty($result['data'])) {
                common_debug('FBDBG Data empty in loop '.$loops.' for path '.$path);
                break;
            }
        
            $n = 0;    // number of new, imported posts
            foreach (array_reverse($result['data']) as $entry) {
                try {
                    $n += call_user_func($callback, $entry);
                } catch (Exception $e) {
                }
            }

            if (isset($result['paging']['next'])) {
                $next = parse_url($result['paging']['next'], PHP_URL_QUERY);
                parse_str($next, $args);    // overwrite with data that makes us go back in time
            }
            $loops++;
        } while ($n > 0 && ($max_loops == 0 || $loops <= $max_loops));
    }

    public function importUpdates($field, $args=array()) {
        if ($this->flink === null) {
            throw new Exception('No foreign link');
        }
        $this->scope = Notice::PUBLIC_SCOPE;

        // Iterate the loop backwards until >= 7 pages
        $this->apiLoop('/me/'.urlencode($field), array($this, 'importThread'), $args, 7);

        // Okay, record the time we synced with Facebook for posterity
        $original = clone($this->flink);
        $this->flink->last_noticesync = common_sql_now();
        $this->flink->update($original);
        return $this->getUpdates();    // number of imported posts
    }

    public function importPage($object)
    {
        if ($this->flink === null) {
            throw new Exception('No foreign link');
        }
        $this->scope = Notice::PUBLIC_SCOPE;	// always public?

        common_debug('FBDBG: getting foreign page '.$object->foreign_id.' because it is local group '.$object->group_id);
        // get this group's feed
        $this->apiLoop(sprintf('/me/feed', $object->foreign_id), array($this, 'importThread'));
    }

    public function importGroup($group)
    {
        $this->scope = Notice::PUBLIC_SCOPE;	// make it GROUP/FOLLOWER/whatever for private groups!

        common_debug('FBDBG: getting foreign group '.$group->foreign_id.' because it is local group '.$group->group_id);
        // get this group's feed
        $this->apiLoop(sprintf('/%s/feed', $group->foreign_id), array($this, 'importThread'));
    }

    protected function importThread($update)
    {
        $oldUpdateN = $this->getUpdates();

        $this->scope = (isset($update['privacy']['value'])
                    ? FacebookImport::getPrivacyScope($update['privacy']['value'])
                    : $this->scope);

        // Add more as we understand them
        switch ($update['type']) {
        case 'status':
        case 'link':
        case 'photo':
            if (isset($update['message'])) :
                // Hacktastic: filter out stuff coming from this StatusNet
                $source = mb_strtolower(common_config('integration', 'source'));
                if ( !isset($update['application']) || !preg_match("/$source/", mb_strtolower($update['application']['name'])) ) {
                    try {
                        $notice = $this->saveUpdate($update, $this->scope);
                    } catch (Exception $e) {
                        return $this->getUpdates();
                    }
                }
                if ($update['comments']['count']>0) {	// TODO: Check if notice has data?
                    if (isset($update['comments']['data'])) {
                        foreach ($update['comments']['data'] as $comment) :
                            try {
                                $notice = $this->saveUpdate($comment, $this->scope);
                            } catch (Exception $e) {
                                common_debug('FBDBG discarding comment '.$comment['id'].': '.$e->getMessage());
                            }
                        endforeach;
					} else {
						common_debug('FACEBOOK comment thread empty for '.$update['id'].'. TODO: fetch from API!');
					}
                } else {
                    common_debug('FACEBOOK found '.$update['comments']['count'].' comments for '.$update['id']);
                }
            //} elseif (isset($update['story'])) {
            else :
                common_debug('FACEBOOK update '.$update['id'].' with scope '.$this->scope.' does not have a message set');
            endif;
            break;
        default:
            common_debug('FACEBOOK unknown update type: '.$update['type'].' for '.$update['id']);
        }

        return $this->getUpdates() - $oldUpdateN;    // new posts this round
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
            return $notice;
        } catch (Exception $e) {
            $notice = new Notice();
        }

        $doNoticeImport = false;    // whether to convert it to a local notice

        common_debug('FACEBOOK importing ('.$update['id'].'): '.$update['message']);
        $flink = Foreign_link::getByForeignID($update['from']['id'], FACEBOOK_SERVICE);
        if (!empty($flink) && ($flink->noticesync & FOREIGN_NOTICE_RECV) == FOREIGN_NOTICE_RECV) {
            $doNoticeImport = true;
            $profile = Profile::staticGet('id', $flink->user_id);
            $flink->free();
            unset($flink);
        } elseif (!empty($flink)) {
            // this is actually odd behaviour, but reasonable as users must be able to decide not to be imported
            throw new Exception('Foreign link disallows importing');
        } else {
            $profile = $this->ensureProfile($update['from']['id']);
        }

        if (empty($profile)) {
            common_log(LOG_ERR, $this->name() .
                ' - Problem saving notice. No associated Profile.');
            throw new Exception('Profile not available');
        } elseif ($profile->isSilenced()) {
            throw new Exception('Profile is silenced');
        }

        $notice->scope      = $scope;
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
            $notice->scope        = $reply->scope;
            $notice->conversation = $reply->conversation;
        } catch (Exception $e) {
            // either this is the original post or the reply-to post has not been imported/mapped
        }

        $message = $update['message'];
        $notice->content  = html_entity_decode($message, ENT_QUOTES, 'UTF-8');

        if ($doNoticeImport) {
            $notice->is_local   = ($scope == Notice::PUBLIC_SCOPE ? Notice::LOCAL_PUBLIC : Notice::LOCAL_NONPUBLIC);
            unset($notice->uri);//unset($noticeOptions['uri']);
        } else {
            $notice->is_local   = Notice::GATEWAY;
            $notice->uri        = $notice->url;
            $notice->rendered = common_render_content($message, $notice);
        }

        $noticeOptions = (array)$notice;    // Notice::saveNew should accept a Notice object :(
        switch ($update['type']) {
        case 'link':
            $notice = Bookmark::saveNew($profile, $message, $update['link'], '', $update['description'], $noticeOptions);
            break;
        default:
            $notice = Notice::saveNew($notice->profile_id, $notice->content, $notice->source, $noticeOptions);
        }

        Foreign_notice_map::saveNew($notice->id, $update['id'], FACEBOOK_SERVICE);

        $this->saveUpdateMentions($notice, $update);

        try {
            $this->saveUpdateAttachments($notice, $update, $profile);
        } catch (Exception $e) {
            common_debug('FBDBG file download failed for notice '.$notice->id);
        }

        if (!$doNoticeImport && isset($this->flink)) {	// otherwise foreign users' posts won't get put in the home feed
            common_debug('FBDBG putting '.$notice->id.' into inbox for '.$this->flink->user_id);
            Inbox::insertNotice($this->flink->user_id, $notice->id);
        }

        $notice->blowOnInsert();

        $this->updates++;
        return $notice;
    }

    protected function saveUpdateAttachments($notice, $update, $profile) {
        if (!isset($update['type']) || !common_config('attachments', 'process_links')) {
            return false;
        }
        switch ($update['type']) {
        case 'link':
            // continue to photo if there's a picture involved
            if (!isset($update['picture'])) {
                break;
            }
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
                unset($mentions[$foreign_id]);	// it's a group so don't process as user below

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

                if (!empty($fetch)) {
                    $fetch = Profile::staticGet($fetch->user_id);	// flink found
                } else {
                    $fetch = $this->ensureProfile($foreign_id);
                }
                $destinations[] = $fetch->profileurl;	// profile found/created
                $fetch->free();
            } catch (Exception $e) {
                continue;
            }
        }
        common_debug('FBDBG: replying '.$notice->id.' to profiles '.implode(' ', $destinations));
        $notice->saveKnownReplies($destinations);

        return true;
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
            $profile->fetch();
				// update to new profile link
			common_debug('FACEBOOK updating profile for foreign_id='.$fuser->id.' to profileurl: '.$fuser->uri.' for new user nick '.$fuser->nickname);
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
        $foreign_user = $fsrv->addForeignUser($foreign_id, $this->flink->credentials);
        try {
			$profile = $this->getProfileByForeignUser($foreign_user);
		} catch (Exception $e) {	// no profile found, let's create one!
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
        $url = 'http://graph.facebook.com/'.urlencode($uid).'/picture?type='.urlencode($type).'&return_ssl_resources=1';
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
