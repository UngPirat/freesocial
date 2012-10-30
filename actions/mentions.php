<?php
/**
 * StatusNet, the distributed open-source microblogging tool
 *
 * List of mentions
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
 * @copyright 2008-2011 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

require_once INSTALLDIR.'/lib/personalgroupnav.php';
require_once INSTALLDIR.'/lib/noticelist.php';
require_once INSTALLDIR.'/lib/feedlist.php';

/**
 * List of mentions
 *
 * @category Personal
 * @package  StatusNet
 * @author   Evan Prodromou <evan@status.net>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 */
class MentionsAction extends TimelineAction
{
    var $page = null;
    var $notice;
	protected $action = 'mentions';

    /**
     * Prepare the object
     *
     * Check the input values and initialize the object.
     * Shows an error page on bad input.
     *
     * @param array $args $_REQUEST data
     *
     * @return boolean success flag
     */
    function prepare($args)
    {
        parent::prepare($args);

        $nickname = common_canonical_nickname($this->arg('nickname'));

        $this->subject = User::staticGet('nickname', $nickname);

        if (!$this->subject) {
            // TRANS: Client error displayed when trying to reply to a non-exsting user.
            $this->clientError(_('No such user.'));
            return false;
        }

        $profile = $this->subject->getProfile();

        if (!$profile) {
            // TRANS: Error message displayed when referring to a user without a profile.
            $this->serverError(_('User has no profile.'));
            return false;
        }

        $this->page = ($this->arg('page')) ? ($this->arg('page')+0) : 1;

        common_set_returnto($this->selfUrl());

        $stream = new MentionNoticeStream($this->subject->id,
                                        Profile::current());

        $this->notice = $stream->getNotices(($this->page-1) * NOTICES_PER_PAGE,
                                            NOTICES_PER_PAGE + 1);

        if($this->page > 1 && $this->notice->N == 0){
            // TRANS: Server error when page not found (404)
            $this->serverError(_('No such page.'),$code=404);
        }

        return true;
    }

    /**
     * Title of the page
     *
     * Includes name of user and page number.
     *
     * @return string title of page
     */
    function title()
    {
        if ($this->page == 1) {
            // TRANS: Title for first page of mentions of a user.
            // TRANS: %s is a user nickname.
            return sprintf(_("Mentions of %s"), $this->subject->nickname);
        } else {
            // TRANS: Title for all but the first page of mentions of a user.
            // TRANS: %1$s is a user nickname, %2$d is a page number.
            return sprintf(_('Mentions of %1$s, page %2$d'),
                           $this->subject->nickname,
                           $this->page);
        }
    }

    /**
     * Feeds for the <head> section
     *
     * @return void
     */
    function getFeeds()
    {
        return array(new Feed(Feed::JSON,
                              common_local_url('ApiTimelineMentions',
                                               array(
                                                    'id' => $this->subject->nickname,
                                                    'format' => 'as')),
                              // TRANS: Link for feed with mentions of a user.
                              // TRANS: %s is a user nickname.
                              sprintf(_('Mention feed for %s (Activity Streams JSON)'),
                                      $this->subject->nickname)),
                     new Feed(Feed::RSS1,
                              common_local_url('mentionsrss',
                                               array('nickname' => $this->subject->nickname)),
                              // TRANS: Link for feed with mentions of a user.
                              // TRANS: %s is a user nickname.
                              sprintf(_('Mention feed for %s (RSS 1.0)'),
                                      $this->subject->nickname)),
                     new Feed(Feed::RSS2,
                              common_local_url('ApiTimelineMentions',
                                               array(
                                                    'id' => $this->subject->nickname,
                                                    'format' => 'rss')),
                              // TRANS: Link for feed with mentions of a user.
                              // TRANS: %s is a user nickname.
                              sprintf(_('Mention feed for %s (RSS 2.0)'),
                                      $this->subject->nickname)),
                     new Feed(Feed::ATOM,
                              common_local_url('ApiTimelineMentions',
                                               array(
                                                    'id' => $this->subject->nickname,
                                                    'format' => 'atom')),
                              // TRANS: Link for feed with mentions of a user.
                              // TRANS: %s is a user nickname.
                              sprintf(_('Mention feed for %s (Atom)'),
                                    $this->subject->nickname)));
    }

    function isReadOnly($args)
    {
        return true;
    }
}
