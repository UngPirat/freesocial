<?php
/*
 *  Name: Remote Profile
 *  Type: noticelist
 */
	$this->box('header');

	$this->elementStart('div', array('id'=>'content'));
	$this->out->element('h2', 'content-title', $this->get_title());
	$this->content('noticelist');
	$this->elementEnd('div');

    $this->box('aside');
    $this->box('footer');
?>
