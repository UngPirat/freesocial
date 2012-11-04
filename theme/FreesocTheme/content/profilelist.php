<?php
	$args = array('item'=>$this->action->profile, 'widgetId'=>'stream');

	if ($this->is_action('usergroups')) {
		$this->widget('GroupList', $args);
	} else {
		$this->widget('SubscriptionList', $args);
	}
