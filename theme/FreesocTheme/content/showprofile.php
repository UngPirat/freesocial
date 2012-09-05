<?php
	$this->out->element('h2', null, $this->get_title());
	$this->box('aside-showprofile');

    $this->widget('Profilestream', array('profile'=>$this->action->profile, 'title'=>_m('Latest posts'), 'widgetId'=>'stream'));

	$this->widget('GroupList', array('profile'=>$this->action->profile, 'title'=>_m('Group memberships'), 'widgetId'=>'groups'));
	$this->widget('AttachmentList', array('profile'=>$this->action->profile, 'title'=>_m('Latest attachments'), 'widgetId'=>'attachments'));
	$this->widget('FavoriteList', array('profile'=>$this->action->profile, 'title'=>_m('Favorite posts'), 'widgetId'=>'favorites'));

