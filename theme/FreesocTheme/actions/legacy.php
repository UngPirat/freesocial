<?php
/*
 *  Name: Remote Profile
 */
	$this->box('header');

	$this->out->elementStart('article', array('id'=>'content', 'class'=>'legacy'));
	$this->out->element('h2', 'content-title', $this->get_title());

	$this->action->showContent();

	$this->out->elementEnd('article');

    $this->box('aside');
    $this->box('footer');
?>
