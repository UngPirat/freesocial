<?php
/*
 *  Name: New Notice
 */
	$this->out->elementStart('article', array('id'=>'content'));
    $this->out->element('h2', 'content-title', $this->get_title());

	$this->content($this->get_template());

	$this->out->elementEnd('article');
