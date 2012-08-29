<?php
/*
 *  Name: Remote Profile
 */
	$this->box('header');

	$this->out->elementStart('article', array('id'=>'content','class'=>($this->is_single()?'single':'')));

	if ($this->is_single()) :
		if ($this->is_action('showprofile')) {
			$this->content('showprofile');
		} else {
			$this->content('noticelist');
		}
	else :
			$this->content('noticelist');
	endif;
	$this->out->elementEnd('article');

	if (!$this->is_single()) {
    	$this->box('aside');
	}
	$this->out->flush();
    $this->box('footer');
?>
