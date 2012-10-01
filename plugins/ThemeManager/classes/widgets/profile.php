<?php

class ProfileWidget extends ThemeWidget {
    // these values will be set by default or $args-supplied values
    protected $item;
    protected $avatarSize = Avatar::PROFILE_SIZE;

    static function run($args=null) {
        $class = get_class();
        $widget = new $class($args);    // runs validate()
        $widget->show();
    }

    // always gets run on __construct, which is also called when instantiated with ::run()
    protected function validate() {
        if (!is_a($this->item, 'Profile')) {
            return false;
        }
        return parent::validate();
    }

    function show() {
        $this->the_vcard();
        $this->the_actions();
        $this->the_metadata();
		$this->the_connections();
    }

    function get_name() {
        return $this->item->getBestName();
    }
    function get_profile_url(Profile $profile=null) {
        $profile = (is_null($profile) ? $this->item : $profile);
        return class_exists('RemoteProfileAction')
                ? common_local_url('remoteprofile', array('id'=>$profile->id))
                : $profile->profileurl;
    }
    function get_webfinger() {
        return $this->item->nickname . '@' . parse_url($this->item->profileurl, PHP_URL_HOST);
    }
    function the_metadata() {
        $this->out->elementStart('dl', 'metadata');
        $this->the_tags();
        $this->out->elementEnd('dl');
    }
    function the_tags() {
        $this->out->element('dt', null, _m('Tags'));
        // a bunch of dd with the user's tags
    }
    function the_actions() {
        ProfileactionsWidget::run(array('item'=>$this->item, 'scoped'=>$this->scoped));
    }
	function the_connections() {
		SubscriptionListWidget::run(array('profile'=>$this->item, 'scoped'=>$this->scoped));
		SubscriberListWidget::run(array('profile'=>$this->item, 'scoped'=>$this->scoped));
	}
    function the_vcard() {
        $this->out->elementStart('span', 'vcard author');
        $this->out->elementStart('a', array('href'=>$this->get_profile_url()));
        if (!Event::handle('GetAvatarElement', array(
								&$element, $this->item, $this->avatarSize
							))) {
			$this->out->element($element['tag'], $element['args']);
		}
        $this->out->element('span', 'fn', $this->get_name());
        $this->out->elementEnd('a');
        $this->out->element('a', array('href'=>$this->item->profileurl, 'class' => 'url'), _m('Original profile'));
        $this->out->elementEnd('span');
    }
}

?>
