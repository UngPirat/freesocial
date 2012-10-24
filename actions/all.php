<?php
/**
 * StatusNet - the distributed open-source microblogging tool
 * Copyright (C) 2008-2011, StatusNet, Inc.
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
 * @category Actions
 * @package  Actions
 * @author   Adrian Lang <mail@adrianlang.de>
 * @author   Brenda Wallace <shiny@cpan.org>
 * @author   Brion Vibber <brion@pobox.com>
 * @author   Craig Andrews <candrews@integralblue.com>
 * @author   Evan Prodromou <evan@status.net>
 * @author   Jeffery To <jeffery.to@gmail.com>
 * @author   Meitar Moscovitz <meitarm@gmail.com>
 * @author   Mike Cochrane <mikec@mikenz.geek.nz>
 * @author   Robin Millette <millette@status.net>
 * @author   Sarven Capadisli <csarven@status.net>
 * @author   Siebrand Mazeland <s.mazeland@xs4all.nl>
 * @author   Zach Copley <zach@status.net>
 * @copyright 2009 Free Software Foundation, Inc http://www.fsf.org
 * @license  GNU Affero General Public License http://www.gnu.org/licenses/
 * @link     http://status.net
 */

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

require_once INSTALLDIR.'/lib/personalgroupnav.php';
require_once INSTALLDIR.'/lib/noticelist.php';
require_once INSTALLDIR.'/lib/feedlist.php';

class AllAction extends ShowstreamAction
{
    var $notice;

    function isReadOnly($args)
    {
        return true;
    }

    function prepare($args)
    {
        parent::prepare($args);

        $user = common_current_user();

        $stream = new ThreadingInboxNoticeStream($this->subject, Profile::current());

        $this->notice = $stream->getNotices(($this->page-1)*NOTICES_PER_PAGE,
                                            NOTICES_PER_PAGE + 1);

        if ($this->page > 1 && $this->notice->N == 0) {
            // TRANS: Server error when page not found (404).
            $this->serverError(_('No such page.'), 404);
        }

        return true;
    }

    function title()
    {
        $user = common_current_user();
        if (!empty($user) && $user->id == $this->subject->id) {
            // TRANS: Title of a user's own start page.
            return _('Home timeline');
        } else {
            $profile = $this->subject->getProfile();
            // TRANS: Title of another user's start page.
            // TRANS: %s is the other user's name.
            return sprintf(_("%s's home timeline"), $profile->getBestName());
        }
    }

    function getFeeds()
    {
        return array(
            new Feed(Feed::JSON,
                common_local_url(
                    'ApiTimelineFriends', array(
                        'format' => 'as',
                        'id' => $this->subject->nickname
                    )
                ),
                // TRANS: %s is user nickname.
                sprintf(_('Feed for friends of %s (Activity Streams JSON)'), $this->subject->nickname)),
            new Feed(Feed::RSS1,
                common_local_url(
                    'allrss', array(
                        'nickname' =>
                        $this->subject->nickname)
                ),
                // TRANS: %s is user nickname.
                sprintf(_('Feed for friends of %s (RSS 1.0)'), $this->subject->nickname)),
            new Feed(Feed::RSS2,
                common_local_url(
                    'ApiTimelineFriends', array(
                        'format' => 'rss',
                        'id' => $this->subject->nickname
                    )
                ),
                // TRANS: %s is user nickname.
                sprintf(_('Feed for friends of %s (RSS 2.0)'), $this->subject->nickname)),
            new Feed(Feed::ATOM,
                common_local_url(
                    'ApiTimelineFriends', array(
                        'format' => 'atom',
                        'id' => $this->subject->nickname
                    )
                ),
                // TRANS: %s is user nickname.
                sprintf(_('Feed for friends of %s (Atom)'), $this->subject->nickname))
        );
    }

    function showEmptyListMessage()
    {
        // TRANS: Empty list message. %s is a user nickname.
        $message = sprintf(_('This is the timeline for %s and friends but no one has posted anything yet.'), $this->subject->nickname) . ' ';

        if (common_logged_in()) {
            $current_user = common_current_user();
            if ($this->subject->id === $current_user->id) {
                // TRANS: Encouragement displayed on logged in user's empty timeline.
                // TRANS: This message contains Markdown links. Keep "](" together.
                $message .= _('Try subscribing to more people, [join a group](%%action.groups%%) or post something yourself.');
            } else {
                // TRANS: %1$s is user nickname, %2$s is user nickname, %2$s is user nickname prefixed with "@".
                // TRANS: This message contains Markdown links. Keep "](" together.
                $message .= sprintf(_('You can try to [post something to them](%%%%action.newnotice%%%%?status_textarea=%3$s).'), $this->subject->nickname, $this->subject->nickname, '@' . $this->subject->nickname);
            }
        } else {
            // TRANS: Encouragement displayed on empty timeline user pages for anonymous users.
            // TRANS: %s is a user nickname. This message contains Markdown links. Keep "](" together.
            $message .= sprintf(_('Why not [register an account](%%%%action.register%%%%) and then post a notice to them.'), $this->subject->nickname);
        }

        $this->elementStart('div', 'guide');
        $this->raw(common_markup_to_html($message));
        $this->elementEnd('div');
    }

    function showContent()
    {
        if (Event::handle('StartShowAllContent', array($this))) {

            $profile = null;

            $current_user = common_current_user();

            if (!empty($current_user)) {
                $profile = $current_user->getProfile();
            }

            $nl = new ThreadedNoticeList($this->notice, $this, $profile);

            $cnt = $nl->show();

            if (0 == $cnt) {
                $this->showEmptyListMessage();
            }

            $this->pagination(
                $this->page > 1, $cnt > NOTICES_PER_PAGE,
                $this->page, 'all', array('nickname' => $this->subject->nickname)
            );

            Event::handle('EndShowAllContent', array($this));
        }
    }

    function showSections()
    {
        // Show invite button, as long as site isn't closed, and
        // we have a logged in user.
        if (common_config('invite', 'enabled') && !common_config('site', 'closed') && common_logged_in()) {
            if (!common_config('site', 'private')) {
                $ibs = new InviteButtonSection(
                    $this,
                    // TRANS: Button text for inviting more users to the StatusNet instance.
                    // TRANS: Less business/enterprise-oriented language for public sites.
                    _m('BUTTON', 'Send invite')
                );
            } else {
                $ibs = new InviteButtonSection($this);
            }
            $ibs->show();
        }
        // XXX: make this a little more convenient

        if (!common_config('performance', 'high')) {
            $pop = new PopularNoticeSection($this, Profile::current());
            $pop->show();
            $pop = new InboxTagCloudSection($this, $this->subject);
            $pop->show();
        }
    }
}

class ThreadingInboxNoticeStream extends ThreadingNoticeStream
{
    function __construct($user, $profile)
    {
        parent::__construct(new InboxNoticeStream($user, $profile));
    }
}
