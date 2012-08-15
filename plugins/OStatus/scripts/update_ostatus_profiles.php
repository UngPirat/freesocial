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

$shortoptions = 'u:a';
$longoptions = array('uri=', 'all');

$helptext = <<<UPDATE_OSTATUS_PROFILES
update_ostatus_profiles.php [options]
Refetch / update OStatus profile info and avatars. Useful if you
do something like accidentally delete your avatars directory when
you have no backup.

    -u --uri OStatus profile URI to update
    -a --all update all


UPDATE_OSTATUS_PROFILES;

require_once INSTALLDIR . '/scripts/commandline.inc';

/*
 * Hacky class to remove some checks and get public access to
 * protected mentods
 */
class LooseOstatusProfile extends Ostatus_profile
{
    /**
     * Download and update given avatar image
     *
     * @param string $url
     * @throws Exception in various failure cases
     */
    public function updateAvatar($url)
    {
        if (!common_valid_http_url($url)) {
            // TRANS: Server exception. %s is a URL.
            throw new ServerException(sprintf(_m('Invalid avatar URL %s.'), $url));
        }

        if ($this->isGroup()) {
            $self = $this->localGroup();
        } else {
            $self = $this->localProfile();
        }
        if (!$self) {
            throw new ServerException(sprintf(
                // TRANS: Server exception. %s is a URI.
                _m('Tried to update avatar for unsaved remote profile %s.'),
                $this->uri));
        }

        // @fixme this should be better encapsulated
        // ripped from oauthstore.php (for old OMB client)
        $temp_filename = tempnam(sys_get_temp_dir(), 'listener_avatar');
        try {
            if (!copy($url, $temp_filename)) {
                // TRANS: Server exception. %s is a URL.
                throw new ServerException(sprintf(_m('Unable to fetch avatar from %s.'), $url));
            }

            if ($this->isGroup()) {
                $id = $this->group_id;
            } else {
                $id = $this->profile_id;
            }
            // @fixme should we be using different ids?
            $imagefile = new ImageFile($id, $temp_filename);
            $filename = Avatar::filename($id,
                                         image_type_to_extension($imagefile->type),
                                         null,
                                         common_timestamp());
            rename($temp_filename, Avatar::path($filename));
        } catch (Exception $e) {
            unlink($temp_filename);
            throw $e;
        }
        // @fixme hardcoded chmod is lame, but seems to be necessary to
        // keep from accidentally saving images from command-line (queues)
        // that can't be read from web server, which causes hard-to-notice
        // problems later on:
        //
        // http://status.net/open-source/issues/2663
        chmod(Avatar::path($filename), 0644);

        $self->setOriginal($filename);

        $orig = clone($this);
        $this->avatar = $url;
        $this->update($orig);
    }

    /**
     * Look up and if necessary create an Ostatus_profile for the remote entity
     * with the given profile page URL. This should never return null -- you
     * will either get an object or an exception will be thrown.
     *
     * @param string $profile_url
     * @return Ostatus_profile
     * @throws Exception on various error conditions
     * @throws OStatusShadowException if this reference would obscure a local user/group
     */
    public static function updateProfileURL($profile_url, $hints=array())
    {
        $oprofile = null;

        $hints['profileurl'] = $profile_url;

        // Fetch the URL
        // XXX: HTTP caching

        $client = new HTTPClient();
        $client->setHeader('Accept', 'text/html,application/xhtml+xml');
        $response = $client->get($profile_url);

        if (!$response->isOk()) {
            // TRANS: Exception. %s is a profile URL.
            throw new Exception(sprintf(_('Could not reach profile page %s.'),$profile_url));
        }

        // Check if we have a non-canonical URL

        $finalUrl = $response->getUrl();

        if ($finalUrl != $profile_url) {
            $hints['profileurl'] = $finalUrl;
        }

        // Try to get some hCard data

        $body = $response->getBody();

        $hcardHints = DiscoveryHints::hcardHints($body, $finalUrl);

        if (!empty($hcardHints)) {
            $hints = array_merge($hints, $hcardHints);
        }

        // Check if they've got an LRDD header

        $lrdd = LinkHeader::getLink($response, 'lrdd', 'application/xrd+xml');

        if (!empty($lrdd)) {

            $xrd = Discovery::fetchXrd($lrdd);
            $xrdHints = DiscoveryHints::fromXRD($xrd);

            $hints = array_merge($hints, $xrdHints);
        }

        // If discovery found a feedurl (probably from LRDD), use it.

        if (array_key_exists('feedurl', $hints)) {
            return self::ensureFeedURL($hints['feedurl'], $hints);
        }

        // Get the feed URL from HTML

        $discover = new FeedDiscovery();

        $feedurl = $discover->discoverFromHTML($finalUrl, $body);

        if (!empty($feedurl)) {
            $hints['feedurl'] = $feedurl;
            return self::ensureFeedURL($feedurl, $hints);
        }

        // TRANS: Exception. %s is a URL.
        throw new Exception(sprintf(_m('Could not find a feed URL for profile page %s.'),$finalUrl));
    }

    /**
     * Look up, and if necessary create, an Ostatus_profile for the remote
     * entity with the given webfinger address.
     * This should never return null -- you will either get an object or
     * an exception will be thrown.
     *
     * @param string $addr webfinger address
     * @return Ostatus_profile
     * @throws Exception on error conditions
     * @throws OStatusShadowException if this reference would obscure a local user/group
     */
    public static function updateWebfinger($addr)
    {
        $disco = new Discovery();

        try {
            $xrd = $disco->lookup($addr);
        } catch (Exception $e) {
            // Save negative cache entry so we don't waste time looking it up again.
            // @fixme distinguish temporary failures?
            self::cacheSet(sprintf('ostatus_profile:webfinger:%s', $addr), null);
            // TRANS: Exception.
            throw new Exception(_m('Not a valid webfinger address.'));
        }

        $hints = array('webfinger' => $addr);

        $dhints = DiscoveryHints::fromXRD($xrd);

        $hints = array_merge($hints, $dhints);

        // If there's an Hcard, let's grab its info
        if (array_key_exists('hcard', $hints)) {
            if (!array_key_exists('profileurl', $hints) ||
                $hints['hcard'] != $hints['profileurl']) {
                $hcardHints = DiscoveryHints::fromHcardUrl($hints['hcard']);
                $hints = array_merge($hcardHints, $hints);
            }
        }

        // If we got a feed URL, try that
        if (array_key_exists('feedurl', $hints)) {
            try {
                common_log(LOG_INFO, "Discovery on acct:$addr with feed URL " . $hints['feedurl']);
                $oprofile = self::ensureFeedURL($hints['feedurl'], $hints);
                self::cacheSet(sprintf('ostatus_profile:webfinger:%s', $addr), $oprofile->uri);
                return $oprofile;
            } catch (Exception $e) {
                common_log(LOG_WARNING, "Failed creating profile from feed URL '$feedUrl': " . $e->getMessage());
                // keep looking
            }
        }

        // If we got a profile page, try that!
        if (array_key_exists('profileurl', $hints)) {
            try {
                common_log(LOG_INFO, "Discovery on acct:$addr with profile URL $profileUrl");
                $oprofile = self::ensureProfileURL($hints['profileurl'], $hints);
                self::cacheSet(sprintf('ostatus_profile:webfinger:%s', $addr), $oprofile->uri);
                return $oprofile;
            } catch (OStatusShadowException $e) {
                // We've ended up with a remote reference to a local user or group.
                // @fixme ideally we should be able to say who it was so we can
                // go back and refer to it the regular way
                throw $e;
            } catch (Exception $e) {
                common_log(LOG_WARNING, "Failed creating profile from profile URL '$profileUrl': " . $e->getMessage());
                // keep looking
                //
                // @fixme this means an error discovering from profile page
                // may give us a corrupt entry using the webfinger URI, which
                // will obscure the correct page-keyed profile later on.
            }
        }
        throw new Exception(sprintf(_m('Could not find a valid profile for "%s".'),$addr));
    }
}

function pullOstatusProfile($uri) {

    $oprofile = null;

    if (Validate::email($uri)) {
        $oprofile = LooseOstatusProfile::updateWebfinger($uri);
    } else if (Validate::uri($uri)) {
        $oprofile = LooseOstatusProfile::updateProfileURL($uri);
    } else {
        print "Sorry, we could not reach the address: $uri\n";
        return false;
    }

   return $oprofile;
}

$quiet = have_option('q', 'quiet');

$lop = new LooseOstatusProfile();

if (have_option('u', 'uri')) {
    $lop->uri = get_option_value('u', 'uri');
} else if (!have_option('a', 'all')) {
    show_help();
    exit(1);
}

$cnt = $lop->find();

if (!empty($cnt)) {
    if (!$quiet) { echo "Found {$cnt} OStatus profiles:\n"; }
} else {
    if (have_option('u', 'uri')) {
        if (!$quiet) { echo "Couldn't find an existing OStatus profile with that URI.\n"; }
    } else {
        if (!$quiet) { echo "Couldn't find any existing OStatus profiles.\n"; }
    }
    exit(0);
}

while($lop->fetch()) {
    if (!$quiet) { echo "Updating OStatus profile '{$lop->uri}' ... "; }
    try {
        $oprofile = pullOstatusProfile($lop->uri);

        if (!empty($oprofile)) {
            $orig = clone($lop);
            $lop->avatar = $oprofile->avatar;
            $lop->update($orig);
            $lop->updateAvatar($oprofile->avatar);
            if (!$quiet) { print "Done.\n"; }
        }
    } catch (Exception $e) {
        if (!$quiet) { print $e->getMessage() . "\n"; }
        common_log(LOG_WARN, $e->getMessage(), __FILE__);
        // continue on error
    }
}

if (!$quiet) { echo "OK.\n"; }
