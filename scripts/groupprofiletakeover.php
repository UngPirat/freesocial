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
groupprofiletakeover.php [options]
deletes a profile's notices AND the profile itself after which a group's data is copied in its place.

  -i --id       ID of the profile
  -y --yes      do not wait for confirmation

END_OF_DELETEUSER_HELP;

require_once INSTALLDIR.'/scripts/commandline.inc';

if (have_option('i', 'id')) {
    $id = get_option_value('i', 'id');
    $profile = Profile::staticGet('id', $id);
    if (empty($profile)) {
        echo "Can't find profile with ID $id\n";
        exit(1);
    }
	$group = User_group::staticGet('id', $id);
	if (empty($group)) {
		echo "Can't find group with ID $id\n";
		exit(1);
	}
} else {
    echo "You must provide a profile id\n";
    exit(1);
}

if (!have_option('y', 'yes')) {
    echo "About to PERMANENTLY delete notices from profile AND profile itself '{$profile->nickname}' ({$profile->id}) to replace it with data from group {$group->nickname}. Are you sure? [y/N] ";
    $response = fgets(STDIN);
    if (strtolower(trim($response)) != 'y') {
        echo "Aborting.\n";
        exit(0);
    }
}

echo "Deleting notices...\n";
$notice = new Notice;
$notice->profile_id = $profile->id;
if (!$notice->find()) {
    echo "no notices found\n";
} else {
	while($notice->fetch()) {
    	echo "{$notice->id}: {$notice->content}\n";
	    $notice->delete();
	}
}
echo "Deleting profile\n";
$profile->delete();

echo "Copying group data\n";
$profile = new Profile();
$profile->type = 2;
$p2gmap = array('nickname'=>'nickname', 'fullname'=>'fullname', 'profileurl'=>'profileurl', 'homepage'=>'homepage', 'bio'=>'description', 'location'=>'location', 'created'=>'created', 'modified'=>'modified');
foreach ($p2gmap as $pkey=>$gkey) {
	$profile->$pkey = $group->$gkey;
}
echo "Inserting profile for {$group->nickname}\n";
$id = $profile->insert();
if (empty($id)) {
	echo "ERROR inserting profile\n";
	exit(1);
}
$profile->query('UPDATE profile SET id="'.$group->id.'" WHERE id="'.$id.'"');
echo "done";
