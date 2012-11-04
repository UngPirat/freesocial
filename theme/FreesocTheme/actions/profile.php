<?php
/*
 *  Name: Profile
 */
    $this->out->elementStart('article', array('id'=>'content'));
    $this->out->element('h2', 'content-title', $this->get_title());

	if (!empty($this->action->profiles)) {
		$this->box('aside-showstream');
		$this->content('profilelist');
	} else {
		$this->box('nav-profile');
		$this->box('aside-showstream');
	    $this->content('noticelist');
	}

    $this->out->elementEnd('article');
