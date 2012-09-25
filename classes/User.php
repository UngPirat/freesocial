<?php
/*
 * StatusNet - the distributed open-source microblogging tool
 * Copyright (C) 2008, 2009, StatusNet, Inc.
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
 */

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

/**
 * Table Definition for user
 */

require_once INSTALLDIR.'/classes/Memcached_DataObject.php';
require_once 'Validate.php';

class User extends Managed_DataObject
{
    const SUBSCRIBE_POLICY_OPEN = 0;
    const SUBSCRIBE_POLICY_MODERATE = 1;

    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'user';                            // table name
    public $id;                              // int(4)  primary_key not_null
    public $nickname;                        // varchar(64)  unique_key
    public $password;                        // varchar(255)
    public $email;                           // varchar(255)  unique_key
    public $incomingemail;                   // varchar(255)  unique_key
    public $emailnotifysub;                  // tinyint(1)   default_1
    public $emailnotifyfav;                  // tinyint(1)   default_1
    public $emailnotifymsg;                  // tinyint(1)   default_1
    public $emailnotifyattn;                 // tinyint(1)   default_1
    public $emailmicroid;                    // tinyint(1)   default_1
    public $language;                        // varchar(50)
    public $timezone;                        // varchar(50)
    public $emailpost;                       // tinyint(1)   default_1
    public $uri;                             // varchar(255)  unique_key
    public $autosubscribe;                   // tinyint(1)
    public $subscribe_policy;                // tinyint(1)
    public $urlshorteningservice;            // varchar(50)   default_ur1.ca
    public $inboxed;                         // tinyint(1)
    public $private_stream;                  // tinyint(1)   default_0
    public $created;                         // datetime()   not_null
    public $modified;                        // timestamp()   not_null default_CURRENT_TIMESTAMP

    /* Static get */
    function staticGet($k,$v=NULL) { return Memcached_DataObject::staticGet('User',$k,$v); }

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE

    public static function schemaDef()
    {
        return array(
            'description' => 'local users',
            'fields' => array(
                'id' => array('type' => 'int', 'not null' => true, 'description' => 'foreign key to profile table'),
                'nickname' => array('type' => 'varchar', 'length' => 64, 'description' => 'nickname or username, duped in profile'),
                'password' => array('type' => 'varchar', 'length' => 255, 'description' => 'salted password, can be null for OpenID users'),
                'email' => array('type' => 'varchar', 'length' => 255, 'description' => 'email address for password recovery etc.'),
                'incomingemail' => array('type' => 'varchar', 'length' => 255, 'description' => 'email address for post-by-email'),
                'emailnotifysub' => array('type' => 'int', 'size' => 'tiny', 'default' => 1, 'description' => 'Notify by email of subscriptions'),
                'emailnotifyfav' => array('type' => 'int', 'size' => 'tiny', 'default' => 1, 'description' => 'Notify by email of favorites'),
                'emailnotifymsg' => array('type' => 'int', 'size' => 'tiny', 'default' => 1, 'description' => 'Notify by email of direct messages'),
                'emailnotifyattn' => array('type' => 'int', 'size' => 'tiny', 'default' => 1, 'description' => 'Notify by email of @-mentions'),
                'emailmicroid' => array('type' => 'int', 'size' => 'tiny', 'default' => 1, 'description' => 'whether to publish email microid'),
                'language' => array('type' => 'varchar', 'length' => 50, 'description' => 'preferred language'),
                'timezone' => array('type' => 'varchar', 'length' => 50, 'description' => 'timezone'),
                'emailpost' => array('type' => 'int', 'size' => 'tiny', 'default' => 1, 'description' => 'Post by email'),
                'uri' => array('type' => 'varchar', 'length' => 255, 'description' => 'universally unique identifier, usually a tag URI'),
                'autosubscribe' => array('type' => 'int', 'size' => 'tiny', 'default' => 0, 'description' => 'automatically subscribe to users who subscribe to us'),
                'subscribe_policy' => array('type' => 'int', 'size' => 'tiny', 'default' => 0, 'description' => '0 = anybody can subscribe; 1 = require approval'),
                'urlshorteningservice' => array('type' => 'varchar', 'length' => 50, 'default' => 'internal', 'description' => 'service to use for auto-shortening URLs'),
                'inboxed' => array('type' => 'int', 'size' => 'tiny', 'default' => 0, 'description' => 'has an inbox been created for this user?'),
                'private_stream' => array('type' => 'int', 'size' => 'tiny', 'default' => 0, 'description' => 'whether to limit all notices to followers only'),

                'created' => array('type' => 'datetime', 'not null' => true, 'description' => 'date this record was created'),
                'modified' => array('type' => 'timestamp', 'not null' => true, 'description' => 'date this record was modified'),
            ),
            'primary key' => array('id'),
            'unique keys' => array(
                'user_nickname_key' => array('nickname'),
                'user_email_key' => array('email'),
                'user_incomingemail_key' => array('incomingemail'),
                'user_uri_key' => array('uri'),
            ),
            'foreign keys' => array(
                'user_id_fkey' => array('profile', array('id' => 'id')),
            ),
        );
    }

    protected $_profile = -1;

    /**
     * @return Profile
     */
    function getProfile()
    {
        if (is_int($this->_profile) && $this->_profile == -1) { // invalid but distinct from null
            $this->_profile = Profile::staticGet('id', $this->id);
            if (empty($this->_profile)) {
                throw new UserNoProfileException($this);
            }
        }

        return $this->_profile;
    }

    function isSubscribed($other)
    {
        $profile = $this->getProfile();
        return $profile->isSubscribed($other);
    }

    function hasPendingSubscription($other)
    {
        $profile = $this->getProfile();
        return $profile->hasPendingSubscription($other);
    }

    // 'update' won't write key columns, so we have to do it ourselves.

    function updateKeys(&$orig)
    {
        $this->_connect();
        $parts = array();
        foreach (array('nickname', 'email', 'incomingemail', 'language', 'timezone') as $k) {
            if (strcmp($this->$k, $orig->$k) != 0) {
                $parts[] = $k . ' = ' . $this->_quote($this->$k);
            }
        }
        if (count($parts) == 0) {
            // No changes
            return true;
        }
        $toupdate = implode(', ', $parts);

        $table = common_database_tablename($this->tableName());
        $qry = 'UPDATE ' . $table . ' SET ' . $toupdate .
          ' WHERE id = ' . $this->id;
        $orig->decache();
        $result = $this->query($qry);
        if ($result) {
            $this->encache();
        }
        return $result;
    }

    /**
     * Check whether the given nickname is potentially usable, or if it's
     * excluded by any blacklists on this system.
     *
     * WARNING: INPUT IS NOT VALIDATED OR NORMALIZED. NON-NORMALIZED INPUT
     * OR INVALID INPUT MAY LEAD TO FALSE RESULTS.
     *
     * @param string $nickname
     * @return boolean true if clear, false if blacklisted
     */
    static function allowed_nickname($nickname)
    {
        // XXX: should already be validated for size, content, etc.
        $blacklist = common_config('nickname', 'blacklist');

        //all directory and file names should be blacklisted
        $d = dir(INSTALLDIR);
        while (false !== ($entry = $d->read())) {
            $blacklist[]=$entry;
        }
        $d->close();

        //all top level names in the router should be blacklisted
        $router = Router::get();
        foreach(array_keys($router->m->getPaths()) as $path){
            if(preg_match('/^\/(.*?)[\/\?]/',$path,$matches)){
                $blacklist[]=$matches[1];
            }
        }
        return !in_array($nickname, $blacklist);
    }

    /**
     * Get the most recent notice posted by this user, if any.
     *
     * @return mixed Notice or null
     */
    function getCurrentNotice()
    {
        $profile = $this->getProfile();
        return $profile->getCurrentNotice();
    }

    /**
     * @deprecated use Subscription::start($sub, $other);
     */
    function subscribeTo($other)
    {
        return Subscription::start($this->getProfile(), $other);
    }

    function hasBlocked($other)
    {
        $profile = $this->getProfile();
        return $profile->hasBlocked($other);
    }

    /**
     * Register a new user account and profile and set up default subscriptions.
     * If a new-user welcome message is configured, this will be sent.
     *
     * @param array $fields associative array of optional properties
     *              string 'bio'
     *              string 'email'
     *              bool 'email_confirmed' pass true to mark email as pre-confirmed
     *              string 'fullname'
     *              string 'homepage'
     *              string 'location' informal string description of geolocation
     *              float 'lat' decimal latitude for geolocation
     *              float 'lon' decimal longitude for geolocation
     *              int 'location_id' geoname identifier
     *              int 'location_ns' geoname namespace to interpret location_id
     *              string 'nickname' REQUIRED
     *              string 'password' (may be missing for eg OpenID registrations)
     *              string 'code' invite code
     *              ?string 'uri' permalink to notice; defaults to local notice URL
     * @return mixed User object or false on failure
     */
    static function register($fields) {

        // MAGICALLY put fields into current scope

        extract($fields);

        $profile = new Profile();

        if(!empty($email))
        {
            $email = common_canonical_email($email);
        }

        $nickname = common_canonical_nickname($nickname);
        $profile->nickname = $nickname;
        $profile->type = Profile::USER;
        if(! User::allowed_nickname($nickname)){
            common_log(LOG_WARNING, sprintf("Attempted to register a nickname that is not allowed: %s", $profile->nickname),
                       __FILE__);
            return false;
        }
        $profile->profileurl = common_profile_url($nickname);

        if (!empty($fullname)) {
            $profile->fullname = $fullname;
        }
        if (!empty($homepage)) {
            $profile->homepage = $homepage;
        }
        if (!empty($bio)) {
            $profile->bio = $bio;
        }
        if (!empty($location)) {
            $profile->location = $location;

            $loc = Location::fromName($location);

            if (!empty($loc)) {
                $profile->lat         = $loc->lat;
                $profile->lon         = $loc->lon;
                $profile->location_id = $loc->location_id;
                $profile->location_ns = $loc->location_ns;
            }
        }

        $profile->created = common_sql_now();

        $user = new User();

        $user->nickname = $nickname;

        $invite = null;

        // Users who respond to invite email have proven their ownership of that address

        if (!empty($code)) {
            $invite = Invitation::staticGet($code);
            if ($invite && $invite->address && $invite->address_type == 'email' && $invite->address == $email) {
                $user->email = $invite->address;
            }
        }

        if(isset($email_confirmed) && $email_confirmed) {
            $user->email = $email;
        }

        // This flag is ignored but still set to 1

        $user->inboxed = 1;

        // Set default-on options here, otherwise they'll be disabled
        // initially for sites using caching, since the initial encache
        // doesn't know about the defaults in the database.
        $user->emailnotifysub = 1;
        $user->emailnotifyfav = 1;
        $user->emailnotifymsg = 1;
        $user->emailnotifyattn = 1;
        $user->emailmicroid = 1;
        $user->emailpost = 1;
        $user->jabbermicroid = 1;

        $user->created = common_sql_now();

        if (Event::handle('StartUserRegister', array(&$user, &$profile))) {

            $profile->query('BEGIN');

            $id = $profile->insert();

            if (empty($id)) {
                common_log_db_error($profile, 'INSERT', __FILE__);
                return false;
            }

            $user->id = $id;

            if (!empty($uri)) {
                $user->uri = $uri;
            } else {
                $user->uri = common_user_uri($user);
            }

            if (!empty($password)) { // may not have a password for OpenID users
                $user->password = common_munge_password($password, $id);
            }

            $result = $user->insert();

            if (!$result) {
                common_log_db_error($user, 'INSERT', __FILE__);
                return false;
            }

            // Everyone gets an inbox

            $inbox = new Inbox();

            $inbox->user_id = $user->id;
            $inbox->notice_ids = '';

            $result = $inbox->insert();

            if (!$result) {
                common_log_db_error($inbox, 'INSERT', __FILE__);
                return false;
            }

            // Everyone is subscribed to themself

            $subscription = new Subscription();
            $subscription->subscriber = $user->id;
            $subscription->subscribed = $user->id;
            $subscription->created = $user->created;

            $result = $subscription->insert();

            if (!$result) {
                common_log_db_error($subscription, 'INSERT', __FILE__);
                return false;
            }

            // Mark that this invite was converted

            if (!empty($invite)) {
                $invite->convert($user);
            }

            if (!empty($email) && !$user->email) {

                $confirm = new Confirm_address();
                $confirm->code = common_confirmation_code(128);
                $confirm->user_id = $user->id;
                $confirm->address = $email;
                $confirm->address_type = 'email';

                $result = $confirm->insert();

                if (!$result) {
                    common_log_db_error($confirm, 'INSERT', __FILE__);
                    return false;
                }
            }

            if (!empty($code) && $user->email) {
                $user->emailChanged();
            }

            // Default system subscription

            $defnick = common_config('newuser', 'default');

            if (!empty($defnick)) {
                $defuser = User::staticGet('nickname', $defnick);
                if (empty($defuser)) {
                    common_log(LOG_WARNING, sprintf("Default user %s does not exist.", $defnick),
                               __FILE__);
                } else {
                    Subscription::start($user, $defuser);
                }
            }

            $profile->query('COMMIT');

            if (!empty($email) && !$user->email) {
                mail_confirm_address($user, $confirm->code, $profile->nickname, $email);
            }

            // Welcome message

            $welcome = common_config('newuser', 'welcome');

            if (!empty($welcome)) {
                $welcomeuser = User::staticGet('nickname', $welcome);
                if (empty($welcomeuser)) {
                    common_log(LOG_WARNING, sprintf("Welcome user %s does not exist.", $defnick),
                               __FILE__);
                } else {
                    $notice = Notice::saveNew($welcomeuser->id,
                                              // TRANS: Notice given on user registration.
                                              // TRANS: %1$s is the sitename, $2$s is the registering user's nickname.
                                              sprintf(_('Welcome to %1$s, @%2$s!'),
                                                      common_config('site', 'name'),
                                                      $user->nickname),
                                              'system');
                }
            }

            Event::handle('EndUserRegister', array(&$profile, &$user));
        }

        return $user;
    }

    // Things we do when the email changes
    function emailChanged()
    {

        $invites = new Invitation();
        $invites->address = $this->email;
        $invites->address_type = 'email';

        if ($invites->find()) {
            while ($invites->fetch()) {
                $other = User::staticGet($invites->user_id);
                subs_subscribe_to($other, $this);
            }
        }
    }

    function hasFave($notice)
    {
        $profile = $this->getProfile();
        return $profile->hasFave($notice);
    }

    function mutuallySubscribed($other)
    {
        $profile = $this->getProfile();
        return $profile->mutuallySubscribed($other);
    }

    function mutuallySubscribedUsers()
    {
        // 3-way join; probably should get cached
        $UT = common_config('db','type')=='pgsql'?'"user"':'user';
        $qry = "SELECT $UT.* " .
          "FROM subscription sub1 JOIN $UT ON sub1.subscribed = $UT.id " .
          "JOIN subscription sub2 ON $UT.id = sub2.subscriber " .
          'WHERE sub1.subscriber = %d and sub2.subscribed = %d ' .
          "ORDER BY $UT.nickname";
        $user = new User();
        $user->query(sprintf($qry, $this->id, $this->id));

        return $user;
    }

    function getMentions($offset=0, $limit=NOTICES_PER_PAGE, $since_id=0, $before_id=0)
    {
        return Mention::stream($this->id, $offset, $limit, $since_id, $before_id);
    }

    function getTaggedNotices($tag, $offset=0, $limit=NOTICES_PER_PAGE, $since_id=0, $before_id=0) {
        $profile = $this->getProfile();
        return $profile->getTaggedNotices($tag, $offset, $limit, $since_id, $before_id);
    }

    function getNotices($offset=0, $limit=NOTICES_PER_PAGE, $since_id=0, $before_id=0)
    {
        $profile = $this->getProfile();
        return $profile->getNotices($offset, $limit, $since_id, $before_id);
    }

    function favoriteNotices($own=false, $offset=0, $limit=NOTICES_PER_PAGE, $since_id=0, $max_id=0)
    {
        return Fave::stream($this->id, $offset, $limit, $own, $since_id, $max_id);
    }

    function noticeInbox($offset=0, $limit=NOTICES_PER_PAGE, $since_id=0, $before_id=0)
    {
        $stream = new InboxNoticeStream($this);
        return $stream->getNotices($offset, $limit, $since_id, $before_id);
    }

    // DEPRECATED, use noticeInbox()

    function noticesWithFriends($offset=0, $limit=NOTICES_PER_PAGE, $since_id=0, $before_id=0)
    {
        return $this->noticeInbox($offset, $limit, $since_id, $before_id);
    }

    // DEPRECATED, use noticeInbox()

    function noticesWithFriendsThreaded($offset=0, $limit=NOTICES_PER_PAGE, $since_id=0, $before_id=0)
    {
        return $this->noticeInbox($offset, $limit, $since_id, $before_id);
    }

    // DEPRECATED, use noticeInbox()

    function noticeInboxThreaded($offset=0, $limit=NOTICES_PER_PAGE, $since_id=0, $before_id=0)
    {
        return $this->noticeInbox($offset, $limit, $since_id, $before_id);
    }

    // DEPRECATED, use noticeInbox()

    function friendsTimeline($offset=0, $limit=NOTICES_PER_PAGE, $since_id=0, $before_id=0)
    {
        return $this->noticeInbox($offset, $limit, $since_id, $before_id);
    }

    // DEPRECATED, use noticeInbox()

    function ownFriendsTimeline($offset=0, $limit=NOTICES_PER_PAGE, $since_id=0, $before_id=0)
    {
        $this->noticeInbox($offset, $limit, $since_id, $before_id);
    }

    function blowFavesCache()
    {
        $profile = $this->getProfile();
        $profile->blowFavesCache();
    }

    function getSelfTags()
    {
        return Profile_tag::getTagsArray($this->id, $this->id, $this->id);
    }

    function setSelfTags($newtags, $privacy)
    {
        return Profile_tag::setTags($this->id, $this->id, $newtags, $privacy);
    }

    function block($other)
    {
        // Add a new block record

        // no blocking (and thus unsubbing from) yourself

        if ($this->id == $other->id) {
            common_log(LOG_WARNING,
                sprintf(
                    "Profile ID %d (%s) tried to block themself.",
                    $this->id,
                    $this->nickname
                )
            );
            return false;
        }

        $block = new Profile_block();

        // Begin a transaction

        $block->query('BEGIN');

        $block->blocker = $this->id;
        $block->blocked = $other->id;

        $result = $block->insert();

        if (!$result) {
            common_log_db_error($block, 'INSERT', __FILE__);
            return false;
        }

        $self = $this->getProfile();
        if (Subscription::exists($other, $self)) {
            Subscription::cancel($other, $self);
        }
        if (Subscription::exists($self, $other)) {
            Subscription::cancel($self, $other);
        }

        $block->query('COMMIT');

        return true;
    }

    function unblock($other)
    {
        // Get the block record

        $block = Profile_block::get($this->id, $other->id);

        if (!$block) {
            return false;
        }

        $result = $block->delete();

        if (!$result) {
            common_log_db_error($block, 'DELETE', __FILE__);
            return false;
        }

        return true;
    }

    function isMember($group)
    {
        $profile = $this->getProfile();
        return $profile->isMember($group);
    }

    function isAdmin($group)
    {
        $profile = $this->getProfile();
        return $profile->isAdmin($group);
    }

    function getGroups($offset=0, $limit=null)
    {
        $profile = $this->getProfile();
        return $profile->getGroups($offset, $limit);
    }

    /**
     * Request to join the given group.
     * May throw exceptions on failure.
     *
     * @param User_group $group
     * @return Group_member
     */
    function joinGroup(User_group $group)
    {
        $profile = $this->getProfile();
        return $profile->joinGroup($group);
    }

    /**
     * Leave a group that this user is a member of.
     *
     * @param User_group $group
     */
    function leaveGroup(User_group $group)
    {
        $profile = $this->getProfile();
        return $profile->leaveGroup($group);
    }

    function getSubscriptions($offset=0, $limit=null)
    {
        $profile = $this->getProfile();
        return $profile->getSubscriptions($offset, $limit);
    }

    function getSubscribers($offset=0, $limit=null)
    {
        $profile = $this->getProfile();
        return $profile->getSubscribers($offset, $limit);
    }

    function getTaggedSubscribers($tag, $offset=0, $limit=null)
    {
        $qry =
          'SELECT profile.* ' .
          'FROM profile JOIN subscription ' .
          'ON profile.id = subscription.subscriber ' .
          'JOIN profile_tag ON (profile_tag.tagged = subscription.subscriber ' .
          'AND profile_tag.tagger = subscription.subscribed) ' .
          'WHERE subscription.subscribed = %d ' .
          "AND profile_tag.tag = '%s' " .
          'AND subscription.subscribed != subscription.subscriber ' .
          'ORDER BY subscription.created DESC ';

        if ($offset) {
            $qry .= ' LIMIT ' . $limit . ' OFFSET ' . $offset;
        }

        $profile = new Profile();

        $cnt = $profile->query(sprintf($qry, $this->id, $tag));

        return $profile;
    }

    function getTaggedSubscriptions($tag, $offset=0, $limit=null)
    {
        $qry =
          'SELECT profile.* ' .
          'FROM profile JOIN subscription ' .
          'ON profile.id = subscription.subscribed ' .
          'JOIN profile_tag on (profile_tag.tagged = subscription.subscribed ' .
          'AND profile_tag.tagger = subscription.subscriber) ' .
          'WHERE subscription.subscriber = %d ' .
          "AND profile_tag.tag = '%s' " .
          'AND subscription.subscribed != subscription.subscriber ' .
          'ORDER BY subscription.created DESC ';

        $qry .= ' LIMIT ' . $limit . ' OFFSET ' . $offset;

        $profile = new Profile();

        $profile->query(sprintf($qry, $this->id, $tag));

        return $profile;
    }

    function hasRight($right)
    {
        $profile = $this->getProfile();
        return $profile->hasRight($right);
    }

    function delete()
    {
        try {
            $profile = $this->getProfile();
            $profile->delete();
        } catch (UserNoProfileException $unp) {
            common_log(LOG_INFO, "User {$this->nickname} has no profile; continuing deletion.");
        }

        $related = array('Fave',
                         'Confirm_address',
                         'Remember_me',
                         'Foreign_link',
                         'Invitation',
                         );

        Event::handle('UserDeleteRelated', array($this, &$related));

        foreach ($related as $cls) {
            $inst = new $cls();
            $inst->user_id = $this->id;
            $inst->delete();
        }

        $this->_deleteTags();
        $this->_deleteBlocks();

        parent::delete();
    }

    function _deleteTags()
    {
        $tag = new Profile_tag();
        $tag->tagger = $this->id;
        $tag->delete();
    }

    function _deleteBlocks()
    {
        $block = new Profile_block();
        $block->blocker = $this->id;
        $block->delete();
        // XXX delete group block? Reset blocker?
    }

    function hasRole($name)
    {
        $profile = $this->getProfile();
        return $profile->hasRole($name);
    }

    function grantRole($name)
    {
        $profile = $this->getProfile();
        return $profile->grantRole($name);
    }

    function revokeRole($name)
    {
        $profile = $this->getProfile();
        return $profile->revokeRole($name);
    }

    function isSandboxed()
    {
        $profile = $this->getProfile();
        return $profile->isSandboxed();
    }

    function isSilenced()
    {
        $profile = $this->getProfile();
        return $profile->isSilenced();
    }

    function repeatedByMe($offset=0, $limit=20, $since_id=null, $max_id=null)
    {
        $stream = new RepeatedByMeNoticeStream($this);
        return $stream->getNotices($offset, $limit, $since_id, $max_id);
    }


    function repeatsOfMe($offset=0, $limit=20, $since_id=null, $max_id=null)
    {
        $stream = new RepeatsOfMeNoticeStream($this);

        return $stream->getNotices($offset, $limit, $since_id, $max_id);
    }


    function repeatedToMe($offset=0, $limit=20, $since_id=null, $max_id=null)
    {
        // TRANS: Exception thrown when trying view "repeated to me".
        throw new Exception(_('Not implemented since inbox change.'));
    }

    function shareLocation()
    {
        $cfg = common_config('location', 'share');

        if ($cfg == 'always') {
            return true;
        } else if ($cfg == 'never') {
            return false;
        } else { // user
            $share = true;

            $prefs = User_location_prefs::staticGet('user_id', $this->id);

            if (empty($prefs)) {
                $share = common_config('location', 'sharedefault');
            } else {
                $share = $prefs->share_location;
                $prefs->free();
            }

            return $share;
        }
    }

    static function siteOwner()
    {
        $owner = self::cacheGet('user:site_owner');

        if ($owner === false) { // cache miss

            $pr = new Profile_role();

            $pr->role = Profile_role::OWNER;

            $pr->orderBy('created');

            $pr->limit(1);

            if ($pr->find(true)) {
                $owner = User::staticGet('id', $pr->profile_id);
            } else {
                $owner = null;
            }

            self::cacheSet('user:site_owner', $owner);
        }

        return $owner;
    }

    /**
     * Pull the primary site account to use in single-user mode.
     * If a valid user nickname is listed in 'singleuser':'nickname'
     * in the config, this will be used; otherwise the site owner
     * account is taken by default.
     *
     * @return User
     * @throws ServerException if no valid single user account is present
     * @throws ServerException if called when not in single-user mode
     */
    static function singleUser()
    {
        if (common_config('singleuser', 'enabled')) {

            $user = null;

            $nickname = common_config('singleuser', 'nickname');

            if (!empty($nickname)) {
                $user = User::staticGet('nickname', $nickname);
            }

            // if there was no nickname or no user by that nickname,
            // try the site owner.

            if (empty($user)) {
                $user = User::siteOwner();
            }

            if (!empty($user)) {
                return $user;
            } else {
                // TRANS: Server exception.
                throw new ServerException(_('No single user defined for single-user mode.'));
            }
        } else {
            // TRANS: Server exception.
            throw new ServerException(_('Single-user mode code called when not enabled.'));
        }
    }

    /**
     * This is kind of a hack for using external setup code that's trying to
     * build single-user sites.
     *
     * Will still return a username if the config singleuser/nickname is set
     * even if the account doesn't exist, which normally indicates that the
     * site is horribly misconfigured.
     *
     * At the moment, we need to let it through so that router setup can
     * complete, otherwise we won't be able to create the account.
     *
     * This will be easier when we can more easily create the account and
     * *then* switch the site to 1user mode without jumping through hoops.
     *
     * @return string
     * @throws ServerException if no valid single user account is present
     * @throws ServerException if called when not in single-user mode
     */
    static function singleUserNickname()
    {
        try {
            $user = User::singleUser();
            return $user->nickname;
        } catch (Exception $e) {
            if (common_config('singleuser', 'enabled') && common_config('singleuser', 'nickname')) {
                common_log(LOG_WARNING, "Warning: code attempting to pull single-user nickname when the account does not exist. If this is not setup time, this is probably a bug.");
                return common_config('singleuser', 'nickname');
            }
            throw $e;
        }
    }

    /**
     * Find and shorten links in the given text using this user's URL shortening
     * settings.
     *
     * By default, links will be left untouched if the text is shorter than the
     * configured maximum notice length. Pass true for the $always parameter
     * to force all links to be shortened regardless.
     *
     * Side effects: may save file and file_redirection records for referenced URLs.
     *
     * @param string $text
     * @param boolean $always
     * @return string
     */
    public function shortenLinks($text, $always=false)
    {
        return common_shorten_links($text, $always, $this);
    }

    /*
     * Get a list of OAuth client applications that have access to this
     * user's account.
     */
    function getConnectedApps($offset = 0, $limit = null)
    {
        $qry =
          'SELECT u.* ' .
          'FROM oauth_application_user u, oauth_application a ' .
          'WHERE u.profile_id = %d ' .
          'AND a.id = u.application_id ' .
          'AND u.access_type > 0 ' .
          'ORDER BY u.created DESC ';

        if ($offset > 0) {
            if (common_config('db','type') == 'pgsql') {
                $qry .= ' LIMIT ' . $limit . ' OFFSET ' . $offset;
            } else {
                $qry .= ' LIMIT ' . $offset . ', ' . $limit;
            }
        }

        $apps = new Oauth_application_user();

        $cnt = $apps->query(sprintf($qry, $this->id));

        return $apps;
    }

    /**
     * Magic function called at serialize() time.
     *
     * We use this to drop a couple process-specific references
     * from DB_DataObject which can cause trouble in future
     * processes.
     *
     * @return array of variable names to include in serialization.
     */

    function __sleep()
    {
        $vars = parent::__sleep();
        $skip = array('_profile');
        return array_diff($vars, $skip);
    }

    static function recoverPassword($nore)
    {
        $user = User::staticGet('email', common_canonical_email($nore));

        if (!$user) {
            try {
                $user = User::staticGet('nickname', common_canonical_nickname($nore));
            } catch (NicknameException $e) {
                // invalid
            }
        }

        // See if it's an unconfirmed email address

        if (!$user) {
            // Warning: it may actually be legit to have multiple folks
            // who have claimed, but not yet confirmed, the same address.
            // We'll only send to the first one that comes up.
            $confirm_email = new Confirm_address();
            $confirm_email->address = common_canonical_email($nore);
            $confirm_email->address_type = 'email';
            $confirm_email->find();
            if ($confirm_email->fetch()) {
                $user = User::staticGet($confirm_email->user_id);
            } else {
                $confirm_email = null;
            }
        } else {
            $confirm_email = null;
        }

        if (!$user) {
            // TRANS: Information on password recovery form if no known username or e-mail address was specified.
            throw new ClientException(_('No user with that email address or username.'));
            return;
        }

        // Try to get an unconfirmed email address if they used a user name

        if (!$user->email && !$confirm_email) {
            $confirm_email = new Confirm_address();
            $confirm_email->user_id = $user->id;
            $confirm_email->address_type = 'email';
            $confirm_email->find();
            if (!$confirm_email->fetch()) {
                $confirm_email = null;
            }
        }

        if (!$user->email && !$confirm_email) {
            // TRANS: Client error displayed on password recovery form if a user does not have a registered e-mail address.
            throw new ClientException(_('No registered email address for that user.'));
            return;
        }

        // Success! We have a valid user and a confirmed or unconfirmed email address

        $confirm = new Confirm_address();
        $confirm->code = common_confirmation_code(128);
        $confirm->address_type = 'recover';
        $confirm->user_id = $user->id;
        $confirm->address = (!empty($user->email)) ? $user->email : $confirm_email->address;

        if (!$confirm->insert()) {
            common_log_db_error($confirm, 'INSERT', __FILE__);
            // TRANS: Server error displayed if e-mail address confirmation fails in the database on the password recovery form.
            throw new ServerException(_('Error saving address confirmation.'));
            return;
        }

         // @todo FIXME: needs i18n.
        $body = "Hey, $user->nickname.";
        $body .= "\n\n";
        $body .= 'Someone just asked for a new password ' .
                 'for this account on ' . common_config('site', 'name') . '.';
        $body .= "\n\n";
        $body .= 'If it was you, and you want to confirm, use the URL below:';
        $body .= "\n\n";
        $body .= "\t".common_local_url('recoverpassword',
                                   array('code' => $confirm->code));
        $body .= "\n\n";
        $body .= 'If not, just ignore this message.';
        $body .= "\n\n";
        $body .= 'Thanks for your time, ';
        $body .= "\n";
        $body .= common_config('site', 'name');
        $body .= "\n";

        $headers = _mail_prepare_headers('recoverpassword', $user->nickname, $user->nickname);
        // TRANS: Subject for password recovery e-mail.
        mail_to_user($user, _('Password recovery requested'), $body, $headers, $confirm->address);
    }
}
