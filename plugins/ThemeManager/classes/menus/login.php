<?php

class LoginMenu extends ThemeMenu {
	protected $widgetClass = 'login';

    protected function validate() {
        return parent::validate();
    }

    protected function initialize() {
        parent::initialize();

		$this->title = _m('Log in');
		$this->titleLink = common_local_url('login');
    }

    function get_list() {
        $list = array();
        // opens up a reference to $list and will replace an Action in events below
        $adapter = new ThemeManagerAdapter($list, $this->action);

	    if (Event::handle('StartLoginGroupNav', array($adapter))) :
	        $list = array_merge($list, array(
            	array('url'=>'login', 'label'=>_m('MENU','Password'), 'description'=>_m('Login with your password')),
	            ));
	        Event::handle('EndLoginGroupNav', array($adapter));
	    endif;

        return $list;
    }
}
