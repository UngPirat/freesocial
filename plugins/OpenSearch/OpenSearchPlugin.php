<?php
/**
 * StatusNet - the distributed open-source microblogging tool
 * Copyright (C) 2009, StatusNet, Inc.
 *
 * OpenSearch as a plugin
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
 * @author    Evan Prodromou <evan@status.net>
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

/*
 * OpenSearch plugin lifts the functionality out from core
 *
 * @category  Plugin
 * @package   StatusNet
 * @author    Evan Prodromou <evan@status.net>
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @copyright 2012 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html AGPL 3.0
 * @link      http://status.net/
 */
class OpenSearchPlugin extends Plugin
{
    function onAutoload($cls)
    {
        $dir = dirname(__FILE__);

        switch ($cls)
        {
        case 'OpensearchAction':
            include_once $dir . '/actions/' . strtolower(mb_substr($cls, 0, -6)) . '.php';
            return false;
        default:
            return true;
        }
    }

    function onRouterInitialized($m)
    {
        $m->connect('opensearch/people', array('action' => 'opensearch',
                                               'type' => 'people'));
        $m->connect('opensearch/notice', array('action' => 'opensearch',
                                               'type' => 'notice'));
        return true;
    }

    function onEndShowHeadElements($action)
    {
        $action->element('link', array('rel' => 'search',
                                     'type' => 'application/opensearchdescription+xml',
                                     'href' =>  common_local_url('opensearch', array('type' => 'people')),
                                     'title' => common_config('site', 'name').' People Search'));
        $action->element('link', array('rel' => 'search', 'type' => 'application/opensearchdescription+xml',
                                     'href' =>  common_local_url('opensearch', array('type' => 'notice')),
                                     'title' => common_config('site', 'name').' Notice Search'));
        return true;
    }

    function onPluginVersion(&$versions)
    {
        $versions[] = array('name' => 'OpenSearch',
                            'version' => STATUSNET_VERSION,
                            'author' => 'Evan Prodromou, Mikael Nordfeldth',
                            'homepage' => 'http://status.net/wiki/Plugin:OpenSearch',
                            'rawdescription' =>
                          // TRANS: Plugin description.
                            _m('A sample plugin to show basics of development for new hackers.'));
        return true;
    }
}
