<?php

class ProfileMenu extends ThemeMenu {
    protected function validate() {
		if (!is_a($this->scoped, 'Profile')) {
			return false;
		}
        return parent::validate();
    }

    protected function initialize() {
        parent::initialize();

        $this->title = _m('Profile');
        $this->titleLink = common_local_url('timeline', array('nickname'=>$this->scoped->nickname));
    }

    function get_list() {
        $list = array();
        // opens up a reference to $list and will replace an Action in events below
        $adapter = new ThemeManagerAdapter($list, $this->action);
        if (Event::handle('StartProfileNav', array($adapter))) {
			$fancyname = $this->scoped->getFancyName();
            $args = array('nickname'=>$this->scoped->nickname);
            // list($actionName, $args, $label, $description, $id)
            $list = array_merge($list, array(
                array('url'=>'profile',   'args'=>$args, 'label'=>_m('MENU','Profile'),
					'description'=>sprintf(_m('Profile page for %s'), $fancyname)),
                array('url'=>'timeline',   'args'=>$args, 'label'=>_m('MENU','Timeline'),
					'description'=>sprintf(_m('Incoming timeline for %s'), $fancyname)),
                array('url'=>'showstream', 'args'=>$args, 'label'=>_m('MENU','Posts'),
					'description'=>sprintf(_m('Posts by %s'), $fancyname)),
                array('url'=>'mentions',   'args'=>$args, 'label'=>_m('MENU','Mentions'),
					'description'=>sprintf(_m('Who mentioned %s?'), $fancyname)),
                array('url'=>'replies',    'args'=>$args, 'label'=>_m('MENU','Replies'),
					'description'=>sprintf(_m('Replies by %s'), $fancyname)),
                array('url'=>'favorites',  'args'=>$args, 'label'=>_m('MENU','Favorites'),
					'description'=>sprintf(_m('Favorited by %s'), $fancyname)),
                ));
            Event::handle('EndProfileNav', array($adapter));
        }
        return $list;
    }
}
