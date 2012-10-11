<?php

class SettingsMenu extends ThemeMenu {
	protected $itemClass = 'settings';

    protected function validate() {
        if (!common_logged_in()) {
            return false;
        }

        $this->profile = Profile::current();

        return parent::validate();
    }

    protected function initialize() {
        parent::initialize();

        $this->title = _m('Settings');
    }

    function get_list() {
        $list = array();
        // opens up a reference to $list and will replace an Action in events below
        $adapter = new ThemeManagerAdapter($list, $this->action);

        if (Event::handle('StartAccountSettingsNav', array($adapter))) {
            // list($actionName, $args, $label, $description, $current)
            $args = array();
            $list = array_merge($list, array(
                    array('url'=>'profilesettings',
                         'label'=>_m('MENU','Profile'), 'description'=>_m('Change your profile settings')),
                    array('url'=>'avatarsettings',
                         'label'=>_m('MENU','Avatar'),  'description'=>_m('Configure your avatar')),
                    array('url'=>'passwordsettings',
                         'label'=>_m('MENU','Password'),'description'=>_m('Set or update your password')),
                    array('url'=>'emailsettings',
                         'label'=>_m('MENU','E-mail'),  'description'=>_m('Change e-mail handling')),
                    array('url'=>'urlsettings',
                         'label'=>_m('MENU','Posting'), 'description'=>_m('URL shortening and posting')),
                    ));

            Event::handle('EndAccountSettingsNav', array(&$adapter));

            // wow, this has to be done in a better fashion :D
            $haveImPlugin = false;
            Event::handle('HaveImPlugin', array(&$haveImPlugin));
            if ($haveImPlugin) {
                $list[] = array('url'=>'imsettings', 'label'=>_m('MENU','Messaging'), 'description'=>'Instant messengers');
            }
        }

        unset($adapter);

        return $list;
    }
}
