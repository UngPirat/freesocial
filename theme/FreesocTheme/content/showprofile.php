<?php
	$this->box('aside-showprofile');

    $this->widget('ProfilestreamList', array('profile'=>$this->action->profile, 'title'=>_m('Latest posts'), 'widgetId'=>'stream'));

