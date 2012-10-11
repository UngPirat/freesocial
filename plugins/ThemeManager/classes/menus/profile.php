<?php

class ProfileMenu extends ThemeMenu {
	protected $profile = null;

    protected function validate() {
		if (!is_a($this->profile, 'Profile')) {
			return false;
		}
        return parent::validate();
    }

    protected function initialize() {
        parent::initialize();

        $this->title = _m('Profile streams');
        $this->titleLink = common_local_url('showstream', array('nickname'=>$this->profile->nickname));
    }

    function get_list() {
        $list = array();
        // opens up a reference to $list and will replace an Action in events below
        $adapter = new ThemeManagerAdapter($list, $this->action);
        if (Event::handle('StartProfileNav', array($adapter))) {
            $args = array('nickname'=>$this->profile->nickname);
            // list($actionName, $args, $label, $description, $id)
            $list = array_merge($list, array(
                array('url'=>'showstream', 'args'=>$args, 'label'=>_m('MENU','Posts'), 'description'=>_m('Your original posts')),
                array('url'=>'all',        'args'=>$args, 'label'=>_m('MENU','Timeline'), 'description'=>_m('Home timeline')),
                array('url'=>'mentions',   'args'=>$args, 'label'=>_m('MENU','Mentions'), 'description'=>_m('Who mentioned you?')),
                array('url'=>'replies',    'args'=>$args, 'label'=>_m('MENU','Replies'), 'description'=>_m('Your replies to others')),
                array('url'=>'favorites',  'args'=>$args, 'label'=>_m('MENU','Favorites'), 'description'=>_m('Your favorites')),
                ));
            Event::handle('EndProfileNav', array($adapter));
        }
        return $list;
    }
}
