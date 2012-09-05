<?php
/*
 *  Name: Remote Profile
 */
	$this->box('header');

	$this->out->elementStart('article', array('id'=>'content', 'class'=>'single'));
	$this->out->element('h2', 'content-title', $this->get_title());

	switch ($this->action->args['action']) {
    case 'attachment':
		$this->content($this->action->args['action']);
		break;
	default:
		echo 'unhandled';
	}

	$this->out->elementEnd('article');

    $this->box('footer');
?>
