<?php
/**
 * StatusNet, the distributed open-source microblogging tool
 *
 * Base class for group actions
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
 * @category  Action
 * @package   StatusNet
 * @author    Zach Copley <zach@status.net>
 * @copyright 2009-2011 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

define('MEMBERS_PER_SECTION', 27);

/**
 * Base class for group actions, similar to ProfileAction
 *
 * @category Action
 * @package  StatusNet
 * @author   Zach Copley <zach@status.net>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 */
class GroupAction extends ProfileAction
{
    function showProfileBlock()
    {
        $block = new GroupProfileBlock($this, $this->subject);
        $block->show();
    }

    /**
     * Fill in the sidebar.
     *
     * @return void
     */
    function showSections()
    {
        $this->showMembers();
        $cur = common_current_user();
        if ($cur && $cur->isAdmin($this->subject)) {
            $this->showPending();
            $this->showBlocked();
        }

        $this->showAdmins();

        if (!common_config('performance', 'high')) {
            $cloud = new GroupTagCloudSection($this, $this->subject);
            $cloud->show();
        }
    }

    /**
     * Show mini-list of members
     *
     * @return void
     */
    function showMembers()
    {
        $member = $this->subject->getMembers(0, MEMBERS_PER_SECTION, true);

        if (!$member) {
            return;
        }

        $this->elementStart('div', array('id' => 'entity_members',
                                         'class' => 'section'));

        if (Event::handle('StartShowGroupMembersMiniList', array($this))) {
            $this->elementStart('h2');

            $this->element('a', array('href' => common_local_url('groupmembers', array('nickname' =>
                                                                                       $this->subject->nickname))),
                           // TRANS: Header for mini list of group members on a group page (h2).
                           _('Members'));

            $this->text(' ');

            $this->text($this->subject->getMemberCount());

            $this->elementEnd('h2');

            $gmml = new GroupMembersMiniList($member, $this);
            $cnt = $gmml->show();
            if ($cnt == 0) {
                // TRANS: Description for mini list of group members on a group page when the group has no members.
                $this->element('p', null, _('(None)'));
            }

            // @todo FIXME: Should be shown if a group has more than 27 members, but I do not see it displayed at
            //              for example http://identi.ca/group/statusnet. Broken?
            if ($cnt > MEMBERS_PER_SECTION) {
                $this->element('a', array('href' => common_local_url('groupmembers',
                                                                     array('nickname' => $this->subject->nickname))),
                               // TRANS: Link to all group members from mini list of group members if group has more than n members.
                               _('All members'));
            }

            Event::handle('EndShowGroupMembersMiniList', array($this));
        }

        $this->elementEnd('div');
    }

    function showPending()
    {
        if ($this->subject->join_policy != User_group::JOIN_POLICY_MODERATE) {
            return;
        }

        $pending = $this->subject->getQueueCount();

        if (!$pending) {
            return;
        }

        $request = $this->subject->getRequests(0, MEMBERS_PER_SECTION);

        if (!$request) {
            return;
        }

        $this->elementStart('div', array('id' => 'entity_pending',
                                         'class' => 'section'));

        if (Event::handle('StartShowGroupPendingMiniList', array($this))) {

            $this->elementStart('h2');

            $this->element('a', array('href' => common_local_url('groupqueue', array('nickname' =>
                                                                                     $this->subject->nickname))),
                           // TRANS: Header for mini list of users with a pending membership request on a group page (h2).
                           _('Pending'));

            $this->text(' ');

            $this->text($pending);

            $this->elementEnd('h2');

            $gmml = new ProfileMiniList($request, $this);
            $gmml->show();

            Event::handle('EndShowGroupPendingMiniList', array($this));
        }

        $this->elementEnd('div');
    }

    function showBlocked()
    {
        $blocked = $this->subject->getBlocked(0, MEMBERS_PER_SECTION);

        if (!$blocked) {
            return;
        }

        $this->elementStart('div', array('id' => 'entity_blocked',
                                         'class' => 'section'));

        if (Event::handle('StartShowGroupBlockedMiniList', array($this))) {

            $this->elementStart('h2');

            $this->element('a', array('href' => common_local_url('blockedfromgroup', array('nickname' =>
                                                                                           $this->subject->nickname))),
                           // TRANS: Header for mini list of users that are blocked in a group page (h2).
                           _('Blocked'));

            $this->text(' ');

            $this->text($this->subject->getBlockedCount());

            $this->elementEnd('h2');

            $gmml = new GroupBlockedMiniList($blocked, $this);
            $cnt = $gmml->show();
            if ($cnt == 0) {
                // TRANS: Description for mini list of group members on a group page when the group has no members.
                $this->element('p', null, _('(None)'));
            }

            // @todo FIXME: Should be shown if a group has more than 27 members, but I do not see it displayed at
            //              for example http://identi.ca/group/statusnet. Broken?
            if ($cnt > MEMBERS_PER_SECTION) {
                $this->element('a', array('href' => common_local_url('blockedfromgroup',
                                                                     array('nickname' => $this->subject->nickname))),
                               // TRANS: Link to all group members from mini list of group members if group has more than n members.
                               _('All members'));
            }

            Event::handle('EndShowGroupBlockedMiniList', array($this));
        }

        $this->elementEnd('div');
    }

    /**
     * Show list of admins
     *
     * @return void
     */
    function showAdmins()
    {
        $adminSection = new GroupAdminSection($this, $this->subject);
        $adminSection->show();
    }

    function noticeFormOptions()
    {
        $options = parent::noticeFormOptions();
        $cur = common_current_user();

		$group = $this->subject->getGroup();
        if (!empty($cur) && $cur->isMember($group)) {
            $options['to_group'] =  $group;
        }

        return $options;
    }
}

class GroupAdminSection extends ProfileSection
{
    var $group;

    function __construct($out, $group)
    {
        parent::__construct($out);
        $this->group = $group;
    }

    function getProfiles()
    {
        return $this->group->getAdmins();
    }

    function title()
    {
        // TRANS: Title for list of group administrators on a group page.
        return _m('TITLE','Admins');
    }

    function divId()
    {
        return 'group_admins';
    }

    function moreUrl()
    {
        return null;
    }
}

class GroupMembersMiniList extends ProfileMiniList
{
    function newListItem($profile)
    {
        return new GroupMembersMiniListItem($profile, $this->action);
    }
}

class GroupMembersMiniListItem extends ProfileMiniListItem
{
    function linkAttributes()
    {
        $aAttrs = parent::linkAttributes();

        if (common_config('nofollow', 'members')) {
            $aAttrs['rel'] .= ' nofollow';
        }

        return $aAttrs;
    }
}

class GroupBlockedMiniList extends ProfileMiniList
{
    function newListItem($profile)
    {
        return new GroupBlockedMiniListItem($profile, $this->action);
    }
}

class GroupBlockedMiniListItem extends ProfileMiniListItem
{
    function linkAttributes()
    {
        $aAttrs = parent::linkAttributes();

        if (common_config('nofollow', 'members')) {
            $aAttrs['rel'] .= ' nofollow';
        }

        return $aAttrs;
    }
}

class ThreadingGroupNoticeStream extends ThreadingNoticeStream
{
    function __construct($group, $profile)
    {
        parent::__construct(new GroupNoticeStream($group, $profile));
    }
}
