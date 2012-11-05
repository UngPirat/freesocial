<!DOCTYPE html>
<?php
    $this->out->elementStart('html', array('lang'=>$this->get_lang()));
    $this->out->elementStart('head');
    $this->title = $this->get_title();
    if (isset($args['page']) && $args['page'] >= 2) {
        $this->title .= ' - ' . sprintf( _m('Page %s'), $args['page']);
    }
    $this->title .= ' | ';
    $this->title .= $this->get_siteinfo('name');
    $this->out->element('title', null, $this->title);
	foreach (array('jquery-1.8.1.min', 'fancybox/jquery.fancybox.pack',
					'jquery-form/jquery.form', 'init', 'interaction-basics') as $script) {
    	$this->out->script($this->url("js/$script.js"));
	}
    $this->head();
$this->out->elementEnd('head');
$this->out->elementStart('body', $this->is_action());
$this->out->elementStart('div', array('id'=>'wrapper'));

    $this->out->elementStart('header');
    $this->box('site-title');
    $this->out->elementStart('div', array('id'=>'login'));

	$this->menu('Session', array('widgetClass'=>'horizontal-menu session'));
		if ($this->loggedIn()) {
	        $this->widget('Vcard', array('item'=>$this->profile, 'avatarSize'=>Avatar::STREAM_SIZE, 'mini'=>true, 'webfinger'=>true));
    	}
    $this->out->elementEnd('div');
    $this->box('topmenu');
$this->out->elementEnd('header');
$this->box('post');
