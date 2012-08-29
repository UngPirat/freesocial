<?php
/**
 * StatusNet, the distributed open-source microblogging tool
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
 * @author    Brion Vibber <brion@status.net>
 * @copyright 2009-2010 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET')) {
    exit(1);
}

/**
 * Widget box handling, this basically collects and loads all widgets related to a box id
 *
 * @category Plugin
 * @package  StatusNet
 * @author   Mikael Nordfeldth <mmn@hethane.se>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 * @link     http://twitter.com/
 */
class Sidebar
{
	protected $id = null;
	protected $widgets = array();

	protected $args = array();

	function __construct($name) {
		$this->register($name);
	}

	protected function register($name) {
		if (empty($name)) {
			Sidebar::create($name);
		}

		$content = Sidebar_content::staticGet('name', $name);
		if (!$content) {
			throw new Exception('Could not find sidebar content for '.$name);
		}
		$this->widgets = $content->fetchAll();
	}

	protected function create($name) {
		$content = new Sidebar_content();
		$profile->query("BEGIN");

		$content->name = $name;
		$content->widget = 'Text';
		$content->created = common_sql_now();
		$id = $content->insert();

		if (empty($id)) {
			throw new Exception('Could not create new Sidebar with name "'.$name.'"');
		}
		$setting = new Sidebar_content_setting();
		$setting->content_id = $id;
		$setting->key = 'content';
		$setting->value = 'Property of Sidebar '.$id;
		$result = $setting->insert();

		if (empty($result)) {
			$content->query("ROLLBACK");
		}

		$content->query("COMMIT");
		$content->reload();

		return $content;
	}
}
