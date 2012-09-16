<?php
	$num = 1;	// default conversation reply show count

	if (isset($this->action->notices)) {
		$this->action->notice = $this->action->notices;
		$num = -1;	// show full reply list
	}
	$loop = $this->loop(array('list'=>$this->action->notice,'num'=>NOTICES_PER_PAGE), 'Conversation');

	try {
		$pages = $loop->get_paging(isset($this->action->args['page']) ? $this->action->args['page'] : null);
	} catch (Exception $e) {
        $pages = array();
	}

    $this->pagination($pages);

    do {
        $this->widget('Conversation', array('conversation'=>$loop->current(),
		                                    'widgetId'=>'conversation-'.$loop->key(),
											'loopArgs'=>array('num'=>$num,'saveFirst'=>true),
										));
    } while ($loop->next());

    $this->pagination($pages);
