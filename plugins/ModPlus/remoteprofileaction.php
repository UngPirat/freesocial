<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class RemoteProfileAction extends ShowstreamAction
{
    function prepare($args)
    {
        Action::prepare($args); // skip the ProfileAction code and replace it...

        $id = $this->arg('id');
        $this->profile = Profile::staticGet('id', $id);
		$this->subject = $this->profile;

        $this->tag = $this->trimmed('tag');
        $this->page = ($this->arg('page')) ? ($this->arg('page')+0) : 1;

        if (!$this->profile) {
            // TRANS: Error message displayed when referring to a user without a profile.
            $this->serverError(_m('User has no profile.'));
            return false;
        }

		// redirect to groups that are accidentally accessed this way
		if ($this->profile->isGroup()) {
			$args = array('id'=>$this->profile->id);
            if ($this->page != 1) {
                $args['page'] = $this->page;
            }
			common_redirect(common_local_url('groupbyid', $args), 301);
		}

        $user = User::staticGet('id', $this->profile->id);
        if ($user) {
            // This is a local user -- send to their regular profile.
            $url = common_local_url('profile', array('nickname' => $user->nickname));
            common_redirect($url);
            return false;
        }

        common_set_returnto($this->selfUrl());

        $p = Profile::current();
        if (empty($this->tag)) {
            $stream = new ProfileNoticeStream($this->profile, $p);
        } else {
            $stream = new TaggedProfileNoticeStream($this->profile, $this->tag, $p);
        }
        $this->notice = $stream->getNotices(($this->page-1)*NOTICES_PER_PAGE, NOTICES_PER_PAGE + 1);

        return true;
    }

    function handle($args)
    {
        // skip yadis thingy
        $this->showPage();
    }

    function title()
    {
        $base = $this->profile->getBestName();
        $host = parse_url($this->profile->profileurl, PHP_URL_HOST);
        // TRANS: Remote profile action page title.
        // TRANS: %1$s is a username, %2$s is a hostname.
        return sprintf(_m('%1$s on %2$s'), $base, $host);
    }

    /**
     * Instead of showing notices, link to the original offsite profile.
     */
    function showContent()
    {
        $url = $this->profile->profileurl;
        $host = parse_url($url, PHP_URL_HOST);
        $markdown = sprintf(
                // TRANS: Message on remote profile page.
                // TRANS: This message contains Markdown links in the form [description](link).
                // TRANS: %1$s is a profile nickname, %2$s is a hostname, %3$s is a URL.
                _m('This remote profile is registered on another site; see [%1$s\'s original profile page on %2$s](%3$s).'),
                $this->profile->nickname,
                $host,
                $url);
        $this->raw(common_markup_to_html($markdown));

		parent::showContent();
    }

    function getFeeds()
    {
        // none
    }

    /**
     * Don't do various extra stuff, and also trim some things to avoid crawlers.
     */
    function extraHead()
    {
        $this->element('meta', array('name' => 'robots',
                                     'content' => 'noindex,nofollow'));
    }

    function showLocalNav()
    {
        //skip
    }

    function showSections()
    {
        // skip
    }

    function showStatistics()
    {
        ProfileAction::showStatistics();
        // skip
    }
}
