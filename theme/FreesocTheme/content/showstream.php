<?php
	$this->box('aside-showstream');

    $this->widget('NoticeList', array('list'=>$this->action->notice, 'widgetId'=>'stream'));
