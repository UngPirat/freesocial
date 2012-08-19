<?php

class HomeMenu extends ThemeMenu {
    protected function initialize() {
        $this->user = common_current_user();
        $args = array('nickname'=>$this->user->nickname);
        // list($actionName, $args, $label, $description, $id)
        $this->title = _m('Home');
        $this->items = array(
                array('all',           $args, _m('MENU','Home'), _('Home timeline')),
                array('showstream',    $args, _m('MENU','Profile'), _('Your profile')),
                array('replies',       $args, _m('MENU','Mentions'), _('Who mentioned you?')),
                array('showfavorites', $args, _m('MENU','Favorites'), _('Your favorites')),
                );
    }
}
