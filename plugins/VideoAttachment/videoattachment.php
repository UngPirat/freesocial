<?php

/**
 * StatusNet, the distributed open-source microblogging tool
 *
 * widget for displaying a list of notice attachments
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
 * @category  UI
 * @package   StatusNet
 * @author    Evan Prodromou <evan@status.net>
 * @author    Sarven Capadisli <csarven@status.net>
 * @copyright 2008 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

/**
 * Extension to the Attachment class, to showRepresentation() for video
 * using native browser player, using fallback on <object>.
 */
class MediaAttachment extends Attachment {

	function showRepresentation() {
		$player = array();

		switch ($type = $this->attachment->mimetype) {
		case 'video':
			$player['poster'] = $this->getThumbInfo()->url;
		case 'audio':
			break;
		default:
			throw new Exception(_('Unknown media attachment type'));
			return;
		}
		$player['id']       = $type.'-'.$this->attachment->id;
		$player['class']    = 'media-js';
		$player['controls'] = 'controls';
		$player['preload']  = 'auto';

        $this->out->elementStart('div', array('class' => 'video-player'));
        $this->out->elementStart($type, $player);	// $type is video/audio
        $this->out->element('source', array('src' => $this->attachment->url,
											'type' => $this->attachment->mimetype));

        $this->out->elementEnd($type);
        $this->out->elementEnd('div');
	}

}
