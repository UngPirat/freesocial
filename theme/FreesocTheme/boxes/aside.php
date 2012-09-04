<?php
	$this->out->elementStart('aside');

	if (common_logged_in()) :
		$this->out->text('logged in');
		NoticeFormWidget::run(array('returnto'=>$this->action));
	else :
		$this->out->text('not logged in');
		$this->out->element('img', array('class'=>'welcome','src'=>$this->url('img/catfish-welcome.png')));
	endif;

	$this->out->elementEnd('aside');
	$this->out->flush();
?>
