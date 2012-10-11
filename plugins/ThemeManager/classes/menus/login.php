<?php

class PersonalMenu extends ThemeMenu {
    protected function validate() {
        if (!common_logged_in()) {
            return false;
        }

        $this->profile = Profile::current();

        return parent::validate();
    }

    protected function initialize() {
        parent::initialize();

        $this->title = _m('Personal');
    }

    function get_list() {
        $list = array();
        // opens up a reference to $list and will replace an Action in events below
        $adapter = new ThemeManagerAdapter($list, $this->action);
        if (Event::handle('StartLoginGroupNav', array($adapter))) {
            $args = array('nickname'=>$this->profile->nickname);
            // list($actionName, $args, $label, $description, $id)
            $list = array_merge($list, array(
				array('menu'=>'ProfileMenu', 'args'=>array('profile'=>$this->profile)),
                ));
            Event::handle('EndLoginGroupNav', array($adapter));
        }
        return $list;
    }
}
