<?php
if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

class ShowprofileAction extends ShowstreamAction
{
    function prepare($args)
    {
        parent::prepare($args);
        return true;
    }

    function isReadOnly($args)
    {
        return true;
    }

	function title() {
		return sprintf(_m('Profile page for %s'), $this->profile->getFancyName());
	}

    function handle($args)
    {
        $this->showPage();
    }
}
