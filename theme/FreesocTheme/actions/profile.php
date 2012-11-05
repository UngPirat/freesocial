<?php
/*
 *  Name: Profile
 */
    $this->out->elementStart('article', array('id'=>'content'));
    $this->out->element('h2', 'content-title', $this->get_title());

	if (!$this->is_action('showstream') && empty($this->action->profiles)) {
		// this should mean we reached the main 'profile' action
		$this->box('nav-profile');
		$this->box('aside-profile');
		$this->content('profile');
	} elseif (!empty($this->action->profiles)) {
		$this->box('aside-profile');
		$this->content('profilelist');
	} else {
		$this->box('nav-profile');
		$this->box('aside-profile');
	    $this->content('noticelist');
	}

    $this->out->elementEnd('article');
