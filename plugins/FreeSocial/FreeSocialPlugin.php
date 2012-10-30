<?php
/**
 * StatusNet - the distributed open-source microblogging tool
 * Copyright (C) 2010-2011, StatusNet, Inc.
 *
 * Converts your site to one with many features imported from Free & Social
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
 * @category  Plugin
 * @package   StatusNet
 * @author    Zach Copley <zach@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html AGPL 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET')) {
    exit(1);
}

class FreeSocialPlugin extends Plugin {
    function initialize() {
		return true;
    }

    function onAutoload($cls) {
		$dir = dirname(__FILE__);

        switch ($cls) {
		default:
			break;
        }
		return true;
    }

	function onEndCloseNoticeListItemElement($nli) {
	}

    function onEndPrimaryNav($action) {
		if ( !common_logged_in() ) {
            $action->menuItem(
				common_local_url('twitterauthorization', null, array('signin' => true)),
                _m('MENU', 'Login with Twitter'),
                _m('Login or register using Twitter.'),
               'twitterlogin' === $action->trimmed('action')
            );

            $action->menuItem(
                // TRANS: Menu item for "Facebook" login.
                common_local_url('facebooklogin', null, array('signin' => true)),
                _m('Login with Facebook'),
                // TRANS: Menu title for "Facebook" login.
                _m('Login or register using Facebook.'),
               'facebooklogin' === $action->trimmed('action')
            );
        }
    }

    function onPluginVersion(&$versions) {
        $versions[] = array(
            'name' => 'Free & Social',
            'version' => STATUSNET_VERSION,
            'author' => 'Mikael Nordfeldth',
            'homepage' => 'https://freesocial.org/',
            'rawdescription' =>
             // TRANS: Plugin description.
            _m('A plugin to get features from Free & Social')
        );

        return true;
    }

	function onStartAddressData($out) {
        if (common_config('singleuser', 'enabled')) {
            $user = User::singleUser();
            $url = common_local_url('showstream',
                                    array('nickname' => $user->nickname));
        } else if (common_logged_in()) {
            $user = common_current_user();
            $url = common_local_url('timeline', array('nickname' => $user->nickname));
        } else {
			$acct = null;
            $url = common_local_url('public');
        }

        if (StatusNet::isHTTPS()) {
            $logoUrl = common_config('site', 'ssllogo');
            if (empty($logoUrl)) {
                // if logo is an uploaded file, try to fall back to HTTPS file URL
                $httpUrl = common_config('site', 'logo');
                if (!empty($httpUrl)) {
                    $f = File::staticGet('url', $httpUrl);
                    if (!empty($f) && !empty($f->filename)) {
                        // this will handle the HTTPS case
                        $logoUrl = File::url($f->filename);
                    }
                }
            }
        } else {
            $logoUrl = common_config('site', 'logo');
        }

        if (empty($logoUrl) && file_exists(Theme::file('logo.png'))) {
            // This should handle the HTTPS case internally
            $logoUrl = Theme::path('logo.png');
        }

        if ((common_logged_in() || common_config('singleuser', 'enabled'))
				&& ($domain = parse_url($url, PHP_URL_HOST))) {
			$profile = $user->getProfile();
			$avatarUrl = Avatar::getUrlByProfile($profile, Avatar::STREAM_SIZE);

			$acct = "{$user->nickname}@{$domain}";
			$out->elementStart('a', array('href' => "$url"));
			$out->element('img', array('class' => 'logo photo', 'src'=>$avatarUrl));
			$out->elementStart('span', array('id' => 'acct', 'class' => 'logo', 'href' => "$url"));
			$out->text($acct);
			$out->elementEnd('span');
			$out->elementEnd('a');
        } else {
        	$out->elementStart('a', array('class' => 'url home bookmark',
                                       'href' => $url));

	        if (!empty($logoUrl)) {
    	        $out->element('img', array('class' => 'logo photo',
	                                        'src' => $logoUrl,
    	                                    'alt' => common_config('site', 'name')));
        	}
	        $out->text(' ');
	        $out->element('span', array('class' => 'fn org'), common_config('site', 'name'));
        	$out->elementEnd('a');
		}

		return false;
	}
}
