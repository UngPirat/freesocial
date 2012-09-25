<?php
/*
 * StatusNet - the distributed open-source microblogging tool
 * Copyright (C) 2010, StatusNet, Inc.
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

if (!defined('STATUSNET')) {
    exit(1);
}

/**
 * @package OStatusPlugin
 * @author James Walker <james@status.net>
 */
class UsersalmonAction extends SalmonAction
{
    function prepare($args)
    {
        parent::prepare($args);

        $id = $this->trimmed('id');

        if (!$id) {
            // TRANS: Client error displayed trying to perform an action without providing an ID.
            $this->clientError(_m('No ID.'));
        }

        $this->user = User::staticGet('id', $id);

        if (empty($this->user)) {
            // TRANS: Client error displayed when referring to a non-existing user.
            $this->clientError(_m('No such user.'));
        }

        $this->target = $this->user;

        return true;
    }

    /**
     * We've gotten a post event on the Salmon backchannel, probably a reply.
     *
     * @todo validate if we need to handle this post, then call into
     * ostatus_profile's general incoming-post handling.
     */
    function handlePost()
    {
        common_log(LOG_INFO, "Received post of '{$this->activity->objects[0]->id}' from '{$this->activity->actor->id}'");

        // @fixme: process all activity objects?
        if (!ActivityUtils::compareObjectTypes($this->activity->objects[0]->type, array (
                    ActivityObject::ARTICLE,
                    ActivityObject::BLOGENTRY,
                    ActivityObject::NOTE,
                    ActivityObject::STATUS,
                    ActivityObject::COMMENT,
                ))) {
            // TRANS: Client exception thrown when an undefied activity is performed.
            throw new ClientException(_m('Cannot handle that kind of post.'));
        }

        // Notice must either be a) in reply to a notice by this user
        // or b) to the attention of this user
        // or c) in reply to a notice to the attention of this user

        $context = $this->activity->context;

        if (!empty($context->replyToID)) {
            $notice = Notice::staticGet('uri', $context->replyToID);
            if (empty($notice)) {
                // TRANS: Client exception.
                throw new ClientException(_m('In reply to unknown notice.'));
            }
            if ($notice->profile_id != $this->user->id &&
                !in_array($this->user->id, $notice->getMentions())) {
                // TRANS: Client exception.
                throw new ClientException(_m('In reply to a notice not by this user and not mentioning this user.'));
            }
        } else if (!empty($context->attention)) {
            if (!in_array($this->user->uri, $context->attention) &&
                !in_array(common_profile_url($this->user->nickname), $context->attention)) {
                common_log(LOG_ERR, "{$this->user->uri} not in attention list (".implode(',', $context->attention).")");
                // TRANS: Client exception.
                throw new ClientException(_m('To the attention of user(s), not including this one.'));
            }
        } else {
            // TRANS: Client exception.
            throw new ClientException(_m('Not to anyone in reply to anything.'));
        }

        $existing = Notice::staticGet('uri', $this->activity->objects[0]->id);

        if (!empty($existing)) {
            common_log(LOG_ERR, "Not saving notice '{$existing->uri}'; already exists.");
            return;
        }

        $this->saveNotice();
    }

    /**
     * We've gotten a follow/subscribe notification from a remote user.
     * Save a subscription relationship for them.
     */
    function handleFollow()
    {
        $oprofile = $this->ensureProfile();
        if ($oprofile) {
            common_log(LOG_INFO, "Setting up subscription from remote {$oprofile->uri} to local {$this->user->nickname}");
            Subscription::start($oprofile->localProfile(),
                                $this->user->getProfile());
        } else {
            common_log(LOG_INFO, "Can't set up subscription from remote; missing profile.");
        }
    }

    /**
     * We've gotten an unfollow/unsubscribe notification from a remote user.
     * Check if we have a subscription relationship for them and kill it.
     *
     * @fixme probably catch exceptions on fail?
     */
    function handleUnfollow()
    {
        $oprofile = $this->ensureProfile();
        if ($oprofile) {
            common_log(LOG_INFO, "Canceling subscription from remote {$oprofile->uri} to local {$this->user->nickname}");
            Subscription::cancel($oprofile->localProfile(), $this->user->getProfile());
        } else {
            common_log(LOG_ERR, "Can't cancel subscription from remote, didn't find the profile");
        }
    }

    /**
     * Remote user likes one of our posts.
     * Confirm the post is ours, and save a local favorite event.
     */

    function handleFavorite()
    {
        $notice = $this->getNotice($this->activity->objects[0]);
        $profile = $this->ensureProfile()->localProfile();

        $old = Fave::pkeyGet(array('user_id' => $profile->id,
                                   'notice_id' => $notice->id));

        if (!empty($old)) {
            // TRANS: Client exception.
            throw new ClientException(_m('This is already a favorite.'));
        }

        if (!Fave::addNew($profile, $notice)) {
           // TRANS: Client exception.
           throw new ClientException(_m('Could not save new favorite.'));
        }
    }

    /**
     * Remote user doesn't like one of our posts after all!
     * Confirm the post is ours, and save a local favorite event.
     */
    function handleUnfavorite()
    {
        $notice = $this->getNotice($this->activity->objects[0]);
        $profile = $this->ensureProfile()->localProfile();

        $fave = Fave::pkeyGet(array('user_id' => $profile->id,
                                   'notice_id' => $notice->id));
        if (empty($fave)) {
            // TRANS: Client exception.
            throw new ClientException(_m('Notice was not favorited!'));
        }

        $fave->delete();
    }

    function handleTag()
    {
        if (ActivityUtils::compareObjectTypes($this->activity->target->type, ActivityObject::_LIST)) {
            if (!ActivityUtils::compareObjectTypes($this->activity->objects[0]->type, ActivityObject::PERSON)) {
                // TRANS: Client exception.
                throw new ClientException(_m('Not a person object.'));
                return false;
            }
            // this is a peopletag
            $tagged = User::staticGet('uri', $this->activity->objects[0]->id);

            if (empty($tagged)) {
                // TRANS: Client exception.
                throw new ClientException(_m('Unidentified profile being listed.'));
            }

            if ($tagged->id !== $this->user->id) {
                // TRANS: Client exception.
                throw new ClientException(_m('This user is not the one being listed.'));
            }

            // save the list
            $tagger = $this->ensureProfile();
            $list   = Ostatus_profile::ensureActivityObjectProfile($this->activity->target);

            $ptag = $list->localPeopletag();
            $result = Profile_tag::setTag($ptag->tagger, $tagged->id, $ptag->tag);
            if (!$result) {
                // TRANS: Client exception.
                throw new ClientException(_m('The listing could not be saved.'));
            }
        }
    }

    function handleUntag()
    {
        if (ActivityUtils::compareObjectTypes($this->activity->target->type, ActivityObject::_LIST)) {
            if (!ActivityUtils::compareObjectTypes($this->activity->objects[0]->type, ActivityObject::PERSON)) {
                // TRANS: Client exception.
                throw new ClientException(_m('Not a person object.'));
                return false;
            }
            // this is a peopletag
            $tagged = User::staticGet('uri', $this->activity->objects[0]->id);

            if (empty($tagged)) {
                // TRANS: Client exception.
                throw new ClientException(_m('Unidentified profile being unlisted.'));
            }

            if ($tagged->id !== $this->user->id) {
                // TRANS: Client exception.
                throw new ClientException(_m('This user is not the one being unlisted.'));
            }

            // save the list
            $tagger = $this->ensureProfile();
            $list   = Ostatus_profile::ensureActivityObjectProfile($this->activity->target);

            $ptag = $list->localPeopletag();
            $result = Profile_tag::unTag($ptag->tagger, $tagged->id, $ptag->tag);

            if (!$result) {
                // TRANS: Client exception.
                throw new ClientException(_m('The listing could not be deleted.'));
            }
        }
    }

    /**
     * @param ActivityObject $object
     * @return Notice
     * @throws ClientException on invalid input
     */
    function getNotice($object)
    {
        if (!$object) {
            // TRANS: Client exception.
            throw new ClientException(_m('Cannot favorite/unfavorite without an object.'));
        }

        if (!ActivityUtils::compareObjectTypes($object->type, array (
                    ActivityObject::ARTICLE,
                    ActivityObject::BLOGENTRY,
                    ActivityObject::NOTE,
                    ActivityObject::STATUS,
                    ActivityObject::COMMENT,
			))) {
            // TRANS: Client exception.
            throw new ClientException(_m('Cannot handle that kind of object for liking/faving.'));
        }

        $notice = Notice::staticGet('uri', $object->id);

        if (empty($notice)) {
            // TRANS: Client exception. %s is an object ID.
            throw new ClientException(sprintf(_m('Notice with ID %s unknown.'),$object->id));
        }

        if ($notice->profile_id != $this->user->id) {
            // TRANS: Client exception. %1$s is a notice ID, %2$s is a user ID.
            throw new ClientException(sprintf(_m('Notice with ID %1$s not posted by %2$s.'),$object->id,$this->user->id));
        }

        return $notice;
    }
}
