<?php
/**
 * StatusNet, the distributed open-source microblogging tool
 *
 * Widget to show a sortable list of profiles
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
 * @category  Public
 * @package   StatusNet
 * @author    Zach Copley <zach@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET')) {
    exit(1);
}

require_once INSTALLDIR . '/lib/subscriptionlist.php';

/**
 * Widget to show a sortable list of subscriptions
 *
 * @category Public
 * @package  StatusNet
 * @author   Zach Copley <zach@status.net>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 */
class SortableSubscriptionList extends SubscriptionList
{
    /** Owner of this list */
    var $owner = null;

    function __construct($profile, $owner=null, $action=null)
    {
        parent::__construct($profile, $owner, $action);

        $this->owner = $owner;
    }

    function startList()
    {
        $this->out->elementStart('table', array('class' => 'profile_list xoxo'));
        $this->out->elementStart('thead');
        $this->out->elementStart('tr');

        $tableHeaders = array(
            // TRANS: Column header in table for user nickname.
            'nickname'    => _m('Nickname'),
            // TRANS: Column header in table for timestamp when user was created.
            'created'     => _m('Created')
        );

        foreach ($tableHeaders as $id => $label) {

            $attrs   = array('id' => $id);
            $current = (!empty($this->action->sort) && $this->action->sort == $id);

            if ($current || empty($this->action->sort) && $id == 'nickname') {
                $attrs['class'] = 'current';
            }

            if ($current && $this->action->reverse) {
                $attrs['class'] .= ' reverse';
                $attrs['class'] = trim($attrs['class']);
            }

            $this->out->elementStart('th', $attrs);

            $linkAttrs = array();
            $params    = array('sort' => $id);

            if (!empty($this->action->q)) {
                $params['q'] = $this->action->q;
            }

            if ($current && !$this->action->reverse) {
                $params['reverse'] = 'true';
            }

            $args = array();

            $filter = $this->action->arg('filter');

            if (!empty($filter)) {
                $args['filter'] = $filter;
            }

            $linkAttrs['href'] = common_local_url(
                $this->action->arg('action'), $args, $params
            );

            $this->out->element('a', $linkAttrs, $label);
            $this->out->elementEnd('th');
        }

        // TRANS: Column header for number of subscriptions.
        $this->out->element('th', array('id' => 'subscriptions'), _m('Subscriptions'));
        // TRANS: Column header for number of notices.
        $this->out->element('th', array('id' => 'notices'), _m('Notices'));
        $this->out->element('th', array('id' => 'controls'), null);

        $this->out->elementEnd('tr');
        $this->out->elementEnd('thead');

        $this->out->elementStart('tbody');
    }

    function endList()
    {
        $this->out->elementEnd('tbody');
        $this->out->elementEnd('table');
    }

    function showProfiles()
    {
        // Note: we don't use fetchAll() because it's borked with query()

        $profiles = array();

        while ($this->profile->fetch()) {
            $profiles[] = clone($this->profile);
        }

        $cnt = count($profiles);

        $max = min($cnt, $this->maxProfiles());

        for ($i = 0; $i < $max; $i++) {
            $odd = ($i % 2 == 0); // for zebra striping
            $pli = $this->newListItem($profiles[$i], $odd);
            $pli->show();
        }

        return $cnt;
    }

    function newListItem($profile, $odd)
    {
        return new SortableSubscriptionListItem($profile, $this->owner, $this->action, $odd);
    }
}

class SortableSubscriptionListItem extends SubscriptionListItem
{
    /** Owner of this list */
    var $owner = null;

    function __construct($profile, $owner, $action, $alt)
    {
        parent::__construct($profile, $owner, $action);

        $this->alt   = $alt; // is this row alternate?
        $this->owner = $owner;
    }

    function startItem()
    {
        $attr = array(
            'class' => 'profile',
            'id'    => 'profile-' . $this->profile->id
        );

        if ($this->alt) {
            $attr['class'] .= ' alt';
        }

        $this->out->elementStart('tr', $attr);
    }

    function endItem()
    {
        $this->out->elementEnd('tr');
    }

    function startProfile()
    {
        $this->out->elementStart('td', 'entity_profile vcard entry-content');
    }

    function endProfile()
    {
        $this->out->elementEnd('td');
    }

    function startActions()
    {
        $this->out->elementStart('td', 'entity_actions');
        $this->out->elementStart('ul');
    }

    function endActions()
    {
        $this->out->elementEnd('ul');
        $this->out->elementEnd('td');
    }

    function show()
    {
        if (Event::handle('StartProfileListItem', array($this))) {
            $this->startItem();
            if (Event::handle('StartProfileListItemProfile', array($this))) {
                $this->showProfile();
                Event::handle('EndProfileListItemProfile', array($this));
            }

            // XXX Add events?
            $this->showCreatedDate();
            $this->showSubscriberCount();
            $this->showNoticeCount();

            if (Event::handle('StartProfileListItemActions', array($this))) {
                $this->showActions();
                Event::handle('EndProfileListItemActions', array($this));
            }
            $this->endItem();
            Event::handle('EndProfileListItem', array($this));
        }
    }

    function showSubscriberCount()
    {
        $this->out->elementStart('td', 'entry_subscriber_count');
        $this->out->raw($this->profile->subscriberCount());
        $this->out->elementEnd('td');
    }

    function showCreatedDate()
    {
        $this->out->elementStart('td', 'entry_created');
        $this->out->raw(date('j M Y', strtotime($this->profile->created)));
        $this->out->elementEnd('td');
    }

    function showNoticeCount()
    {
        $this->out->elementStart('td', 'entry_notice_count');
        $this->out->raw($this->profile->noticeCount());
        $this->out->elementEnd('td');
    }

    /**
     * Overrided to truncate the user description if it's real long, because it
     * looks better that way in the SortableSubscriptionList's table
     */
    function showDescription()
    {
        if (!empty($this->profile->description)) {
            $cutoff = 140; // XXX Should this be configurable?
            $description    = htmlspecialchars($this->profile->description);

            if (mb_strlen($description) > $cutoff) {
                $description = mb_substr($description, 0, $cutoff - 1)
                    .'<a href="' . $this->profile->profileurl .'">…</a>';
            }

            $this->out->elementStart('p', 'note');
            $this->out->raw($description);
            $this->out->elementEnd('p');
        }
    }

    /**
     * Only show the tags if we're logged in
     */
    function showTags()
    {
         if (common_logged_in()) {
            parent::showTags();
        }

    }
}
