<?php
/**
 * StatusNet, the distributed open-source microblogging tool
 *
 * User profile page
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
 * @category  Personal
 * @package   StatusNet
 * @author    Evan Prodromou <evan@status.net>
 * @author    Sarven Capadisli <csarven@status.net>
 * @copyright 2008-2009 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET')) {
    exit(1);
}

require_once INSTALLDIR.'/lib/personalgroupnav.php';
require_once INSTALLDIR.'/lib/noticelist.php';
require_once INSTALLDIR.'/lib/profileminilist.php';
require_once INSTALLDIR.'/lib/groupminilist.php';
require_once INSTALLDIR.'/lib/feedlist.php';

/**
 * User profile page
 *
 * When I created this page, "show stream" seemed like the best name for it.
 * Now, it seems like a really bad name.
 *
 * It shows a stream of the user's posts, plus lots of profile info, links
 * to subscriptions and stuff, etc.
 *
 * @category Personal
 * @package  StatusNet
 * @author   Evan Prodromou <evan@status.net>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 */
class ShowstreamAction extends ProfileAction
{
    var $notice;
    var $tag     = null;
	protected $action = 'showstream';

    function prepare($args)
    {
        parent::prepare($args);

        $p = Profile::current();

		if ($this->profile->isUser()) {
	        if (empty($this->tag)) {
    	        $stream = new ProfileNoticeStream($this->profile, $p);
	        } else {
        	    $stream = new TaggedProfileNoticeStream($this->profile, $this->tag, $p);
    	    }
		} elseif ($this->profile->isGroup()) {
        	$stream = new ThreadingGroupNoticeStream($this->subject, $this->userProfile);
		}

        $this->notice = $stream->getNotices(($this->page-1)*NOTICES_PER_PAGE, NOTICES_PER_PAGE);
        $this->tag = $this->trimmed('tag');

        return true;
    }

    function isReadOnly($args)
    {
        return true;
    }

    function title()
    {
        $base = $this->profile->getFancyName();
        if (!empty($this->tag)) {
            if ($this->page == 1) {
                // TRANS: Page title showing tagged notices in one user's timeline.
                // TRANS: %1$s is the username, %2$s is the hash tag.
                return sprintf(_('Notices by %1$s tagged %2$s'), $base, $this->tag);
            } else {
                // TRANS: Page title showing tagged notices in one user's timeline.
                // TRANS: %1$s is the username, %2$s is the hash tag, %3$d is the page number.
                return sprintf(_('Notices by %1$s tagged %2$s, page %3$d'), $base, $this->tag, $this->page);
            }
        } else {
            if ($this->page == 1) {
                return $base;
            } else {
                // TRANS: Extended page title showing tagged notices in one user's timeline.
                // TRANS: %1$s is the username, %2$d is the page number.
                return sprintf(_('Notices by %1$s, page %2$d'),
                               $base,
                               $this->page);
            }
        }
    }

    /**
     * Show the content
     *
     * A stream of notices
     *
     * @return void
     */
    function showContent()
    {
        if (Event::handle('ShowStreamNoticeList', array($this->notice, $this, &$nl))) {
            $nl = new NoticeList($this->notice, $this);
        }

        $cnt = $nl->show();
        if (0 === $cnt) {
            $this->showEmptyListMessage();
        }

        $args = array('nickname' => $this->subject->nickname);
        if (!empty($this->tag))
        {
            $args['tag'] = $this->tag;
        }

        $this->pagination($this->page > 1, $cnt > NOTICES_PER_PAGE,
                          $this->page, $this->action, $args);
    }

    function showProfileBlock()
    {
        $block = new AccountProfileBlock($this, $this->profile);
        $block->show();
    }

    function showPageNoticeBlock()
    {
        return;
    }

    function getFeeds()
    {
        if (!empty($this->tag)) {
            return array(new Feed(Feed::RSS1,
                                  common_local_url('userrss',
                                                   array('nickname' => $this->subject->nickname,
                                                         'tag' => $this->tag)),
                                  // TRANS: Title for link to notice feed.
                                  // TRANS: %1$s is a user nickname, %2$s is a hashtag.
                                  sprintf(_('Notice feed for %1$s tagged %2$s (RSS 1.0)'),
                                          $this->subject->nickname, $this->tag)));
        }

        return array(new Feed(Feed::JSON,
                              common_local_url('ApiTimelineUser',
                                               array(
                                                    'id' => $this->subject->id,
                                                    'format' => 'as')),
                              // TRANS: Title for link to notice feed.
                              // TRANS: %s is a user nickname.
                              sprintf(_('Notice feed for %s (Activity Streams JSON)'),
                                      $this->subject->nickname)),
                     new Feed(Feed::RSS1,
                              common_local_url('userrss',
                                               array('nickname' => $this->subject->nickname)),
                              // TRANS: Title for link to notice feed.
                              // TRANS: %s is a user nickname.
                              sprintf(_('Notice feed for %s (RSS 1.0)'),
                                      $this->subject->nickname)),
                     new Feed(Feed::RSS2,
                              common_local_url('ApiTimelineUser',
                                               array(
                                                    'id' => $this->subject->id,
                                                    'format' => 'rss')),
                              // TRANS: Title for link to notice feed.
                              // TRANS: %s is a user nickname.
                              sprintf(_('Notice feed for %s (RSS 2.0)'),
                                      $this->subject->nickname)),
                     new Feed(Feed::ATOM,
                              common_local_url('ApiTimelineUser',
                                               array(
                                                    'id' => $this->subject->id,
                                                    'format' => 'atom')),
                              // TRANS: Title for link to notice feed.
                              // TRANS: %s is a user nickname.
                              sprintf(_('Notice feed for %s (Atom)'),
                                      $this->subject->nickname)),
                     new Feed(Feed::FOAF,
                              common_local_url('foaf', array('nickname' =>
                                                             $this->subject->nickname)),
                              // TRANS: Title for link to notice feed. FOAF stands for Friend of a Friend.
                              // TRANS: More information at http://www.foaf-project.org. %s is a user nickname.
                              sprintf(_('FOAF for %s'), $this->subject->nickname)));
    }

    function extraHead()
    {
        if ($this->profile->description) {
            $this->element('meta', array('name' => 'description',
                                         'content' => $this->profile->description));
        }

        if ($this->subject->emailmicroid && $this->subject->email && $this->profile->profileurl) {
            $id = new Microid('mailto:'.$this->subject->email,
                              $this->selfUrl());
            $this->element('meta', array('name' => 'microid',
                                         'content' => $id->toString()));
        }

        $rsd = common_local_url('rsd',
                                array('nickname' => $this->subject->nickname));

        // RSD, http://tales.phrasewise.com/rfc/rsd
        $this->element('link', array('rel' => 'EditURI',
                                     'type' => 'application/rsd+xml',
                                     'href' => $rsd));

        if ($this->page != 1) {
            $this->element('link', array('rel' => 'canonical',
                                         'href' => $this->subject->profileurl));
        }
    }

    function showEmptyListMessage()
    {
        // TRANS: First sentence of empty list message for a timeline. $1%s is a user nickname.
        $message = sprintf(_('This is a stream of %2$s related to the user %1$s, but no activity has yet been seen.'), $this->subject->nickname, $this->action) . ' ';

        if (common_logged_in()) {
            $current_user = common_current_user();
            if ($this->subject->id === $current_user->id) {
                // TRANS: Second sentence of empty list message for a stream for the user themselves.
                $message .= _('Seen anything interesting recently? You haven\'t posted any notices yet, now would be a good time to start :)');
            } else {
                // TRANS: Second sentence of empty  list message for a non-self timeline. %1$s is a user nickname, %2$s is a part of a URL.
                // TRANS: This message contains a Markdown link. Keep "](" together.
                $message .= sprintf(_('You can try to [post something to them](%%%%action.newnotice%%%%?status_textarea=%2$s).'), $this->subject->nickname, '@' . $this->subject->nickname);
            }
        }
        else {
            // TRANS: Second sentence of empty message for anonymous users. %s is a user nickname.
            // TRANS: This message contains a Markdown link. Keep "](" together.
            $message .= sprintf(_('Why not [register an account](%%%%action.register%%%%) and then post a notice to them.'), $this->subject->nickname);
        }

        $this->elementStart('div', 'guide');
        $this->raw(common_markup_to_html($message));
        $this->elementEnd('div');
    }

    function showAnonymousMessage()
    {
        if (!(common_config('site','closed') || common_config('site','inviteonly'))) {
            // TRANS: Announcement for anonymous users showing a timeline if site registrations are open.
            // TRANS: This message contains a Markdown link. Keep "](" together.
            $m = sprintf(_('**%s** has an account on %%%%site.name%%%%, a [micro-blogging](http://en.wikipedia.org/wiki/Micro-blogging) service ' .
                           'based on the Free Software [StatusNet](http://status.net/) tool. ' .
                           '[Join now](%%%%action.register%%%%) to follow **%s**\'s notices and many more! ([Read more](%%%%doc.help%%%%))'),
                         $this->subject->nickname, $this->subject->nickname);
        } else {
            // TRANS: Announcement for anonymous users showing a timeline if site registrations are closed or invite only.
            // TRANS: This message contains a Markdown link. Keep "](" together.
            $m = sprintf(_('**%s** has an account on %%%%site.name%%%%, a [micro-blogging](http://en.wikipedia.org/wiki/Micro-blogging) service ' .
                           'based on the Free Software [StatusNet](http://status.net/) tool.'),
                         $this->subject->nickname, $this->subject->nickname);
        }
        $this->elementStart('div', array('id' => 'anon_notice'));
        $this->raw(common_markup_to_html($m));
        $this->elementEnd('div');
    }

    function showSections()
    {
        parent::showSections();
        if (!common_config('performance', 'high')) {
            $cloud = new PersonalTagCloudSection($this, $this->subject);
            $cloud->show();
        }
    }

    function noticeFormOptions()
    {
        $options = parent::noticeFormOptions();
        $cur = common_current_user();

        if (empty($cur) || $cur->id != $this->subject->id) {
            $options['to_profile'] =  $this->subject;
        }

        return $options;
    }
}

// We don't show the author for a profile, since we already know who it is!

/**
 * Slightly modified from standard list; the author & avatar are hidden
 * in CSS. We used to remove them here too, but as it turns out that
 * confuses the inline reply code... and we hide them in CSS anyway
 * since realtime updates come through in original form.
 *
 * Remaining customization right now is for the repeat marker, where
 * it'll list who the original poster was instead of who did the repeat
 * (since the repeater is you, and the repeatee isn't shown!)
 * This will remain inconsistent if realtime updates come through,
 * since those'll get rendered as a regular NoticeListItem.
 */
class ProfileNoticeList extends NoticeList
{
    function newListItem($notice)
    {
        return new ProfileNoticeListItem($notice, $this->out);
    }
}

class ProfileNoticeListItem extends DoFollowListItem
{
    /**
     * show a link to the author of repeat
     *
     * @return void
     */
    function showRepeat()
    {
        if (!empty($this->repeat)) {

            // FIXME: this code is almost identical to default; need to refactor

            $attrs = array('href' => $this->subject->profileurl,
                           'class' => 'url');

            if (!empty($this->subject->fullname)) {
                $attrs['title'] = $this->subject->getFancyName();
            }

            $this->out->elementStart('span', 'repeat');

            $text_link = XMLStringer::estring('a', $attrs, $this->subject->nickname);

            // TRANS: Link to the author of a repeated notice. %s is a linked nickname.
            $this->out->raw(sprintf(_('Repeat of %s'), $text_link));

            $this->out->elementEnd('span');
        }
    }
}
