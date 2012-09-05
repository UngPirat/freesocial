<?php
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
											'loopArgs'=>array('num'=>1,'saveFirst'=>true),
										));
    } while ($loop->next());

    $this->pagination($pages);
