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

$shortoptions = 'f:s:';
$longoptions = array('foreign_id=');


require_once INSTALLDIR . '/scripts/commandline.inc';
require_once INSTALLDIR . '/plugins/FacebookService/FacebookServicePlugin.php';

if (!have_option('f', 'foreign_id') || !have_option('s', 'secret')) {
    echo <<<ADD_FOREIGN_USER
takeover.php -u id
Refetch / update OStatus profile info and avatars. Useful if you
do something like accidentally delete your avatars directory when
you have no backup.

    -f --foreign_id   foreign user id
    -s --secret       foreign link secret credentials


ADD_FOREIGN_USER;
    exit(1);
}
$foreign_id = get_option_value('f');
$credentials = get_option_value('s');

$fsrv = new FacebookService;

echo "adding $foreign_id";
$fsrv->addForeignUser($foreign_id, $credentials);
