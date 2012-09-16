<?php
    $this->out->elementStart('aside', array('id'=>'profile'));

    $this->widget('Profile', array('profile'=>$this->action->profile));
	$this->widget('GroupList', array('profile'=>$this->action->profile, 'title'=>_m('Group memberships'), 'widgetId'=>'groups'));
	$this->widget('AttachmentList', array('profile'=>$this->action->profile, 'title'=>_m('Latest attachments'), 'widgetId'=>'attachments'));

	$this->widget('FavoriteList', array('profile'=>$this->action->profile, 'title'=>_m('Favorite posts'), 'widgetId'=>'favorites'));


    $this->out->elementEnd('aside');
