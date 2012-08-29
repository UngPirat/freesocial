<?php
/**
 * Laconica, the distributed open-source microblogging tool
 *
 * Plugin to check submitted notices with Mollom
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
 * Mollom is a bayesian spam checker, wrapped into a webservice
 * This plugin is based on the Drupal Mollom module
 *
 * @category  Plugin
 * @package   Laconica
 * @author    Brenda Wallace <brenda@cpan.org>
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 *
 */

if (!defined('STATUSNET')) {
    exit(1);
}

class FlattrPlugin extends Plugin
{
	function onEndShowStatusNetStyles($action) {
		$flattrjs = <<<EOF
/* <![CDATA[ */
    (function() {
        var s = document.createElement('script'), t = document.getElementsByTagName('script')[0];
        s.type = 'text/javascript';
        s.async = true;
        s.src = 'https://freesocial.org/js/flattr-0.6/load.js?mode=auto';
        t.parentNode.insertBefore(s, t);
    })();
/* ]]> */
EOF;
		$action->element('script', array('type'=>'text/javascript'), $flattrjs);
	}
    function onStartPrimaryNav($action) {
		$action->elementStart('li', array('id'=>'nav_flattr'));
/*        $action->element('a', array('class'=>'FlattrButton', 'style'=>'display:none;', 'rev'=>'flattr;button:compact;', 'href'=>'https://freesocial.org/'), null);
		$action->elementStart('noscript');*/
		$action->elementStart('a', array('href'=>'https://flattr.com/thing/546191/Free-Social', 'target'=>'_blank'));
		$action->element('img', array('src'=>'https://freesocial.org/plugins/Flattr/button/flattr-badge-large.png', 'alt'=>'Flattr this', 'title'=>'Flattr this', 'border'=>'0'));
		$action->elementEnd('a');
//		$action->elementEnd('noscript');
		$action->elementEnd('li');
    }
}
