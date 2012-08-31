<?php
	$loop = $this->loop(array('list'=>$this->action->notice), 'Conversation');

	try {
		$pages = $loop->get_paging(isset($this->action->args['page']) ? $this->action->args['page'] : null);
	} catch (Exception $e) {
        $pages = array();
	}

    $this->pagination($pages);

    do {
        $this->widget('Conversation', array('conversation'=>$loop->current(),'out'=>$this->out,'widgetId'=>'conversation-'.$loop->get_id()));
    } while ($loop->next());

    $this->pagination($pages);
