<?php
/**
 * StatusNet - the distributed open-source microblogging tool
 * Copyright (C) 2011, StatusNet, Inc.
 *
 * Widget showing a drop-down of potential addressees
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
 * @category  Widget
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
 * Widget showing a drop-down of potential addressees
 *
 * @category  Widget
 * @package   StatusNet
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html AGPL 3.0
 * @link      http://status.net/
 */
class ToSelector extends Widget
{
    protected $user;
    protected $to;
    protected $id;
    protected $name;
    protected $private;

    /**
     * Constructor
     *
     * @param HTMLOutputter $out  output context
     * @param User          $user Current user
     * @param mixed         $to   Default selection for addressee
     */
    function __construct($out, $user, $to, $private=false, $id='notice_to', $name='notice_to')
    {
        parent::__construct($out);

        $this->user    = $user;
        $this->to      = $to;
        $this->private = $private;
        $this->id      = $id;
        $this->name    = $name;
    }

    /**
     * Constructor
     *
     * @param HTMLOutputter $out  output context
     * @param User          $user Current user
     * @param mixed         $to   Default selection for addressee
     */
    function show()
    {
        $choices = array();
        $default = 'public:site';

        if (!common_config('site', 'private')) {
            // TRANS: Option in drop-down of potential addressees.
            $choices['public:everyone'] = _m('SENDTO','Everyone');
            $default = 'public:everyone';
        }
        // XXX: better name...?
        // TRANS: Option in drop-down of potential addressees.
        // TRANS: %s is a StatusNet sitename.
        $choices['public:site'] = sprintf(_('My colleagues at %s'), common_config('site', 'name'));

        $groups = $this->user->getGroups();

        while ($groups->fetch()) {
            $value = 'group:'.$groups->id;
            if (($this->to instanceof User_group) && $this->to->id == $groups->id) {
                $default = $value;
            }
            $choices[$value] = $groups->getBestName();
        }

        // XXX: add users...?

        if ($this->to instanceof Profile) {
            $value = 'profile:'.$this->to->id;
            $default = $value;
            $choices[$value] = $this->to->getBestName();
        }

        $this->out->dropdown($this->id,
                             // TRANS: Label for drop-down of potential addressees.
                             _m('LABEL','To:'),
                             $choices,
                             null,
                             false,
                             $default);

        $this->out->elementStart('span', 'checkbox-wrapper');
        $this->out->checkbox('notice_private',
                             // TRANS: Checkbox label in widget for selecting potential addressees to mark the notice private.
                             _('Private?'),
                             $this->private);
        $this->out->elementEnd('span');
    }

    static function fillOptions($action, &$options)
    {
        // XXX: make arg name selectable
        $toArg = $action->trimmed('notice_to');
        $private = $action->boolean('notice_private');

        if (empty($toArg)) {
            return;
        }

        list($prefix, $value) = explode(':', $toArg);
        switch ($prefix) {
        case 'group':
            $options['groups'] = array($value);
            if ($private) {
                $options['scope'] = Notice::GROUP_SCOPE;
            }
            break;
        case 'profile':
            $profile = Profile::staticGet('id', $value);
            $options['mentions'] = array($profile->getUri());
            if ($private) {
                $options['scope'] = Notice::ADDRESSEE_SCOPE;
            }
            break;
        case 'public':
            if ($value == 'everyone' && !common_config('site', 'private')) {
                $options['scope'] = 0;
            } else if ($value == 'site') {
                $options['scope'] = Notice::SITE_SCOPE;
            }
            break;
        default:
            // TRANS: Client exception thrown in widget for selecting potential addressees when an invalid fill option was received.
            throw new ClientException(sprintf(_('Unknown to value: "%s".'),$toArg));
            break;
        }
    }
}
