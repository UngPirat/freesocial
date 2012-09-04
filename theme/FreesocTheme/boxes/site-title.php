<?php

	$this->out->elementStart('h1', array('id'=>'site-title'));
	$this->out->element('a', array('href'=>$this->get_siteinfo('url')), $this->get_siteinfo('name'));
	$this->out->elementEnd('h1');
	$this->out->element('div', null, '[Beta vision. This is a work in progress. New Theme Manager means new styling. Please deal with it.]');
