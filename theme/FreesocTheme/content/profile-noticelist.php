<?php
	$this->box('aside-showprofile');
	
	if (is_a($this->action->notice, 'Notice')) {
		$this->widget('NoticeList', array('list'=>array($this->action->notice)));
	} else {
		$this->widget('NoticeList', array('list'=>$this->action->notice,'widgetId'=>'stream'));
	}
/*
	$loop = $this->loop(array('list'=>$this->action->notice));

	try {
		$pages = $loop->get_paging(isset($this->action->args['page']) ? $this->action->args['page'] : null);
	} catch (Exception $e) {
        $pages = array();
	}

    $this->pagination($pages);

    do {
		NoticeWidget::run(array('notice'=>$loop->current()));
    } while ($loop->next());

    $this->pagination($pages);
*/
