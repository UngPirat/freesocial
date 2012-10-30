<?php
/*
 *  Name: Remote Profile
 *  Type: noticelist
 */
	$this->out->elementStart('div', array('id'=>'content'));
	$this->out->element('h2', 'content-title', $this->get_title());
	$this->content('noticelist');
	$this->out->elementEnd('div');
