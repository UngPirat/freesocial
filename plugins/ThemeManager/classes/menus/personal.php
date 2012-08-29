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
        $args = array('nickname'=>$this->profile->nickname);
        // list($actionName, $args, $label, $description, $id)
        $this->title = _m('Personal');
        $this->items = array(
                array('all',           $args, _m('MENU','Home'), _('Home timeline')),
                array('showprofile',   $args, _m('MENU','Profile'), _('Your profile')),
                array('replies',       $args, _m('MENU','Mentions'), _('Who mentioned you?')),
                array('showstream',    $args, _m('MENU','Notices'), _('Your noticestream')),
                array('showfavorites', $args, _m('MENU','Favorites'), _('Your favorites')),
                );
    }
}
