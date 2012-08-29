<?php
/**
 * StatusNet - the distributed open-source microblogging tool
 * Copyright (C) 2009, StatusNet, Inc.
 *
 * A module to enable sidebars in themes
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
 * @category  Themes
 * @package   StatusNet
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @copyright 2012 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html AGPL 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET')) {
    // This check helps protect against security problems;
    // your code file can't be executed directly from the web.
    exit(1);
}

/**
 * Sidebar plugin
 *
 */
class SidebarPlugin extends Plugin
{
	/*
	 * Every Sidebar is put in this array, and every box in turn has widgets
	 */
    public $sidebars = array();

    /**
     * Initializer for this plugin
     *
     * Plugins overload this method to do any initialization they need,
     * like connecting to remote servers or creating paths or so on.
     *
     * @return boolean hook value; true means continue processing, false means stop.
     */
    function initialize()
    {
		// handle-code? "onSidebarInit" or something
        return true;
    }

    /**
     * Cleanup for this plugin
     *
     * Plugins overload this method to do any cleanup they need,
     * like disconnecting from remote servers or deleting temp files or so on.
     *
     * @return boolean hook value; true means continue processing, false means stop.
     */
    function cleanup()
    {
		// ...this shouldn't be necessary?
        return true;
    }

    /**
     * Database schema setup
     *
     * Plugins can add their own tables to the StatusNet database. Plugins
     * should use StatusNet's schema interface to add or delete tables. The
     * ensureTable() method provides an easy way to ensure a table's structure
     * and availability.
     *
     * By default, the schema is checked every time StatusNet is run (say, when
     * a Web page is hit). Admins can configure their systems to only check the
     * schema when the checkschema.php script is run, greatly improving performance.
     * However, they need to remember to run that script after installing or
     * upgrading a plugin!
     *
     * @see Schema
     * @see ColumnDef
     *
     * @return boolean hook value; true means continue processing, false means stop.
     */
    function onCheckSchema()
    {
        $schema = Schema::get();
        $schema->ensureTable(Sidebar_content::getTableName(), Sidebar_content::schemaDef());
        $schema->ensureTable(Sidebar_setting::getTableName(), Sidebar_setting::schemaDef());
        return true;
    }

    /**
     * Load related modules when needed
     *
     * Most non-trivial plugins will require extra modules to do their work. Typically
     * these include data classes, action classes, widget classes, or external libraries.
     *
     * This method receives a class name and loads the PHP file related to that class. By
     * tradition, action classes typically have files named for the action, all lower-case.
     * Data classes are in files with the data class name, initial letter capitalized.
     *
     * Note that this method will be called for *all* overloaded classes, not just ones
     * in this plugin! So, make sure to return true by default to let other plugins, and
     * the core code, get a chance.
     *
     * @param string $cls Name of the class to be loaded
     *
     * @return boolean hook value; true means continue processing, false means stop.
     */
    function onAutoload($cls)
    {
        $dir = dirname(__FILE__);

        switch ($cls)
        {
        case 'Sidebar':
        case 'Sidebar_content':
        case 'Sidebar_setting':
            include_once $dir . '/classes/'.$cls.'.php';
            return false;
        default:
            return true;
        }
    }

    function onPluginVersion(&$versions)
    {
        $versions[] = array('name' => 'Sidebar',
                            'version' => STATUSNET_VERSION,
                            'author' => 'Mikael Nordfeldth',
                            'homepage' => 'http://status.net/wiki/Plugin:Sidebar',
                            'rawdescription' =>
                          // TRANS: Plugin description.
                            _m('A sidebar plugin for putting widgets in.'));
        return true;
    }
}
