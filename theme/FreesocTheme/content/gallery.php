<?php

	$this->box('aside-showprofile');

	$this->out->elementStart('section', array('id'=>'stream', 'class'=>'legacy gallery'));
	$this->action->showContent();
	$this->out->elementEnd('section');
