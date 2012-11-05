<?php
	if (isset($this->action->conversation)) {
		// list of notices from same conversation
		$this->widget('Conversation', array('id'=>$this->action->conversation, 'num'=>-1));

	} elseif ($this->is_action('showstream') || $this->is_action('public')) {
		$num = ($this->is_action('timeline') || $this->is_action('public')) ? 1 : 0;
		// list of notices in timeline mode (public is a timeline mode without profile)
		$this->widget('ConversationList', array('list'=>$this->action->notice,
												'convArgs'=>array(
													'num'=>$num,
													'saveFirst'=>true,
													),
												'pagination'=>true,
												));
	} else {
		// single notice
		$this->widget('Notice', array('item'=>$this->action->notice));

	}
