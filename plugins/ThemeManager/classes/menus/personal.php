<?php

class PersonalMenu extends ThemeMenu {
    protected function validate() {
		if (!is_a($this->scoped, 'Profile')) {
			return false;
		}
        return parent::validate();
    }

    protected function initialize() {
        parent::initialize();

        $this->title = _m('Personal');
        $this->titleLink = common_local_url('timeline', array('nickname'=>$this->scoped->nickname));
    }

    function get_list() {
        $list = array();
        // opens up a reference to $list and will replace an Action in events below
        $adapter = new ThemeManagerAdapter($list, $this->action);
        if (Event::handle('StartPersonalNav', array($adapter))) {
            $args = array('nickname'=>$this->scoped->nickname);
            // list($actionName, $args, $label, $description, $id)
            $list = array_merge($list, array(
                array('url'=>'profile',   'args'=>$args, 'label'=>_m('MENU','Profile'), 'description'=>_m('Your profile page')),
                array('url'=>'timeline',   'args'=>$args, 'label'=>_m('MENU','Timeline'), 'description'=>_m('Incoming timeline')),
                array('url'=>'showstream', 'args'=>$args, 'label'=>_m('MENU','Posts'), 'description'=>_m('Your original posts')),
                array('url'=>'mentions',   'args'=>$args, 'label'=>_m('MENU','Mentions'), 'description'=>_m('Who mentioned you?')),
                array('url'=>'replies',    'args'=>$args, 'label'=>_m('MENU','Replies'), 'description'=>_m('Your replies to others')),
                array('url'=>'favorites',  'args'=>$args, 'label'=>_m('MENU','Favorites'), 'description'=>_m('Your favorites')),
                ));
            Event::handle('EndPersonalNav', array($adapter));
        }
        return $list;
    }
}
