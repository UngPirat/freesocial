#!/usr/bin/env php
<?php
/*
 * StatusNet - the distributed open-source microblogging tool
 * Copyright (C) 2010, StatusNet, Inc.
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

$helptext = <<<ENDOFHELP
USAGE: migrate_to_foreign_notice_map.php

Initializes the foreign_notice_map table with existing Twitter synch
data. This is done in two steps, one where the old Twitter bridge
before version 0.9.5 is taken care of, and one where notice_to_status
migrates to foreign_notice_map.

ENDOFHELP;

require_once INSTALLDIR.'/scripts/commandline.inc';

// We update any notices that may have come in from
// Twitter that we don't have a status_id for. Note that
// this won't catch notices that originated at this StatusNet site.

/*
    I'll throw this in here before I forget. To migrate Twitter_synch_status
        to Foreign_sync_status you do this single command:
    INSERT INTO foreign_sync_status
        (foreign_id, service_id, timeline, last_id, created, modified)
        SELECT foreign_id, 1, timeline, last_id, created, modified
        FROM twitter_synch_status;
*/

$n = new Notice();

$n->query('SELECT notice.id, notice.uri ' .
          'FROM notice LEFT JOIN foreign_notice_map ' .
          'ON notice.id = foreign_notice_map.notice_id ' .
          'WHERE notice.source = "twitter" ' .
          'AND foreign_notice_map.foreign_id IS NULL');

while ($n->fetch()) {
    if (preg_match('/^https?:\/\/twitter.com(\/#!)?\/[\w_\.]+\/status\/(\d+)$/', $n->uri, $match)) {
        $status_id = ($match[1] == '/#!' ? $match[2] : $match[1]);
        try {
            Foreign_notice_map::saveNew($n->id, $status_id, TWITTER_SERVICE);
        } catch (Exception $e) {
            echo 'Could not map notice '.$n->id.': '.$e->getMessage()."\n";
        }
    }
}
