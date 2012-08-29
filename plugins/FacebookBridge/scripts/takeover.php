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
$longoptions = array('from=', 'to=');


require_once INSTALLDIR . '/scripts/commandline.inc';
require_once INSTALLDIR . '/plugins/FacebookService/FacebookServicePlugin.php';

if (!have_option('f', 'from') || !have_option('t', 'to')) {
    echo <<<FOREIGN_PROFILE_TAKEOVER
takeover.php -f user_id -t foreign_id
Convert all notices from a foreign profile to a local user

    -f --from local user id
    -t --to   foreign profile id


FOREIGN_PROFILE_TAKEOVER;
    exit(1);
}
$from = get_option_value('f', 'from');
$to = get_option_value('t', 'to');

$fsrv = new FacebookService;

echo "moving foreign $from's notices to $to";
$user = User::staticGet('id', $to);
if (empty($user)) throw new Exception('User not found');
try {
    $fuser = $fsrv->getForeignUser($from);
    echo '...go!';
    $fsrv->profileTakeover($to, $from);
} catch (Exception $e) {
    echo $e->getMessage();
}
