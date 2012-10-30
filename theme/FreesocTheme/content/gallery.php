<?php

	$this->box('aside-showstream');

	$this->out->elementStart('section', array('id'=>'stream', 'class'=>'legacy gallery'));
	if (!isset($this->action->notice)) {
		$profile = $this->action->user->getProfile();
		$action = strtolower($this->action->arg('action'));
		switch ($action) {
		case 'subscriptions':
		case 'subscribers':
			$this->widget(ucfirst(mb_substr($action, 0, -1)).'List', array('item'=>$profile));
			break;
		}
	} elseif ($this->is_action('notice')) {
		NoticeWidget::run(array('item'=>$this->action->notice));
	} else {
		$this->action->showContent();
	}
	$this->out->elementEnd('section');
