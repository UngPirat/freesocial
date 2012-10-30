<?php
	if (isset($this->action->conversation)) {
		// list of notices from same conversation
		$this->widget('Conversation', array('id'=>$this->action->conversation, 'num'=>-1));

	} elseif ($this->is_action('timeline') || $this->is_action('public')) {
		// list of notices in timeline mode (public is a timeline mode without profile)
		$this->widget('ConversationList', array('list'=>$this->action->notice,
												'convArgs'=>array(
													'num'=>1,
													'saveFirst'=>true,
													),
												'pagination'=>true,
												));
	} elseif ($this->is_action('showstream')) {
		$this->widget('NoticeList', array('list'=>$this->action->notice, 'widgetId'=>'stream'));
	} else {
		// single notice
		$this->widget('Notice', array('item'=>$this->action->notice));

	}
