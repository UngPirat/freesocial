<?php

class FederationMenu extends ThemeMenu {
    protected function validate() {
        if (!common_logged_in()) {
            return false;
        }

        return parent::validate();
    }

    protected function initialize() {
        parent::initialize();

        $this->title = _m('Federation');
    }

    function get_list() {
        $list = array();
        // opens up a reference to $list and will replace an Action in events below
        $adapter = new ThemeManagerAdapter($list, $this->action);
        if (Event::handle('StartFederationNav', array($adapter))) {
            $args = array('nickname'=>$this->scoped->nickname);
            // list($actionName, $args, $label, $description, $id)
            $list = array_merge($list, array(
                array('url'=>'subscriptions', 'args'=>$args, 'label'=>_m('MENU','Subscriptions'), 'description'=>_m('Streams you subscribe to')),
                array('url'=>'subscribers',   'args'=>$args, 'label'=>_m('MENU','Subscribers'), 'description'=>_m('Accounts that subscribe to you')),
				array('menu'=>'PersonalMenu', 'args'=>$args),
                ));
            Event::handle('EndFederationNav', array($adapter));
        }
        return $list;
    }
}
