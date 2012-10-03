#!/usr/bin/env php
<?php
/*
 * StatusNet - a distributed open-source microblogging tool
 * Copyright (C) 2008, 2009, StatusNet, Inc.
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

define('INSTALLDIR', realpath(dirname(__FILE__) . '/..'));

$shortoptions = 'i::n::y';
$longoptions = array('id=', 'nickname=', 'yes');

$helptext = <<<END_OF_DELETEUSER_HELP
deleteuser.php [options]
deletes a profile from the database

  -i --id       ID of the profile
  -y --yes      do not wait for confirmation

END_OF_DELETEUSER_HELP;

require_once INSTALLDIR.'/scripts/commandline.inc';

if (have_option('i', 'id')) {
    $id = get_option_value('i', 'id');
    $profile = Profile::staticGet('id', $id);
    if (empty($profile)) {
        print "Can't find profile with ID $id\n";
        exit(1);
    }
} else {
    print "You must provide a profile id\n";
    exit(1);
}

if (!have_option('y', 'yes')) {
    print "About to PERMANENTLY delete notices from profile '{$profile->nickname}' ({$profile->id}). Are you sure? [y/N] ";
    $response = fgets(STDIN);
    if (strtolower(trim($response)) != 'y') {
        print "Aborting.\n";
        exit(0);
    }
}

print "Deleting...";
$notice = new Notice;
$notice->profile_id = $profile->id;
if (!$notice->find()) {
    print "no notices found";
    exit (0);
}
while($notice->fetch()) {
    echo "{$notice->id}: {$notice->content}\n";
    $notice->delete();
}
