<?php
	$this->out->elementStart('aside');

	if (common_logged_in()) :
		$this->out->text('logged in');
	    $this->out->flush();
		$nf = new NoticeForm($this->action);
		$nf->show();
		$this->action->flush();
	else :
		$this->out->text('not logged in');
	endif;

	$this->out->elementEnd('aside');
        $this->out->elementStart('marquee', array('direction'=>'left','scrolldelay'=>'50'));
		$this->out->element('img', array('class'=>'welcome','src'=>$this->url('img/catfish-welcome.png')));
        $this->out->elementEnd('marquee');
	$this->out->flush();
?>
