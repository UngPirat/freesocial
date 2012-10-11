<?php

class GlobalMenu extends ThemeMenu {
    protected function initialize() {
        parent::initialize();

        $this->title = _m('Global');
    }

    function get_list() {
        $list = array();
        // opens up a reference to $list and will replace an Action in events below
        $adapter = new ThemeManagerAdapter($list, $this->action);
        if (Event::handle('StartPublicGroupNav', array($adapter))) {
            // list($actionName, $args, $label, $description, $id)
            $list = array_merge($list, array(
                array('url'=>'public',    'label'=>_m('MENU','Timeline'),  'description'=>_m('Public timeline')),
                array('url'=>'favorited', 'label'=>_m('MENU','Favorites'), 'description'=>_m('Popular notices')),
                array('url'=>'groups',    'label'=>_m('MENU','Groups'),    'description'=>_m('Join a group!')),
                ));
			Event::handle('EndPublicGroupNav', array($adapter));
		}
        return $list;
    }
}
