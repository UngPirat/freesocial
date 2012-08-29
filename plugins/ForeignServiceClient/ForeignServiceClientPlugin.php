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
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @copyright 2012 Anyone
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET')) {
    exit(1);
}

/**
 * Plugin to add functionality required to map foreign notices
 *
 * This plugin enables the class Foreign_notice_map
 *
 * @category Plugin
 * @package  StatusNet
 * @author   Zach Copley <zach@status.net>
 * @author   Julien C <chaumond@gmail.com>
 * @author   Mikael Nordfeldth <mmn@hethane.se>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 * @link     http://twitter.com/
 */
class ForeignServiceClientPlugin extends Plugin
{
    const VERSION = STATUSNET_VERSION;

    /**
     * Automatically load the PHP file for our class
     *
     * @param Class $cls the class
     *
     * @return boolean hook return
     *
     */
    function onAutoload($cls)
    {
        $dir = dirname(__FILE__);

        switch ($cls) {
        case 'Foreign_group':
        case 'Foreign_notice_map':
            require_once $dir . '/classes/' . $cls . '.php';
            return false;
        default:
            return true;
        }
    }

    /**
     * Database schema setup
     *
     * We maintain a table mapping StatusNet notices to foreign notice ids and services
     *
     * @see Schema
     * @see ColumnDef
     *
     * @return boolean hook value; true means continue processing, false means stop.
     */
    function onCheckSchema()
    {  
        $schema = Schema::get();
        $schema->ensureTable('foreign_group', Foreign_group::schemaDef());
        $schema->ensureTable('foreign_notice_map', Foreign_notice_map::schemaDef());
        return true;
    }
}

abstract class ForeignServiceClient
{
    private $service = null;
    protected $client = null;

    function __construct(array $params) {
        $required = array('service_id');
        foreach ($required as $param) {
            if (empty($params[$param])) {
                throw new Exception("Required parameter '$param' empty");
            }
            $this->$param = $params[$param];
        }

        $this->prepare($params);
    }

    function getService() {
        return $this->service_id;
    }

    abstract function prepare(array $params=array());

// adds or updates a Foreign_user entry
    abstract function addForeignUser($foreign_id, $credentials='', $update=false);
// fetches user object
    abstract function fetchUserData($foreign_id, $credentials='', array $fields=array(), $verify=false);
// handles foreign error messages
    abstract static function handleForeignError($e, $flink);

// move Foreign_user notices to a User
    function profileTakeover($user_id, $foreign_id)
    {
        if (empty($user_id) || empty($foreign_id)) {
            throw new Exception('Bad input data');
        }

        $fuser = $this->getForeignUser($foreign_id);

        // get all foreign users with this user's uri
        $takeover = new Profile();
        $takeover->profileurl = $fuser->uri;
        $takeover->find();

        // for each (possibly duplicate) entry, iterate through notices
        while ($takeover->fetch()) {
            $switch = new Notice();
            $switch->profile_id = $takeover->id;
            $switch->find();
            // ...and change the profile id to our takeover profile
            while ($switch->fetch()) {
                $original = clone($switch);
                $switch->profile_id = $user_id;
                $switch->is_local = 1;	// turn it into a local post
                $switch->update($original);
            }
            // and delete the old profile as we do not need it anymore
            $takeover->delete();
        }
    }

    function getForeignUser($foreign_id) {
        $fuser = Foreign_user::pkeyGet('Foreign_user', array('id'=>$foreign_id, 'service'=>$this->getService()));
        if (empty($fuser)) {
            throw new Exception('Foreign user not found');
        }
        return $fuser;
    }

}
