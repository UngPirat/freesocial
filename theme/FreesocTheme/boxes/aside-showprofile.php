<?php
	$this->out->elementStart('aside', array('id'=>'profile'));

	$this->widget('Profile', array('profile'=>$this->action->profile));
	$this->widget('Profilestream', array('profile'=>$this->action->profile, 'title'=>_m('Latest posts')));

	$this->out->elementEnd('aside');
	$this->out->flush();
?>
