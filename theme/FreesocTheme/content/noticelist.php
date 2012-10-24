<?php
	if (isset($this->action->notices)) {
		$this->widget('Conversation', array('list'=>$this->action->notices));
	} elseif (is_a($this->action->notice, 'ArrayWrapper') || is_array($this->action->notice)) {
		$this->widget('ConversationList', array('list'=>$this->action->notice,
												'convArgs'=>array(
													'num'=>1,
													'saveFirst'=>true,
													)
												));
	} else {
		$this->widget('Notice', array('item'=>$this->action->notice));
	}

