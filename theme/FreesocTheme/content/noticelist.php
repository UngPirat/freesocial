<?php
	$num = 1;	// default conversation reply show count

	if (isset($this->action->notices)) {
		$this->action->notice = $this->action->notices;
		$num = -1;	// show full reply list
	}
	$page = isset($this->action->args['page']) ? $this->action->args['page'] : 1;
	$this->widget('ConversationList', array('list'=>$this->action->notice,
											'page'=>$page,
											'convArgs'=>array(
													'num'=>$num,
													'saveFirst'=>true,
												),
										));
