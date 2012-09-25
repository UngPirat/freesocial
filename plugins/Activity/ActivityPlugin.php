<?php
/**
 * StatusNet - the distributed open-source microblogging tool
 * Copyright (C) 2010, StatusNet, Inc.
 *
 * Shows social activities in the output feed
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
 * @category  Activity
 * @package   StatusNet
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2010 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html AGPL 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET')) {
    // This check helps protect against security problems;
    // your code file can't be executed directly from the web.
    exit(1);
}

/**
 * Activity plugin main class
 *
 * @category  Activity
 * @package   StatusNet
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2010 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html AGPL 3.0
 * @link      http://status.net/
 */
class ActivityPlugin extends Plugin
{
    const VERSION = '0.1';
    const SOURCE  = 'activity';

    // Flags to switch off certain activity notices
    public $StartFollowUser = true;
    public $StopFollowUser  = false;
    public $JoinGroup = true;
    public $LeaveGroup = false;
    public $StartLike = false;
    public $StopLike = false;

    function onAutoload($cls)
    {
        $dir = dirname(__FILE__);

        switch ($cls)
        {
        case 'JoinListItem':
        case 'LeaveListItem':
        case 'FollowListItem':
        case 'UnfollowListItem':
        case 'SystemListItem':
            include_once $dir . '/'.strtolower($cls).'.php';
            return false;
        default:
            return true;
        }
    }

    function onEndSubscribe($subscriber, $other)
    {
        // Only do this if config is enabled
        if(!$this->StartFollowUser) return true;
        $user = $subscriber->getUser();
        if (!empty($user)) {
            $sub = Subscription::pkeyGet(array('subscriber' => $subscriber->id,
                                               'subscribed' => $other->id));
            // TRANS: Text for "started following" item in activity plugin.
            // TRANS: %1$s is a profile URL, %2$s is a profile name,
            // TRANS: %3$s is a profile URL, %4$s is a profile name.
            $rendered = sprintf(_m('<a href="%1$s">%2$s</a> started following <a href="%3$s">%4$s</a>.'),
                                $subscriber->profileurl,
                                $subscriber->getBestName(),
                                $other->profileurl,
                                $other->getBestName());
            // TRANS: Text for "started following" item in activity plugin.
            // TRANS: %1$s is a profile name, %2$s is a profile URL,
            // TRANS: %3$s is a profile name, %4$s is a profile URL.
            $content  = sprintf(_m('%1$s (%2$s) started following %3$s (%4$s).'),
                                $subscriber->getBestName(),
                                $subscriber->profileurl,
                                $other->getBestName(),
                                $other->profileurl);

            $notice = Notice::saveNew($user->id,
                                      $content,
                                      ActivityPlugin::SOURCE,
                                      array('rendered' => $rendered,
                                            'urls' => array(),
                                            'mentions' => array($other->getUri()),
                                            'verb' => ActivityVerb::FOLLOW,
                                            'object_type' => ActivityObject::PERSON,
                                            'uri' => $sub->uri));
        }
        return true;
    }

    function onEndUnsubscribe($subscriber, $other)
    {
        // Only do this if config is enabled
        if(!$this->StopFollowUser) return true;
        $user = $subscriber->getUser();
        if (!empty($user)) {
            // TRANS: Text for "stopped following" item in activity plugin.
            // TRANS: %1$s is a profile URL, %2$s is a profile name,
            // TRANS: %3$s is a profile URL, %4$s is a profile name.
            $rendered = sprintf(_m('<a href="%1$s">%2$s</a> stopped following <a href="%3$s">%4$s</a>.'),
                                $subscriber->profileurl,
                                $subscriber->getBestName(),
                                $other->profileurl,
                                $other->getBestName());
            // TRANS: Text for "stopped following" item in activity plugin.
            // TRANS: %1$s is a profile name, %2$s is a profile URL,
            // TRANS: %3$s is a profile name, %4$s is a profile URL.
            $content  = sprintf(_m('%1$s (%2$s) stopped following %3$s (%4$s).'),
                                $subscriber->getBestName(),
                                $subscriber->profileurl,
                                $other->getBestName(),
                                $other->profileurl);

            $uri = TagURI::mint('stop-following:%d:%d:%s',
                                $subscriber->id,
                                $other->id,
                                common_date_iso8601(common_sql_now()));

            $notice = Notice::saveNew($user->id,
                                      $content,
                                      ActivityPlugin::SOURCE,
                                      array('rendered' => $rendered,
                                            'urls' => array(),
                                            'mentions' => array($other->getUri()),
                                            'uri' => $uri,
                                            'verb' => ActivityVerb::UNFOLLOW,
                                            'object_type' => ActivityObject::PERSON));
        }
        return true;
    }

    function onEndFavorNotice($profile, $notice)
    {
        //  Only do this if config is enabled
        if(!$this->StartLike) return true;

        $user = $profile->getUser();

        if (!empty($user)) {

            $author = $notice->getProfile();
            $fave   = Fave::pkeyGet(array('user_id' => $user->id,
                                          'notice_id' => $notice->id));

            // TRANS: Text for "liked" item in activity plugin.
            // TRANS: %1$s is a profile URL, %2$s is a profile name,
            // TRANS: %3$s is a notice URL, %4$s is an author name.
            $rendered = sprintf(_m('<a href="%1$s">%2$s</a> liked <a href="%3$s">%4$s\'s update</a>.'),
                                $profile->profileurl,
                                $profile->getBestName(),
                                $notice->bestUrl(),
                                $author->getBestName());
            // TRANS: Text for "liked" item in activity plugin.
            // TRANS: %1$s is a profile name, %2$s is a profile URL,
            // TRANS: %3$s is an author name, %4$s is a notice URL.
            $content  = sprintf(_m('%1$s (%2$s) liked %3$s\'s status (%4$s).'),
                                $profile->getBestName(),
                                $profile->profileurl,
                                $author->getBestName(),
                                $notice->bestUrl());

            $notice = Notice::saveNew($user->id,
                                      $content,
                                      ActivityPlugin::SOURCE,
                                      array('rendered' => $rendered,
                                            'urls' => array(),
                                            'mentions' => array($author->getUri()),
                                            'uri' => $fave->getURI(),
                                            'verb' => ActivityVerb::FAVORITE,
                                            'object_type' => (($notice->verb == ActivityVerb::POST) ?
                                                             ActivityUtils::resolveUri($notice->object_type, true) : ActivityObject::ACTIVITY)));
        }
        return true;
    }

    function onEndDisfavorNotice($profile, $notice)
    {
        // Only do this if config is enabled
        if(!$this->StopLike) return true;
        $user = User::staticGet('id', $profile->id);

        if (!empty($user)) {
            $author = Profile::staticGet('id', $notice->profile_id);
            // TRANS: Text for "stopped liking" item in activity plugin.
            // TRANS: %1$s is a profile URL, %2$s is a profile name,
            // TRANS: %3$s is a notice URL, %4$s is an author name.
            $rendered = sprintf(_m('<a href="%1$s">%2$s</a> stopped liking <a href="%3$s">%4$s\'s update</a>.'),
                                $profile->profileurl,
                                $profile->getBestName(),
                                $notice->bestUrl(),
                                $author->getBestName());
            // TRANS: Text for "stopped liking" item in activity plugin.
            // TRANS: %1$s is a profile name, %2$s is a profile URL,
            // TRANS: %3$s is an author name, %4$s is a notice URL.
            $content  = sprintf(_m('%1$s (%2$s) stopped liking %3$s\'s status (%4$s).'),
                                $profile->getBestName(),
                                $profile->profileurl,
                                $author->getBestName(),
                                $notice->bestUrl());

            $uri = TagURI::mint('unlike:%d:%d:%s',
                                $profile->id,
                                $notice->id,
                                common_date_iso8601(common_sql_now()));

            $notice = Notice::saveNew($user->id,
                                      $content,
                                      ActivityPlugin::SOURCE,
                                      array('rendered' => $rendered,
                                            'urls' => array(),
                                            'mentions' => array($author->getUri()),
                                            'uri' => $uri,
                                            'verb' => ActivityVerb::UNFAVORITE,
                                            'object_type' => (($notice->verb == ActivityVerb::POST) ?
                                                             ActivityUtils::resolveUri($notice->object_type, true) : ActivityObject::ACTIVITY)));
        }
        return true;
    }

    function onEndJoinGroup($group, $profile)
    {
        // Only do this if config is enabled
        if(!$this->JoinGroup) return true;

        $user = $profile->getUser();

        if (empty($user)) {
            return true;
        }

        // TRANS: Text for "joined group" item in activity plugin.
        // TRANS: %1$s is a profile URL, %2$s is a profile name,
        // TRANS: %3$s is a group URL, %4$s is a group name.
        $rendered = sprintf(_m('<a href="%1$s">%2$s</a> joined the group <a href="%3$s">%4$s</a>.'),
                            $profile->profileurl,
                            $profile->getBestName(),
                            $group->homeUrl(),
                            $group->getBestName());
        // TRANS: Text for "joined group" item in activity plugin.
        // TRANS: %1$s is a profile name, %2$s is a profile URL,
        // TRANS: %3$s is a group name, %4$s is a group URL.
        $content  = sprintf(_m('%1$s (%2$s) joined the group %3$s (%4$s).'),
                            $profile->getBestName(),
                            $profile->profileurl,
                            $group->getBestName(),
                            $group->homeUrl());

        $mem = Group_member::pkeyGet(array('group_id' => $group->id,
                                           'profile_id' => $profile->id));

        $notice = Notice::saveNew($user->id,
                                  $content,
                                  ActivityPlugin::SOURCE,
                                  array('rendered' => $rendered,
                                        'urls' => array(),
                                        'groups' => array($group->id),
                                        'uri' => $mem->getURI(),
                                        'verb' => ActivityVerb::JOIN,
                                        'object_type' => ActivityObject::GROUP));
        return true;
    }

    function onEndLeaveGroup($group, $profile)
    {
        // Only do this if config is enabled
        if(!$this->LeaveGroup) return true;

        $user = $profile->getUser();

        if (empty($user)) {
            return true;
        }

        // TRANS: Text for "left group" item in activity plugin.
        // TRANS: %1$s is a profile URL, %2$s is a profile name,
        // TRANS: %3$s is a group URL, %4$s is a group name.
        $rendered = sprintf(_m('<a href="%1$s">%2$s</a> left the group <a href="%3$s">%4$s</a>.'),
                            $profile->profileurl,
                            $profile->getBestName(),
                            $group->homeUrl(),
                            $group->getBestName());
        // TRANS: Text for "left group" item in activity plugin.
        // TRANS: %1$s is a profile name, %2$s is a profile URL,
        // TRANS: %3$s is a group name, %4$s is a group URL.
        $content  = sprintf(_m('%1$s (%2$s) left the group %3$s (%4$s).'),
                            $profile->getBestName(),
                            $profile->profileurl,
                            $group->getBestName(),
                            $group->homeUrl());

        $uri = TagURI::mint('leave:%d:%d:%s',
                            $user->id,
                            $group->id,
                            common_date_iso8601(common_sql_now()));

        $notice = Notice::saveNew($user->id,
                                  $content,
                                  ActivityPlugin::SOURCE,
                                  array('rendered' => $rendered,
                                        'urls' => array(),
                                        'groups' => array($group->id),
                                        'uri' => $uri,
                                        'verb' => ActivityVerb::LEAVE,
                                        'object_type' => ActivityObject::GROUP));
        return true;
    }

    function onStartShowNoticeItem($nli)
    {
        $notice = $nli->notice;

        $adapter = null;

        switch (ActivityUtils::resolveUri($notice->verb, true)) {
        case ActivityVerb::FAVORITE:
        case ActivityVerb::UNFAVORITE:
            $adapter = new SystemListItem($nli);
            break;
        case ActivityVerb::JOIN:
            $adapter = new JoinListItem($nli);
            break;
        case ActivityVerb::LEAVE:
            $adapter = new JoinListItem($nli);
            break;
        case ActivityVerb::FOLLOW:
            $adapter = new FollowListItem($nli);
            break;
        case ActivityVerb::UNFOLLOW:
            $adapter = new UnfollowListItem($nli);
            break;
        }

        if (!empty($adapter)) {
            $adapter->showNotice();
            $adapter->showNoticeAttachments();
            $adapter->showNoticeInfo();
            $adapter->showNoticeOptions();
            return false;
        }

        return true;
    }

    function onEndNoticeAsActivity($notice, &$activity)
    {
        switch (ActivityUtils::resolveUri($notice->verb, true)) {
        case ActivityVerb::FAVORITE:
            $fave = Fave::staticGet('uri', $notice->uri);
            if (!empty($fave)) {
                $notice = Notice::staticGet('id', $fave->notice_id);
                if (!empty($notice)) {
                    $cur = common_current_user();
                    $target = $notice->asActivity($cur);
                    if (ActivityUtils::compareObjectTypes($target->verb, ActivityVerb::POST)) {
                        // "I like the thing you posted"
                        $activity->objects = $target->objects;
                    } else {
                        // "I like that you did whatever you did"
                        $activity->objects = array($target);
                    }
                }
            }
            break;
        case ActivityVerb::UNFAVORITE:
            // FIXME: do something here
            break;
        case ActivityVerb::JOIN:
            $mem = Group_member::staticGet('uri', $notice->uri);
            if (!empty($mem)) {
                $group = $mem->getGroup();
                $activity->objects = array(ActivityObject::fromGroup($group));
            }
            break;
        case ActivityVerb::LEAVE:
            // FIXME: ????
            break;
        case ActivityVerb::FOLLOW:
            $sub = Subscription::staticGet('uri', $notice->uri);
            if (!empty($sub)) {
                $profile = Profile::staticGet('id', $sub->subscribed);
                if (!empty($profile)) {
                    $activity->objects = array(ActivityObject::fromProfile($profile));
                }
            }
            break;
        case ActivityVerb::UNFOLLOW:
            // FIXME: ????
            break;
        }

        return true;
    }

    function onPluginVersion(&$versions)
    {
        $versions[] = array('name' => 'Activity',
                            'version' => self::VERSION,
                            'author' => 'Evan Prodromou',
                            'homepage' => 'http://status.net/wiki/Plugin:Activity',
                            'rawdescription' =>
                            // TRANS: Plugin description.
                            _m('Emits notices when social activities happen.'));
        return true;
    }
}
