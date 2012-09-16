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
        $items = array();
        // opens up a reference to $items and will replace an Action in events below
        $adapter = new ThemeManagerAdapter($items, $this->action);
        if (Event::handle('StartPersonalGroupNav', array($adapter))) {
            $args = array('nickname'=>$this->profile->nickname);
            // list($actionName, $args, $label, $description, $id)
            $items = array_merge($items, array(
                array('url'=>'all', 'args'=>$args, 'label'=>_m('MENU','Home'), 'description'=>_m('Home timeline')),
                array('url'=>'showprofile',   'args'=>$args, 'label'=>_m('MENU','Profile'), 'description'=>_m('Your profile')),
                array('url'=>'replies',       'args'=>$args, 'label'=>_m('MENU','Mentions'), 'description'=>_m('Who mentioned you?')),
                array('url'=>'showstream',    'args'=>$args, 'label'=>_m('MENU','Notices'), 'description'=>_m('Your noticestream')),
                array('url'=>'showfavorites', 'args'=>$args, 'label'=>_m('MENU','Favorites'), 'description'=>_m('Your favorites')),
                ));
            Event::handle('EndPersonalGroupNav', array($adapter));
        }
        return $items;
    }
}
