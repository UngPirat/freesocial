<?php

class SessionMenu extends ThemeMenu {
	protected $widgetClass = 'session';

    protected function validate() {
        $this->profile = Profile::current();
		$this->loggedin = common_logged_in();

        return parent::validate();
    }

    function get_list() {
        $list = array();
        // opens up a reference to $list and will replace an Action in events below
        $adapter = new ThemeManagerAdapter($list, $this->action);

		if (!$this->loggedin) {
			$list[] = array('menu'=>'LoginMenu', 'args'=>array('action'=>$this->action, 'widgetTag'=>'li', 'loopClass'=>'sub-menu'));
            $list[] = array('url'=>'register', 'label'=>_m('MENU','Register'), 'description'=>_m('Register a new account'));
		} else {
	        if (Event::handle('StartLogoutGroupNav', array($adapter))) :
	            $list = array_merge($list, array(
                	array('url'=>'logout', 'label'=>_m('MENU','Log out'), 'description'=>_m('Close this session')),
	                ));
	            Event::handle('EndLogoutGroupNav', array($adapter));
	        endif;
		}
        return $list;
    }
}
