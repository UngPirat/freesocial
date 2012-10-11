<?php

class ProfileSettings extends ThemeSettings {
    protected function validate() {
        if (!common_logged_in()) {
            return false;
        }

        $this->profile = Profile::current();

        return parent::validate();
    }

    protected function initialize() {
        parent::initialize();

        $this->title = _m('Profile settings');
		}
    }

    function get_list() {
		$items = array(
				'nickname' => 'readonly',
				'fullname' => 'input',
				'homepage' => 'input',
				'bio'      => 'textarea',
				'location' => 'input',
				'sharelocation' => 'checkbox',
			);

		if (common_config('profile', 'changenick') == true) {
			$items['nickname'] = 'input';
		}
		foreach (array_keys($items) as $setting=>$type) {
			$items[$setting] = array('type' => $type);
			$items[$setting]['value'] = ($this->action->arg($setting))
								? $this->action->arg($setting)
								: $items[$setting] = $this->profile->$setting;

        // opens up a reference to $items and will replace an Action in events below
        $adapter = new ThemeManagerAdapter($items, $this->action);

        // list($actionName, $args, $label, $description, $current)
        $args = array();
        $items = array_merge($items, array(
                array('type'=>'input', 'name'=>'fullname', 'value'=>$this->fullname,
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

        // wow, this has to be done in a better fashion :D
        $haveImPlugin = false;
        Event::handle('HaveImPlugin', array(&$haveImPlugin));
        if ($haveImPlugin) {
            $items[] = array('url'=>'imsettings', 'label'=>_m('MENU','Messaging'), 'description'=>'Instant messengers');
        }

        unset($adapter);

        return $items;
    }
}
