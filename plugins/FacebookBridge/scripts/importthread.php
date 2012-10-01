#!/usr/bin/env php
<?php
/*
 * StatusNet - a distributed open-source microblogging tool
 * Copyright (C) 2011, StatusNet, Inc.
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
 */

define('INSTALLDIR', realpath(dirname(__FILE__) . '/../../..'));

$shortoptions = 'f:t:';
$longoptions = array('foreign_id=','thread_id=');


require_once INSTALLDIR . '/scripts/commandline.inc';
require_once INSTALLDIR . '/plugins/FacebookService/FacebookServicePlugin.php';

if (!have_option('f', 'foreign_id') || !have_option('t', 'thread_id')) {
    echo <<<ADD_FOREIGN_USER
importthread.php -f id -t thread_id
Imports a thread from Facebook using a user's credentials.

    -f --foreign_id   foreign user id
    -t --thread_id    thread to import

ADD_FOREIGN_USER;
    exit(1);
}
$foreign_id = get_option_value('f');
$thread_id  = get_option_value('t');

$flink    = Foreign_link::getByForeignID($foreign_id, FACEBOOK_SERVICE);
if (empty($flink) || empty($flink->credentials)) {
	throw new Exception ('no flink or credentials for: '.$foreign_id);
}

$facebook = Facebookclient::getFacebook();
$importer = new FacebookImport($flink->foreign_id);
echo "fetching thread_id $thread_id from Facebook\n";
$thread   = $facebook->api('/'.$thread_id, 'get', array('access_token'=>$flink->credentials));

echo "importing thread_id $thread_id\n";
$data = array('entry'=>$thread);
$importer->importThread($data);
