<?php

class GlobalMenu extends ThemeMenu {
    protected function initialize() {
        // list($actionName, $args, $label, $description, $id)
        $this->title = _m('Global');
        $this->items = array(
                array('public',           null, _m('MENU','Timeline'), _('Public timeline')),
                array('favorited',    null, _m('MENU','Favorites'), _('Popular notices')),
                array('groups',       null, _m('MENU','Groups'), _('Join a group!')),
                );
    }
}
