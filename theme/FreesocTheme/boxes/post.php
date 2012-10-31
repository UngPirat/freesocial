<?php
    $this->out->elementStart('aside', array('id'=>'post'));

	if (common_logged_in()) :
		$this->out->element('header', array('accesskey'=>'n', 'class'=>'slidecontrol'), _m("Post »"));
		NewnoticeForm::run();
    else :
		WelcomeWidget::run(array('image'=>$this->url('img/catfish-welcome.png')));
    endif;

    $this->out->elementEnd('aside');
