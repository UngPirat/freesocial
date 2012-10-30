<?php
/*
 *  Name: Remote Profile
 *  Type: noticelist
 */
	$this->out->elementStart('div', array('id'=>'content'));
	$this->out->element('h2', 'content-title', $this->get_title());
	$this->content('settings');
	$this->out->elementEnd('div');

    $this->box('aside-settings');
