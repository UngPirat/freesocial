<?php
/**
 * StatusNet, the distributed open-source microblogging tool
 *
 * Plugin that uses Imap for notice storage and retrieval
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
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @copyright 2012 StatusNet Inc. http://status.net/
 * @copyright 2012 Free Software Foundation, Inc http://www.fsf.org
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

/**
 * Plugin that uses Imap for notice storage and retrieval
 *
 * @category  Plugin
 * @package   StatusNet
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @copyright 2012 StatusNet, Inc. http://status.net/
 * @copyright 2012 Free Software Foundation, Inc http://www.fsf.org
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */
class StorageImapPlugin extends Plugin
{
    public $host;
    public $user;
    public $pass;
    public $port=143;
    public $use_ssl=true;
    public $exclusive = false;

    function initialize() {
        if ( empty($this->host) ) {
            // TRANS: Exception thrown when configuration of the IMAP plugin is incorrect.
            throw new Exception(_m('At least an Imap host must be specified.'));
        }
        return true;
    }

    /**
     * Load related modules when needed
     *
     * @param string $cls Name of the class to be loaded
     *
     * @return boolean hook value; true means continue processing, false means stop.
     */
    function onAutoload($cls)
    {
        switch ($cls) {
        case 'rcube_imap':
        case 'rcube_imap_generic':
            require_once __DIR__ . "/extlib/rcube/$cls.php";
            return false;
        default:
            return true;
        }
    }


    /**
     * Event handler for notice saves; copies notice data
	 * to the configured Imap server
     *
     * @param Notice $notice The notice being saved
     *
     * @return bool hook result code
     */
    function onStartNoticeSave($notice)
    {
		//connect to imap and store the object as a message with headers etc.
		if ($rcube = $this->imap_connect()) {
			$rcube->save_message($notice->profile_id, $notice->content, 'Content-Type: x-statusnet/notice');
		}
        return !$this->exclusive;
    }

    /**
     * Event handler for registration attempts; rejects the registration
     * if email field is missing.
     *
     * @param Action $action Action being executed
     *
     * @return bool hook result code
     */
    function onStartRegisterUser(&$user, &$profile)
    {
        $email = $user->email;

        if (empty($email)) {
            // TRANS: Client exception thrown when trying to register without providing an e-mail address.
            throw new ClientException(_m('You must provide an email address to register.'));
        }

        return true;
    }

    /**
     * Add version information for this plugin.
     *
     * @param array &$versions Array of associative arrays of version data
     *
     * @return boolean hook value
     */
    function onPluginVersion(&$versions)
    {
        $versions[] =
          array('name' => 'Store notices on Imap',
                'version' => STATUSNET_VERSION,
                'author' => 'Mikael Nordfeldth',
                'homepage' =>
                'http://status.net/wiki/Plugin:StorageImap',
                'rawdescription' =>
                // TRANS: Plugin description.
                _m('Stores notice data on Imap account.'));

        return true;
    }

}
