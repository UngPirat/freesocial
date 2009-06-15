<?php
/*
 * Laconica - a distributed open-source microblogging tool
 * Copyright (C) 2008, Controlez-Vous, Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.     See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.     If not, see <http://www.gnu.org/licenses/>.
 */

if (!defined('LACONICA')) {
    exit(1);
}

require_once(INSTALLDIR.'/lib/twitterapi.php');

class TwitapiusersAction extends TwitterapiAction
{

    function show($args, $apidata)
    {
        parent::handle($args);

        if (!in_array($apidata['content-type'], array('xml', 'json'))) {
            $this->clientError(_('API method not found!'), $code = 404);
            return;
        }

        $user = null;
        $email = $this->arg('email');
        $user_id = $this->arg('user_id');

        // XXX: email field deprecated in Twitter's API

        // XXX: Also: need to add screen_name param

        if ($email) {
            $user = User::staticGet('email', $email);
        } elseif ($user_id) {
            $user = $this->get_user($user_id);
        } elseif (isset($apidata['api_arg'])) {
            $user = $this->get_user($apidata['api_arg']);
        } elseif (isset($apidata['user'])) {
            $user = $apidata['user'];
        }

        if (empty($user)) {
            $this->client_error(_('Not found.'), 404, $apidata['content-type']);
            return;
        }

        $twitter_user = $this->twitter_user_array($user->getProfile(), true);

        if ($apidata['content-type'] == 'xml') {
            $this->init_document('xml');
            $this->show_twitter_xml_user($twitter_user);
            $this->end_document('xml');
        } elseif ($apidata['content-type'] == 'json') {
            $this->init_document('json');
            $this->show_json_objects($twitter_user);
            $this->end_document('json');
        } else {

            // This is in case 'show' was called via /account/verify_credentials
            // without a format (xml or json).
            header('Content-Type: text/html; charset=utf-8');
            print 'Authorized';
        }

    }
}
