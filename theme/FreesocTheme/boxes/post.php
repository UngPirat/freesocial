<?php
    $this->out->elementStart('aside', array('id'=>'post'));

	if (common_logged_in()) :
		NoticeFormWidget::run(array('returnto'=>$this->action));
    else :
		WelcomeWidget::run(array('image'=>$this->url('img/catfish-welcome.png')));
    endif;

    $this->out->elementEnd('aside');
