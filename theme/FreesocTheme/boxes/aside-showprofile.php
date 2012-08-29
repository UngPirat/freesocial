<?php
	$this->out->elementStart('aside', array('id'=>'profile'));

	$this->widget('Profile', array('profile'=>$this->action->profile, 'out'=>$this->out));
	$this->widget('Profilestream', array('profile'=>$this->action->profile, 'out'=>$this->out, 'title'=>_m('Latest posts')));

	$this->out->elementEnd('aside');
	$this->out->flush();
?>
