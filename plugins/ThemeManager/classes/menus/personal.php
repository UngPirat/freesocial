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
        if (Event::handle('StartPersonalGroupNav', array($adapter))) {
            $args = array('nickname'=>$this->profile->nickname);
            // list($actionName, $args, $label, $description, $id)
            $list = array_merge($list, array(
				array('menu'=>'ProfileMenu',  'args'=>array('profile'=>$this->profile)),
                array('url'=>'subscriptions', 'args'=>$args, 'label'=>_m('MENU','Subscriptions'), 'description'=>_m('Streams you subscribe to')),
                array('url'=>'subscribers',   'args'=>$args, 'label'=>_m('MENU','Subscribers'), 'description'=>_m('Accounts that subscribe to you')),
                ));
            Event::handle('EndPersonalGroupNav', array($adapter));
        }
        return $list;
    }
}
