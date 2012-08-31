<?php
/*
 *  Name: Remote Profile
 */
	$this->box('header');

	$this->out->elementStart($this->is_single()?'article':'div', array('id'=>'content','class'=>($this->is_single()?'single':'')));
	$this->out->element('h2', 'content-title', $this->get_title());
	$this->out->flush();

	if ($this->is_single()) :
		if ($this->is_action('showprofile')) {
			$this->content('showprofile');
		} else {
			$this->content('noticelist');
		}
	else :
			$this->content('noticelist');
	endif;
	$this->out->elementEnd($this->is_single()?'article':'div');
	$this->out->flush();

	if (!$this->is_single()) {
    	$this->box('aside');
	}
	$this->out->flush();
    $this->box('footer');
?>
