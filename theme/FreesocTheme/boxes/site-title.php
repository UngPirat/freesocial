<?php

	$this->out->elementStart('h1', array('id'=>'site-title'));
	$this->out->element('a', array('href'=>$this->get_siteinfo('url')), $this->get_siteinfo('name'));
	$this->out->elementEnd('h1');
	$this->out->elementStart('div', array('style'=>'position:absolute;'));
	$this->out->text('[Beta design. Problems? ');
	$this->out->element('a', array('href'=>$_SERVER['REQUEST_URI'].'?notm'), 'Try the old view');
	$this->out->text('.]');
	$this->out->elementEnd('div');
