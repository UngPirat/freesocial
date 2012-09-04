<?php
	$this->out->elementStart('aside', array('id'=>'settings'));

	$this->menus(array('Settings', 'Connections'), array('action'=>$this->action));

	$this->out->elementEnd('aside');
	$this->out->flush();
?>
