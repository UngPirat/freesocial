<?php

class PublicMenu extends ThemeMenu {
    protected function initialize() {
        // list($actionName, $args, $label, $description, $id)
        $this->title = _m('Site menu');
        $this->items = array(
                array('public',           null, _m('MENU','Site timeline'), _('Public timeline')),
                array('favorited',    null, _m('MENU','Site favorites'), _('Popular notices')),
                array('groups',       null, _m('MENU','Site groups'), _('Join a group!')),
                );
    }
}
