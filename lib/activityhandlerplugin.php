<?php
/**
 * StatusNet - the distributed open-source microblogging tool
 * Copyright (C) 2011, StatusNet, Inc.
 *
 * Superclass for plugins which add Activity types and such
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
 * @copyright 2011 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html AGPL 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET')) {
    // This check helps protect against security problems;
    // your code file can't be executed directly from the web.
    exit(1);
}

/**
 * Superclass for plugins which add Activity types and such
 *
 * @category  Activity
 * @package   StatusNet
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html AGPL 3.0
 * @link      http://status.net/
 */
abstract class ActivityHandlerPlugin extends Plugin
{
    /**
     * Return a list of ActivityStreams object type IRIs
     * which this micro-app handles. Default implementations
     * of the base class will use this list to check if a
     * given ActivityStreams object belongs to us, via
     * $this->isMyNotice() or $this->isMyActivity.
     *
     * All micro-app classes must override this method.
     *
     * @fixme can we confirm that these types are the same
     * for Atom and JSON streams? Any limitations or issues?
     *
     * @return array of strings
     */
    abstract function types();

    /**
     * Return a list of ActivityStreams verb IRIs which
     * this micro-app handles. Default implementations
     * of the base class will use this list to check if a
     * given ActivityStreams verb belongs to us, via
     * $this->isMyNotice() or $this->isMyActivity.
     *
     * All micro-app classes must override this method.
     *
     * @return array of strings
     */
    function verbs() {
        return array(ActivityVerb::POST);
    }

    /**
     * Check if a given notice object should be handled by this micro-app
     * plugin.
     *
     * The default implementation checks against the activity type list
     * returned by $this->types(). You can override this method to expand
     * your checks.
     *
     * @param Notice $notice
     * @return boolean
     */
    function isMyNotice($notice) {
        $types = $this->types();
        $verbs = $this->verbs();
        return ActivityUtils::compareObjectTypes($notice->verb, $verbs) && ActivityUtils::compareObjectTypes($notice->object_type, $types);
    }

    /**
     * Check if a given ActivityStreams activity should be handled by this
     * micro-app plugin.
     *
     * The default implementation checks against the activity type list
     * returned by $this->types(), and requires that exactly one matching
     * object be present. You can override this method to expand
     * your checks or to compare the activity's verb, etc.
     *
     * @param Activity $activity
     * @return boolean
     */
    function isMyActivity($activity) {
        $types = $this->types();
        $verbs = $this->verbs();
        return (count($activity->objects) == 1 &&
                ($activity->objects[0] instanceof ActivityObject) &&
                ActivityUtils::compareObjectTypes($activity->verb, $verbs) &&
                ActivityUtils::compareObjectTypes($activity->objects[0]->type, $types));
    }

    /**
     * Output the HTML for this kind of object in a list
     *
     * @param NoticeListItem $nli The list item being shown.
     *
     * @return boolean hook value
     *
     * @fixme WARNING WARNING WARNING this closes a 'div' that is implicitly opened in BookmarkPlugin's showNotice implementation
     */
    function onStartShowNoticeItem($nli)
    {
        if (!$this->isMyNotice($nli->notice)) {
            return true;
        }

        $adapter = $this->adaptNoticeListItem($nli);

        if (!empty($adapter)) {
            $adapter->showNotice();
            $adapter->showNoticeAttachments();
            $adapter->showNoticeInfo();
            $adapter->showNoticeOptions();
        } else {
            $this->oldShowNotice($nli);
        }

        return false;
    }

    /**
     * Given a notice list item, returns an adapter specific
     * to this plugin.
     *
     * @param NoticeListItem $nli item to adapt
     *
     * @return NoticeListItemAdapter adapter or null
     */
    function adaptNoticeListItem($nli)
    {
      return null;
    }

    function oldShowNotice($nli)
    {
        $out = $nli->out;
        $notice = $nli->notice;

        try {
            $this->showNotice($notice, $out);
        } catch (Exception $e) {
            common_log(LOG_ERR, $e->getMessage());
            // try to fall back
            $out->elementStart('div');
            $nli->showAuthor();
            $nli->showContent();
        }

        $nli->showNoticeLink();
        $nli->showNoticeSource();
        $nli->showNoticeLocation();
        $nli->showContext();
        $nli->showRepeat();

        $out->elementEnd('div');

        $nli->showNoticeOptions();
    }

    function showNotice($notice, $out)
    {
        // TRANS: Server exception thrown when a micro app plugin developer has not done his job too well.
        throw new ServerException(_('You must implement either adaptNoticeListItem() or showNotice().'));
    }
}
