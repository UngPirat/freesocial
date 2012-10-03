<?php
/*
 *  Name: New Notice
 */
	$this->box('header');

	$this->out->elementStart('article', array('id'=>'content', 'class'=>($this->is_single()?'single':'multi')));
    $this->out->element('h2', 'content-title', $this->get_title());

	$this->content($this->get_template());

	$this->out->elementEnd('article');

    $this->box('footer');
