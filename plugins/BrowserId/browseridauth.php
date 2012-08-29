<?php
/**
 * StatusNet, the distributed open-source microblogging tool
 *
 * Class for doing OAuth authentication against Twitter
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
 * @category  Plugin
 * @package   StatusNet
 * @author    Zach Copley <zach@status.net>
 * @author    Julien C <chaumond@gmail.com>
 * @copyright 2009-2010 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET')) {
    exit(1);
}

/**
 * Class for doing BrowserID Authentication
 *
 * @category Plugin
 * @package  StatusNet
 * @author   Mikael Nordfeldth <mmn@hethane.se>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 *
 */
class BrowseridauthAction extends Action {
	function __construct() {
	}
    function handle($args) {
        parent::handle($args);
        if (common_is_real_login()) {
            // TRANS: Client error message trying to log on with OpenID while already logged on.
            $this->clientError(_m('Already logged in.'));
		}
		$assertion = $this->trimmed('assertion');
		if (!empty($assertion)) {
			common_log('BROWSERID: '.$assertion);
			$result = $this->http_request('https://browserid.org/verify',
						array(), 'POST', array(
							'assertion'=>urlencode($assertion),
							'audience'=>common_local_url())
						);
			common_log('BROWSERID: '.$result);
			echo "result: $result";
die;			return true;
		}
	}
}
