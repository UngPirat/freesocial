<?php

class PersonalMenu extends ThemeMenu {
    protected function initialize() {
        $this->user = common_current_user();
        if (empty($this->user)) {
            return false;
        }

        $args = array('nickname'=>$this->user->nickname);
        // list($actionName, $args, $label, $description, $id)
        $this->title = _m('Personal');
        $this->items = array(
                array('all',           $args, _m('MENU','Home'), _('Home timeline')),
                array('showstream',    $args, _m('MENU','Profile'), _('Your profile')),
                array('replies',       $args, _m('MENU','Mentions'), _('Who mentioned you?')),
                array('showfavorites', $args, _m('MENU','Favorites'), _('Your favorites')),
                );
    }
}
