<?php
	$this->box('aside-showstream');

    $this->widget('NoticeList', array('list'=>$this->action->notice, 'title'=>_m('Latest posts'), 'widgetId'=>'stream'));

