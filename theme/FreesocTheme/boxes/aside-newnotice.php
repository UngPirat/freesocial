<?php
	if (isset($this->action->args['inreplyto'])
			&& $inreplyto = Notice::staticGet('id', $this->action->args['inreplyto'])) {
		//$this->out->element('h3', null, _m('What you are replying to'));
		NoticeWidget::run(array('item'=>$inreplyto));
	} elseif (isset($this->action->args['replyto'])
			&& $replyto = Profile::staticGet('id', $this->action->args['replyto'])) {
		//$this->out->element('h3', null, _m('Who you are sending to'));
		ProfileWidget::run(array('item'=>$replyto));
	}
