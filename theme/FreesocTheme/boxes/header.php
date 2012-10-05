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
    $this->head();
$this->out->elementEnd('head');
$this->out->elementStart('body', $this->is_action());
$this->out->elementStart('div', array('id'=>'wrapper'));

    $this->out->elementStart('header');
    $this->box('site-title');
    $this->out->elementStart('div', array('id'=>'login'));

    try {
        $this->widget('Vcard', array('item'=>$this->profile, 'avatarSize'=>Avatar::STREAM_SIZE, 'mini'=>true));
    } catch (Exception $e) {
        $this->out->element('p', null, _m('You are not logged in!'));
        $this->out->elementStart('p');
        $this->out->element('a', array('href'=>common_local_url('login')), _m('Log in'));
        $this->out->text(_m(' or '));
        $this->out->element('a', array('href'=>common_local_url('register')), _m('register an account'));
        $this->out->text('.');
        $this->out->elementEnd('p');
    }
    $this->out->elementEnd('div');
    $this->box('topmenu');
$this->out->elementEnd('header');
$this->box('post');
