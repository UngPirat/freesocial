<?php
	$this->out->element('h2', null, $this->get_title());
	$this->box('aside-showprofile');
	$this->widget('Grouplist', array('profile'=>$this->action->profile, 'out'=>$this->out, 'title'=>_m('Group memberships'), 'widgetId'=>'groups'));
	$this->widget('Attachmentlist', array('profile'=>$this->action->profile, 'out'=>$this->out, 'title'=>_m('Latest attachments'), 'widgetId'=>'attachments'));
	$this->widget('Favoritelist', array('profile'=>$this->action->profile, 'out'=>$this->out, 'title'=>_m('Favorite posts'), 'widgetId'=>'favorites'));

