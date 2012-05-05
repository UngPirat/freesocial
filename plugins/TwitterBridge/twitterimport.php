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

require_once INSTALLDIR . '/plugins/TwitterBridge/twitter.php';

/**
 * Encapsulation of the Twitter status -> notice incoming bridge import.
 * Is used by both the polling twitterstatusfetcher.php daemon, and the
 * in-progress streaming import.
 *
 * @category Plugin
 * @package  StatusNet
 * @author   Zach Copley <zach@status.net>
 * @author   Julien C <chaumond@gmail.com>
 * @author   Brion Vibber <brion@status.net>
 * @author   Mikael Nordfeldth <mmn@hethane.se>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 * @link     http://twitter.com/
 */
class TwitterImport
{
    public function importStatus($status)
    {
        // Hacktastic: filter out stuff coming from this StatusNet
        $source = mb_strtolower(common_config('integration', 'source'));

        if (preg_match("/$source/", mb_strtolower($status->source))) {
            common_debug($this->name() . ' - Skipping import of status ' .
                         twitter_id($status) . ' with source ' . $source);
            return null;
        }

        // Don't save it if the user is protected
        // FIXME: save it but treat it as private
        if ($status->user->protected) {
            return null;
        }

        $notice = $this->saveStatus($status);

        return $notice;
    }

    function name()
    {
        return get_class($this);
    }

    function saveStatus($status)
    {
        $statusId = twitter_id($status);
		$is_local = Notice::GATEWAY;

		$flink = Foreign_link::getByForeignID($status->user->id, TWITTER_SERVICE);
		if ( !empty($flink) &&
			($flink->noticesync & Foreign_notice_map::FOREIGN_NOTICE_RECV_IMPORT) == Foreign_notice_map::FOREIGN_NOTICE_RECV_IMPORT ) {
				common_debug('TWITTER user has activated IMPORT, meaning I assign tweet for '.$status->user->screen_name.' to '.$flink->user_id);
				$profile = User::staticGet('id', $flink->user_id);
				$is_local = Notice::REMOTE;
		} else {
	        $profile = $this->ensureProfile($status->user);
		}

        if (empty($profile)) {
            common_log(LOG_ERR, $this->name() .
                ' - Problem saving notice. No associated Profile.');
            return null;
        }


		$statusUri = $this->makeStatusURI($status->user->screen_name, $statusId);
        // check to see if we've already imported the status
		try {
			if ($notice = $this->checkDupe($profile, $statusUri)
				    || $notice = Foreign_notice_map::get_foreign_notice($statusId, TWITTER_SERVICE)) {
			    common_debug('TWITTER DUPE CHECK notice='.print_r($notice,true));
			    return $notice;
			}
		} catch (Exception $e) {
		}

        // If it's a retweet, save it as a repeat!
        if (!empty($status->retweeted_status)) {
            common_log(LOG_INFO, "TWITTER REPEAT Status {$statusId} is a retweet of " . twitter_id($status->retweeted_status) . ".");
            $original = $this->saveStatus($status->retweeted_status);
            if (empty($original)) {
                return null;
            } else {
				if (!is_object($original)) {
					common_debug('TWITTER BUG PROFILE for statusId '.$statusId.' original: '.print_r($original, true));
					return null;
				}
                $author = $original->getProfile();
                // TRANS: Message used to repeat a notice. RT is the abbreviation of 'retweet'.
                // TRANS: %1$s is the repeated user's name, %2$s is the repeated notice.
                $content = sprintf(_m('RT @%1$s %2$s'),
                                   $author->nickname,
                                   $original->content);

                if (Notice::contentTooLong($content)) {
                    $contentlimit = Notice::maxContent();
                    $content = mb_substr($content, 0, $contentlimit - 4) . ' ...';
                }

				try {
					$repeat = Notice::saveNew($profile->id,
                                      $original->content,
                                      'twitter',
                                      array('repeat_of' => $original->id,
                                            'uri' => $statusUri,
                                            'is_local' => $is_local));
				} catch (Exception $e) {
	                common_log(LOG_DEBUG, $this->name() . " could not save {$repeat->id} as a repeat of {$original->id}");
					return null;
				}
	            common_log(LOG_INFO, $this->name() . " saved {$repeat->id} as a repeat of {$original->id}");
                Foreign_notice_map::saveNew($repeat->id, $statusId, TWITTER_SERVICE);
                return $repeat;
            }
        }

        $notice = new Notice();

        $notice->profile_id = $profile->id;
        $notice->uri        = $statusUri;
        $notice->created    = strftime(
            '%Y-%m-%d %H:%M:%S',
            strtotime($status->created_at)
        );

        $notice->source     = 'twitter';

        $notice->reply_to   = null;

        $replyTo = twitter_id($status, 'in_reply_to_status_id');
        if (!empty($replyTo)) {
            common_log(LOG_INFO, "Status {$statusId} is a reply to status {$replyTo}");
            try {
				$reply = Foreign_notice_map::get_foreign_notice($replyTo, TWITTER_SERVICE);
                common_log(LOG_INFO, "Found local replyTo notice {$reply->id} for replyTo status {$replyTo}. Setting conversation {$reply->conversation}");
                $notice->reply_to     = $reply->id;
                $notice->conversation = $reply->conversation;
			} catch (Exception $e) {
                common_log(LOG_INFO, "FNMAP Couldn't find mapped local notice for replyTo status {$replyTo}");
            }
        }

        if (empty($notice->conversation)) {
            $conv = Conversation::create();
            $notice->conversation = $conv->id;
            common_log(LOG_INFO, "No known conversation for status {$statusId} so making a new one {$conv->id}.");
        }

        $notice->is_local = $is_local;

        $notice->content  = html_entity_decode($status->text, ENT_QUOTES, 'UTF-8');
        $notice->rendered = $this->linkify($status);

        if (Event::handle('StartNoticeSave', array(&$notice))) {

            $noticeId = $notice->insert();

            if (!$noticeId) {
                common_log_db_error($notice, 'INSERT', __FILE__);
                common_log(LOG_ERR, $this->name() .
                    ' - Problem saving notice.');
            }

            Event::handle('EndNoticeSave', array($notice));
        }

        Foreign_notice_map::saveNew($notice->id, $statusId, TWITTER_SERVICE);

        $this->saveStatusMentions($notice, $status);
        $this->saveStatusAttachments($notice, $status);

        $notice->blowOnInsert();

        return $notice;
    }

    /**
     * Make an URI for a status.
     *
     * @param object $status status object
     *
     * @return string URI
     */
    function makeStatusURI($username, $id)
    {
        return 'https://twitter.com/#!/'
          . $username
          . '/status/'
          . $id;
    }


    /**
     * Look up a Profile by profileurl field.  Profile::staticGet() was
     * not working consistently.
     *
     * @param string $nickname   local nickname of the Twitter user
     * @param string $profileurl the profile url
     *
     * @return mixed value the first Profile with that url, or null
     */
    function getProfileByUrl($nickname, $profileurl)
    {
        $profile = new Profile();
        $profile->nickname = $nickname;
        $profile->profileurl = $profileurl;
        $profile->limit(1);

        if ($profile->find()) {
            $profile->fetch();
            return $profile;
        }

        return null;
    }

    /**
     * Check to see if this Twitter status has already been imported
     *
     * @param Profile $profile   Twitter user's local profile
     * @param string  $statusUri URI of the status on Twitter
     *
     * @return mixed value a matching Notice or null
     */
    function checkDupe($profile, $statusUri)
    {
        $notice = new Notice();
        $notice->uri = $statusUri;
        $notice->profile_id = $profile->id;
        $notice->limit(1);

        if ($notice->find()) {
            $notice->fetch();
	        common_log(LOG_DEBUG, "FNMAP got a dupe of {$statusUri}");
            return $notice;
        }

        return null;
    }

    function ensureProfile($user)
    {
        // check to see if there's already a profile for this user
        $profileurl = 'https://twitter.com/' . $user->screen_name;
        $profile = $this->getProfileByUrl($user->screen_name, $profileurl);

        if (!empty($profile)) {
            common_debug($this->name() . " - Profile for $profile->nickname found.");

            // Check to see if the user's Avatar has changed
            $this->checkAvatar($user, $profile);
            return $profile;

        } else {
            common_debug($this->name() . " - Adding profile and remote profile for Twitter user: $profileurl.");

            $profile = new Profile();
            $profile->query("BEGIN");

            $profile->nickname = $user->screen_name;
            $profile->fullname = $user->name;
            $profile->homepage = $user->url;
            $profile->bio = $user->description;
            $profile->location = $user->location;
            $profile->profileurl = $profileurl;
            $profile->created = common_sql_now();

            try {
                $id = $profile->insert();
            } catch(Exception $e) {
                common_log(LOG_WARNING, $this->name() . ' Couldn\'t insert profile - ' . $e->getMessage());
            }

            if (empty($id)) {
                common_log_db_error($profile, 'INSERT', __FILE__);
                $profile->query("ROLLBACK");
                return false;
            }

            // check for remote profile

            $remote_pro = Remote_profile::staticGet('uri', $profileurl);

            if (empty($remote_pro)) {
                $remote_pro = new Remote_profile();

                $remote_pro->id = $id;
                $remote_pro->uri = $profileurl;
                $remote_pro->created = common_sql_now();

                try {
                    $rid = $remote_pro->insert();
                } catch (Exception $e) {
                    common_log(LOG_WARNING, $this->name() . ' Couldn\'t save remote profile - ' . $e->getMessage());
                }

                if (empty($rid)) {
                    common_log_db_error($profile, 'INSERT', __FILE__);
                    $profile->query("ROLLBACK");
                    return false;
                }
            }

            $profile->query("COMMIT");

            $this->saveAvatars($user, $id);

            return $profile;
        }
    }

    function checkAvatar($twitter_user, $profile)
    {
        global $config;

        $path_parts = pathinfo($twitter_user->profile_image_url);

        $newname = 'Twitter_' . $twitter_user->id . '_' . $path_parts['basename'];

        $avatar = $profile->getAvatar(48);
      	$oldname = ($avatar === null ? $avatar : $avatar->filename);

        if ($newname != $oldname) {
            common_debug($this->name() . ' - Avatar for Twitter user ' .
                         "$profile->nickname has changed.");
            common_debug($this->name() . " - old: $oldname new: $newname");

            $this->updateAvatars($twitter_user, $profile);
        }

        if ($this->missingAvatarFile($profile)) {
			common_debug('TWITTER AVATAR newname ('.$newname.') vs. oldname ('.$oldname.')');
            common_debug($this->name() . ' - Twitter user ' .
                         $profile->nickname .
                         ' is missing one or more local avatars.');
            common_debug($this->name() ." - old: $oldname new: $newname");

            $this->updateAvatars($twitter_user, $profile);
        }
    }

    function updateAvatars($twitter_user, $profile) {

        global $config;

        $path_parts = pathinfo($twitter_user->profile_image_url);

        common_debug('Twitter image_url pathinfo basename: '.$path_parts['basename'].' -> '.substr($path_parts['basename'], 0, -11));
        $img_root = substr($path_parts['basename'], 0, -11);
        $ext = $path_parts['extension'];
        $mediatype = $this->getMediatype($ext);

        foreach (array('mini', 'normal', 'bigger') as $size) {
            $url = $path_parts['dirname'] . '/' .
                $img_root . '_' . $size . ".$ext";
            $filename = 'Twitter_' . $twitter_user->id . '_' .
                $img_root . "_$size.$ext";

            $this->updateAvatar($profile->id, $size, $mediatype, $filename);
            $this->fetchAvatar($url, $filename);
        }
    }

    function missingAvatarFile($profile) {
        foreach (array(24, 48, 73) as $size) {
			$avatar = $profile->getAvatar($size);
			if ($avatar === null) {
				return false;
			}
            $filename = $avatar->filename;
            $avatarpath = Avatar::path($filename);
            if (file_exists($avatarpath) == FALSE) {
                return true;
            }
        }
        return false;
    }

    function getMediatype($ext)
    {
        $mediatype = null;

        switch (strtolower($ext)) {
        case 'jpeg':
        case 'jpg':
            $mediatype = 'image/jpeg';
            break;
        case 'gif':
            $mediatype = 'image/gif';
            break;
        default:
            $mediatype = 'image/png';
        }

        return $mediatype;
    }

    function saveAvatars($user, $id)
    {
        global $config;

        $path_parts = pathinfo($user->profile_image_url);
        $ext = $path_parts['extension'];
        $end = strlen('_normal' . $ext);
        $img_root = substr($path_parts['basename'], 0, -($end+1));
        $mediatype = $this->getMediatype($ext);

        foreach (array('mini', 'normal', 'bigger') as $size) {
            $url = $path_parts['dirname'] . '/' .
                $img_root . '_' . $size . ".$ext";
            $filename = 'Twitter_' . $user->id . '_' .
                $img_root . "_$size.$ext";

            if ($this->fetchAvatar($url, $filename)) {
                $this->newAvatar($id, $size, $mediatype, $filename);
            } else {
                common_log(LOG_WARNING, 'TWITTER AVATAR (' . $id . ')'.
                           " - Problem fetching Avatar: $url");
            }
        }
    }

    function updateAvatar($profile_id, $size, $mediatype, $filename) {

        common_debug($this->name() . " - Updating avatar: $size");

        $profile = Profile::staticGet($profile_id);

        if (empty($profile)) {
            common_debug($this->name() . " - Couldn't get profile: $profile_id!");
            return;
        }

        $sizes = array('mini' => 24, 'normal' => 48, 'bigger' => 73);
        $avatar = $profile->getAvatar($sizes[$size]);

        // Delete the avatar, if present
        if ($avatar) {
            $avatar->delete();
        }

        $this->newAvatar($profile->id, $size, $mediatype, $filename);
    }

    function newAvatar($profile_id, $size, $mediatype, $filename)
    {
        global $config;

        $avatar = new Avatar();
        $avatar->profile_id = $profile_id;

        switch($size) {
        case 'mini':
            $avatar->width  = 24;
            $avatar->height = 24;
            break;
        case 'normal':
            $avatar->width  = 48;
            $avatar->height = 48;
            break;
        default:
            // Note: Twitter's big avatars are a different size than
            // StatusNet's (StatusNet's = 96)
            $avatar->width  = 73;
            $avatar->height = 73;
        }

        $avatar->original = 0; // we don't have the original
        $avatar->mediatype = $mediatype;
        $avatar->filename = $filename;
        $avatar->url = Avatar::url($filename);

        $avatar->created = common_sql_now();

        try {
            $id = $avatar->insert();
        } catch (Exception $e) {
            common_log(LOG_WARNING, $this->name() . ' Couldn\'t insert avatar - ' . $e->getMessage());
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
    function fetchAvatar($url, $filename)
    {
        common_debug($this->name() . " - Fetching Twitter avatar: $url");
	try {
        $request = HTTPClient::start();
        $response = $request->get($url);
        if ($response->isOk()) {
            $avatarfile = Avatar::path($filename);
            $ok = file_put_contents($avatarfile, $response->getBody());
            if (!$ok) {
                common_log(LOG_WARNING, $this->name() .
                           " - Couldn't open file $filename");
                return false;
            }
        } else {
            return false;
        }
	} catch (Exception $e) {
		common_debug('TWITTER AVATAR failed due to: '.$e->getMessage());
		return false;
	}
        return true;
    }

    const URL = 1;
    const HASHTAG = 2;
    const MENTION = 3;

    function linkify($status)
    {
        $text = $status->text;

        if (empty($status->entities)) {
            $statusId = twitter_id($status);
            common_log(LOG_WARNING, "No entities data for {$statusId}; trying to fake up links ourselves.");
            $text = common_replace_urls_callback($text, 'common_linkify');
            $text = preg_replace('/(^|\&quot\;|\'|\(|\[|\{|\s+)#([\pL\pN_\-\.]{1,64})/e', "'\\1#'.TwitterStatusFetcher::tagLink('\\2')", $text);
            $text = preg_replace('/(^|\s+)@([a-z0-9A-Z_]{1,64})/e', "'\\1@'.TwitterStatusFetcher::atLink('\\2')", $text);
            return $text;
        }

        // Move all the entities into order so we can
        // replace them and escape surrounding plaintext
        // in order

        $toReplace = array();

        if (!empty($status->entities->urls)) {
            foreach ($status->entities->urls as $url) {
                $toReplace[$url->indices[0]] = array(self::URL, $url);
            }
        }

        if (!empty($status->entities->hashtags)) {
            foreach ($status->entities->hashtags as $hashtag) {
                $toReplace[$hashtag->indices[0]] = array(self::HASHTAG, $hashtag);
            }
        }

        if (!empty($status->entities->user_mentions)) {
            foreach ($status->entities->user_mentions as $mention) {
                $toReplace[$mention->indices[0]] = array(self::MENTION, $mention);
            }
        }

        // sort in forward order by key

        ksort($toReplace);

        $result = '';
        $cursor = 0;

        foreach ($toReplace as $part) {
            list($type, $object) = $part;
            $start = $object->indices[0];
            $end = $object->indices[1];
            if ($cursor < $start) {
                // Copy in the preceding plaintext
                $result .= $this->twitEscape(mb_substr($text, $cursor, $start - $cursor));
                $cursor = $start;
            }
            $orig = $this->twitEscape(mb_substr($text, $start, $end - $start));
            switch($type) {
            case self::URL:
                $linkText = $this->makeUrlLink($object, $orig);
                break;
            case self::HASHTAG:
                $linkText = $this->makeHashtagLink($object, $orig);
                break;
            case self::MENTION:
                $linkText = $this->makeMentionLink($object, $orig);
                break;
            default:
                $linkText = $orig;
                continue;
            }
            $result .= $linkText;
            $cursor = $end;
        }
        $last = $this->twitEscape(mb_substr($text, $cursor));
        $result .= $last;

        return $result;
    }

    function twitEscape($str)
    {
        // Twitter seems to preemptive turn < and > into &lt; and &gt;
        // but doesn't for &, so while you may have some magic protection
        // against XSS by not bothing to escape manually, you still get
        // invalid XHTML. Thanks!
        //
        // Looks like their web interface pretty much sends anything
        // through intact, so.... to do equivalent, decode all entities
        // and then re-encode the special ones.
        return htmlspecialchars(html_entity_decode($str, ENT_COMPAT, 'UTF-8'));
    }

    function makeUrlLink($object, $orig)
    {
        return "<a href='{$object->url}' class='extlink'>{$orig}</a>";
    }

    function makeHashtagLink($object, $orig)
    {
        return "#" . self::tagLink($object->text, substr($orig, 1));
    }

    function makeMentionLink($object, $orig)
    {
        return "@".self::atLink($object->screen_name, $object->name, substr($orig, 1));
    }

    static function tagLink($tag, $orig)
    {
        return "<a href='https://search.twitter.com/search?q=%23{$tag}' class='hashtag'>{$orig}</a>";
    }

    static function atLink($screenName, $fullName, $orig)
    {
        if (!empty($fullName)) {
            return "<a href='https://twitter.com/#!/{$screenName}' title='{$fullName}'>{$orig}</a>";
        } else {
            return "<a href='https://twitter.com/#!/{$screenName}'>{$orig}</a>";
        }
    }

    function saveStatusMentions($notice, $status)
    {
        $mentions = array();

        if (empty($status->entities) || empty($status->entities->user_mentions)) {
            return;
        }

        foreach ($status->entities->user_mentions as $mention) {
            $flink = Foreign_link::getByForeignID($mention->id, TWITTER_SERVICE);
            if (!empty($flink)) {
                $user = User::staticGet('id', $flink->user_id);
                if (!empty($user)) {
                    $reply = new Reply();
                    $reply->notice_id  = $notice->id;
                    $reply->profile_id = $user->id;
                    $reply->modified   = $notice->created;
                    common_log(LOG_INFO, __METHOD__ . ": saving reply: notice {$notice->id} to profile {$user->id}");
                    $id = $reply->insert();
                }
            }
        }
    }

    /**
     * Record URL links from the notice. Needed to get thumbnail records
     * for referenced photo and video posts, etc.
     *
     * @param Notice $notice
     * @param object $status
     */
    function saveStatusAttachments($notice, $status)
    {
        if (common_config('attachments', 'process_links')) {
            if (!empty($status->entities) && !empty($status->entities->urls)) {
                foreach ($status->entities->urls as $url) {
                    File::processNew($url->url, $notice->id);
                }
            }
        }
    }
}
