<?php
/*
 *  Name: Profile
 */
    $this->box('header');

    $this->out->elementStart($this->is_single()?'article':'div', array('id'=>'content','class'=>($this->is_single()?'single':'')));
    $this->out->element('h2', 'content-title', $this->get_title());

    if ($this->is_single()) :
        if ($this->is_action('showstream')) {
            $this->content('showstream');
    	} elseif ($this->is_action('gallery')) {
			$this->content('gallery');
        } else {
            $this->content('noticelist');
        }
	else :
        $this->content('noticelist');
    endif;
    $this->out->elementEnd($this->is_single()?'article':'div');

    $this->box('footer');
