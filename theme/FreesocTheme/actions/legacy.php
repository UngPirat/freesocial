<?php
/*
 *  Name: Legacy
 */
	$this->box('header');

	$this->out->elementStart('article', array('id'=>'content', 'class'=>'legacy'.($this->is_single()?' single':'')));
    $this->out->element('h2', 'content-title', $this->get_title());

	if (isset($this->action->notice) &&
			(isset($this->action->user) || isset($this->action->subject))) {
		if (isset($this->action->user)) {
			$this->action->subject = $this->action->user->getProfile();
		}
		$this->content('gallery');
	} elseif (isset($this->action->notice) || isset($this->action->notices)) {
		$this->content('noticelist');
	} elseif ($this->get_template() != 'legacy') {
		$this->content($this->get_template());
	} else {
		$this->action->showContent();
	}

	$this->out->elementEnd('article');

    $this->box('footer');
?>
