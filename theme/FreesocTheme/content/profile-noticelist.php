<?php
	$this->box('aside-showprofile');
	
	if (is_a($this->action->notice, 'Notice')) {
		$this->widget('NoticeList', array('list'=>array($this->action->notice)));
	} else {
		$this->widget('NoticeList', array('list'=>$this->action->notice,'widgetId'=>'stream'));
	}
