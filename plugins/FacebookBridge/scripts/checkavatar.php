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

$shortoptions = 'l:f:';
$longoptions = array('local=', 'foreign=');


require_once INSTALLDIR . '/scripts/commandline.inc';
require_once INSTALLDIR . '/classes/Avatar.php';
require_once INSTALLDIR . '/plugins/FacebookBridge/classes/FacebookImport.php';

if (!have_option('l', 'local') || !have_option('f', 'foreign')) {
    echo <<<FOREIGN_PROFILE_CHECKAVATAR
checkavatar.php -l user_id -f foreign_id
Check a profile avatar and update if necessary.

    -l --local     local user id
    -f --foreign   foreign user id


FOREIGN_PROFILE_CHECKAVATAR;
    exit(1);
}
$local = get_option_value('l', 'local');
$foreign = get_option_value('f', 'foreign');

FacebookImport::checkAvatar($local, $foreign);
