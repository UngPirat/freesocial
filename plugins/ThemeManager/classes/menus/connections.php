<?php

class ConnectionsMenu extends ThemeMenu {
    protected function validate() {
        if (!common_logged_in()) {
            return false;
        }

        $this->profile = Profile::current();

        return parent::validate();
    }

    protected function initialize() {
        parent::initialize();

        $this->title = _m('Connections');
    }

    function get_list() {
        $list = array();
        // opens up a reference to $list and will replace an Action in events below
        $adapter = new ThemeManagerAdapter($list, $this->action);

           $list[] = array(
                        'url'=>'oauthconnectionssettings',
                        'label'=>_m('MENU','Connected apps'),
                        'description'=>_m('Authorized connected apps'));

        Event::handle('EndConnectSettingsNav', array(&$adapter));

        unset($adapter);

        return $list;
    }
}
