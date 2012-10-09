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

		// saveStatus throws an exception if it fails
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
        $statusUri = $this->makeStatusURI($status->user->screen_name, $statusId);

        try {
            $notice = Foreign_notice_map::get_foreign_notice($statusId, TWITTER_SERVICE);
            return $notice;
        } catch (Exception $e) {
            $notice = new Notice();
        }

        $importNotice = false;    // whether to convert it to a local notice

        $flink = Foreign_link::getByForeignID($status->user->id, TWITTER_SERVICE);
        if (!empty($flink) && ($flink->noticesync & FOREIGN_NOTICE_RECV) == FOREIGN_NOTICE_RECV) {
            $importNotice = true;
            $profile = Profile::staticGet('id', $flink->user_id);
        } else {
            $profile = $this->ensureProfile($status->user);
        }

        if (empty($profile)) {
            common_log(LOG_ERR, $this->name() .
                ' - Problem saving notice. No associated Profile.');
            throw new Exception('No associated profile with foreign user id '.$status->user->id);
        } elseif ($profile->isSilenced()) {
            throw new Exception('Foreign profile is silenced');
        }

        $notice->source     = 'twitter';
        $notice->profile_id = $profile->id;
        $notice->created    = strftime(
            '%Y-%m-%d %H:%M:%S',
            strtotime($status->created_at)
        );

        // If it's a retweet, save it as a repeat!
        if (!empty($status->retweeted_status)) {
            common_log(LOG_INFO, "TWITTER REPEAT Status {$statusId} is a retweet of " . twitter_id($status->retweeted_status) . ".");

			// if we can't save the retweeted status, this will throw an exception
	        $original = $this->saveStatus($status->retweeted_status);

			$author = $original->getProfile();
            // TRANS: Message used to repeat a notice. RT is the abbreviation of 'retweet'.
            // TRANS: %1$s is the repeated user's name, %2$s is the repeated notice.
            $content = sprintf(_m('♲  @%1$s %2$s'),
                               $author->nickname,
                               $original->content);

            if (Notice::contentTooLong($content)) {
                $contentlimit = Notice::maxContent();
                $content = mb_substr($content, 0, $contentlimit - 4) . ' ...';
            }

            $repeat = Notice::saveNew($profile->id,
                                  $original->content,
                                  'twitter',
                                  array('repeat_of' => $original->id,
                                        'uri' => $statusUri,
                                        'is_local' => Notice::GATEWAY));
            try {
				Foreign_notice_map::saveNew($repeat->id, $statusId, TWITTER_SERVICE);
			} catch (Exception $e) {
				$repeat->delete();
				throw $e;
			}

            common_log(LOG_INFO, $this->name() . " saved {$repeat->id} as a repeat of {$original->id}");
            return $repeat;
        }

        $replyToId = twitter_id($status, 'in_reply_to_status_id');
        if (!empty($replyToId)) {
            try {
                $reply = Foreign_notice_map::get_foreign_notice($replyToId, TWITTER_SERVICE);
                $notice->reply_to     = $reply->id;
                $notice->conversation = $reply->conversation;
            } catch (Exception $e) {
                common_log(LOG_INFO, "TWITTER Couldn't find mapped local notice for replyTo status {$replyToId}");
            }
        }

        $notice->content  = html_entity_decode($status->text, ENT_QUOTES, 'UTF-8');
        $notice->rendered = $this->linkify($status);

        if ($importNotice) {
            $noticeOptions = (array)$notice;    // Notice::saveNew should accept a Notice object
            $notice = Notice::saveNew($notice->profile_id, $notice->content, $notice->source, $noticeOptions);
            Foreign_notice_map::saveNew($notice->id, $statusId, TWITTER_SERVICE);
        } else {
            $notice->is_local    = Notice::GATEWAY;
            $notice->uri        = $statusUri;

            if (Event::handle('StartNoticeSave', array(&$notice))) {
    
                $id = $notice->insert();
    
                if (!$id) {
                    common_log_db_error($notice, 'INSERT', __FILE__);
                    common_log(LOG_ERR, $this->name() . ' - Problem saving notice.');
					throw new Exception('Could not save notice');
                } else {
                    Foreign_notice_map::saveNew($notice->id, $statusId, TWITTER_SERVICE);

                    if (empty($notice->conversation)) {
                        $conv = Conversation::create($id);
						$original = clone($notice);
                        $notice->conversation = $id;
						$notice->update($original);
                    } else {
						Conversation::append($notice->conversation, $id);
					}
                }
                Event::handle('EndNoticeSave', array($notice));
            }
        }

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
    static function getProfileByUrl($profileurl)
    {
        $profile = new Profile();
        $profile->profileurl = strtolower($profileurl);
        $profile->limit(1);

        if ($profile->find()) {
            $profile->fetch();
            return $profile;
        }

        return null;
    }

    static function ensureProfile($user)
    {
        // check to see if there's already a profile for this user
        $profileurl = 'https://twitter.com/' . $user->screen_name;
        $profile = self::getProfileByUrl($profileurl);

        if (!empty($profile)) {
            // Check to see if the user's Avatar has changed
            self::checkProfile($user, $profile);
            self::checkAvatar($user, $profile->id);
            return $profile;
        } else {
            common_debug("Adding profile and remote profile for Twitter user: $profileurl.");

            $profile = new Profile();
            $profile->query("BEGIN");

            $profile->nickname = $user->screen_name;
            $profile->fullname = $user->name;
            $profile->homepage = $user->url;
            $profile->bio = $user->description;
            $profile->location = $user->location;
            $profile->profileurl = strtolower($profileurl);
            $profile->created = common_sql_now();

            try {
                $id = $profile->insert();
            } catch(Exception $e) {
                common_log(LOG_WARNING, "Couldn't insert profile: " . $e->getMessage());
                common_log_db_error($profile, 'INSERT', __FILE__);
                $profile->query("ROLLBACK");
                return false;
            }

            $profile->query("COMMIT");

            self::updateAvatar($user, $id);

            return $profile;
        }
    }

    static function checkProfile($user, Profile $profile) {
        $original = clone($profile);
        $checks = array('screen_name'=>'nickname', 'name'=>'fullname', 'url'=>'homepage', 'description'=>'bio', 'location'=>'location');
        foreach ( $checks as $tw=>$sn ) {
            if ( $user->{$tw} != $profile->{$sn} ) {
                $profile->{$sn} = $user->{$tw};
            }
        }
        $profile->update($original);
    }
    static function checkAvatar($user, $profile_id)
    {
        $path_parts = pathinfo($user->profile_image_url);
        $ext = (isset($path_parts['extension']) ? '.'.$path_parts['extension'] : '');    // some lack extension
        $img_root = basename($path_parts['basename'], '_normal'.$ext);    // cut off extension
        $newname = "Twitter_{$user->id}-original-" . $img_root . $ext;

        try {
            $avatar = Avatar::getOriginal($profile_id);
            $oldname = $avatar->filename;
            unset($avatar);
        } catch (Exception $e) {
            $oldname = null;
        }

        if ($newname != $oldname || self::missingAvatarFile($profile_id)) {
            common_debug("Avatar for Twitter user $profile_id has changed. Old: $oldname New: $newname");
            self::updateAvatar($user, $profile_id);
        }
    }

    static function updateAvatar($user, $profile_id) {
        $path_parts = pathinfo($user->profile_image_url);
        $ext = (isset($path_parts['extension']) ? '.'.$path_parts['extension'] : '');    // some lack extension
        $img_root = basename($path_parts['basename'], '_normal' . $ext);
        $url = $path_parts['dirname'] . "/{$img_root}_reasonably_small" . $ext;
        $filename = "Twitter_{$user->id}-original-" . $img_root . $ext;

        try {
            Avatar::deleteFromProfile($profile_id);
        } catch (Exception $e) {
            common_debug('no avatars to delete');
        }

		try {
		    self::newAvatar($profile_id, $url, $filename, self::getMediatype(substr($ext, 1)));
		} catch (Exception $e) {
			if (file_exists(Avatar::path($filename))) {
				unlink(Avatar::path($filename));
			}
		}
    }

    static function missingAvatarFile($profile_id) {
        try {
            $avatar = Avatar::getOriginal($profile_id);
        } catch (Exception $e) {
            return false;
        }
        return !file_exists(Avatar::path($avatar->filename));
    }

    static function getMediatype($ext)
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

    static function newAvatar($profile_id, $url, $filename, $mediatype)
    {
		//throws exception if unable to fetch
		self::fetchRemoteUrl($url, Avatar::path($filename));

        $avatar = new Avatar();
        $avatar->profile_id = $profile_id;
        $avatar->original = true; // we pretend this is the original
        $avatar->mediatype = $mediatype;
        $avatar->filename = $filename;
        $avatar->url = Avatar::url($filename);
        $avatar->width = 128;
        $avatar->height = 128;

        $avatar->created = common_sql_now();

        try {
            $avatar->insert();
        } catch (Exception $e) {
            common_log_db_error($avatar, 'INSERT', __FILE__);
            throw $e;
        }
    }

    /**
     * Fetch a remote file and save to local storage.
     *
     * @param string $url avatar source URL
     * @param string $filename full local save path
     * @return bool true on success, exception on failure
     */

    static function fetchRemoteUrl($url, $filename)
    {
        common_debug("Twitter fetchRemoteUrl - Fetching Twitter url: $url");
        $request = HTTPClient::start();
        $request->setConfig('connect_timeout', 5);
        $request->setConfig('timeout', 10);
        $response = $request->get($url);
        if ($response->isOk()) {
            $ok = file_put_contents($filename, $response->getBody());
            if (!$ok) {
                throw new Exception('Failed fetching to filename '.$filename);
            }
        } else {
            throw new Exception('HTTPClient bad response for '.$url);
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
        return '<a href="'.htmlspecialchars($object->expanded_url).'" class="extlink">'.htmlspecialchars($object->display_url).'</a>';
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
                    $mention = new Mention();
                    $mention->notice_id  = $notice->id;
                    $mention->profile_id = $user->id;
                    $mention->modified   = $notice->created;
                    common_log(LOG_INFO, __METHOD__ . ": saving mention: notice {$notice->id} to profile {$user->id}");
                    $id = $mention->insert();
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
        if (isset($status->entities->media)) : foreach ($status->entities->media as $media) {
file_put_contents('/tmp/tweet-media', print_r($media,true));
            $url = $media->media_url_https;
            $filename = 'Twitter_'.urlencode($media->id_str).'-original-'.urlencode(basename($url));

            $this->fetchRemoteUrl($url, File::path($filename));
//            $attachment = File::processNew(File::url($filename), $notice->id);
            $mediafile = new MediaFile($notice->getProfile(), $filename, MediaFile::getUploadedFileType(File::path($filename)));
            $mediafile->attachToNotice($notice);
    
            common_debug('TWITTER imported File '.$mediafile->fileRecord->id.' by URL to notice '.$notice->id);
        } endif;
    }
}
