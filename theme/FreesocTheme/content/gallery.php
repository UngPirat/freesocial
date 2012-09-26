<?php

	$this->box('aside-showstream');

	$this->out->elementStart('section', array('id'=>'stream', 'class'=>'legacy gallery'));
	$this->action->showContent();
	$this->out->elementEnd('section');
