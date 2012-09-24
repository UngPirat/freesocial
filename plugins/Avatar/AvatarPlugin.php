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
 * @author    Evan Prodromou <evan@status.net>
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @copyright 2012 StatusNet, Inc.
 * @copyright 2012 Free Software Foundation, Inc http://www.fsf.org
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET')) {
    exit(1);
}

/**
 * Plugin for graphical representations of online user profiles
 *
 * @category Plugin
 * @package  StatusNet
 * @author   Evan Prodromou <evan@status.net>
 * @author   Mikael Nordfeldth <mmn@hethane.se>
 * @copyright 2012 Free Software Foundation, Inc http://www.fsf.org
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 * @link     http://openid.net/
 */

class AvatarPlugin extends Plugin
{
    /**
     * Add Avatar-related paths to the router table
     *
     * Hook for RouterInitialized event.
     *
     * @param Net_URL_Mapper $m URL mapper
     *
     * @return boolean hook return
     */
    function onStartInitializeRouter($m)
    {

        return true;
    }

    /**
     * User XRDS output hook
     *
     * Puts the bits of code needed to discover OpenID endpoints.
     *
     * @param Action       $action         Action being executed
     * @param XMLOutputter &$xrdsOutputter Output channel
     *
     * @return boolean hook return
     */
    function onEndUserXRDS($action, &$xrdsOutputter)
    {
        return true;
    }

    /**
     * Autoloader
     *
     * Loads our classes if they're requested.
     *
     * @param string $cls Class requested
     *
     * @return boolean hook return
     */
    function onAutoload($cls)
    {
        switch ($cls)
        {
        case 'AvatarLink':
            require_once dirname(__FILE__) . '/lib/' . strtolower($cls) . '.php';
            return false;
        case 'Avatar':
            require_once dirname(__FILE__) . '/classes/' . $cls . '.php';
            return false;
        default:
            return true;
        }
    }

    /**
     * Data definitions
     *
     * Assure that our data objects are available in the DB
     *
     * @return boolean hook value
     */
    function onCheckSchema()
    {
        $schema = Schema::get();

        $schema->ensureTable('avatar', Avatar::schemaDef());

        return true;
    }

    /**
     * Add our tables to be deleted when a user is deleted
     *
     * @param User  $user    User being deleted
     * @param array &$tables Array of table names
     *
     * @return boolean hook value
     */
    function onUserDeleteRelated($user, &$tables)
    {
        return true;
    }

    /**
     * Add our version information to output
     *
     * @param array &$versions Array of version-data arrays
     *
     * @return boolean hook value
     */
    function onPluginVersion(&$versions)
    {
        $versions[] = array('name' => 'Avatar',
                            'version' => STATUSNET_VERSION,
                            'author' => 'Evan Prodromou, Mikael Nordfeldth',
                            'homepage' => 'http://status.net/wiki/Plugin:Avatar',
                            'rawdescription' =>
                            // TRANS: Plugin description.
                            _m('Enables graphical representation of an online user identity'));
        return true;
    }

    /**
     * Add link in user's XRD file with representative avatar data
     *
     * @param XRD  &$xrd Currently-displaying XRD object
     * @param User $user The user that it's for
     *
     * @return boolean hook value (always true)
     */

    function onEndXrdActionLinks(&$xrd, $user)
    {
        return true;
    }

	// ThemeManager integration
	function onGetAvatar(&$avatar, Profile $profile, $size=Avatar::PROFILE_SIZE, array $args=array())
	{
		try {
			$avatar = Avatar::getByProfile($profile, $size, $size, $args);
		} catch (Exception $e) {
			return true;
		}
		return false;
	}
	function onGetAvatarElement(&$element, Profile $profile, $size=Avatar::PROFILE_SIZE, array $args=array())
	{
		if (!Event::handle('GetAvatarUrl', array(&$avatarUrl, $profile, $size, $args))) {
			$class = isset($args['class']) ? $args['class'] : 'photo avatar';
			$element = array(
					'tag'  => 'img',
					'args' => array('src'=>$avatarUrl, 'class'=>$class),
				);
			return false;
		}
	}
	function onGetAvatarUrl(&$avatarUrl, Profile $profile, $size=Avatar::PROFILE_SIZE, array $args=array())
	{
		try {
			$avatarUrl = Avatar::getUrlByProfile($profile, $size);
		} catch (Exception $e) {
			return true;
		}
		return false;
	}
}
