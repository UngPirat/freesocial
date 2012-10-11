<?php

class ProfileWidget extends ThemeWidget {
    // these values will be set by default or $args-supplied values
    protected $item;

    protected $avatarSize = Avatar::PROFILE_SIZE;
    protected $mini       = false;
//    protected $webfinger  = false;

    static function run(array $args=array()) {
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
        if (!$this->mini && !$this->is_me()) {
			$this->the_actions();
		}
        $this->the_vcard();
    }

	function is_me() {
		if (!$this->scoped) {
			return false;
		}
		return $this->item->id === $this->scoped->id;
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
    function the_metadata() {
        $this->out->elementStart('dl', 'metadata');
		$this->the_userinfo();
        $this->the_tags();
        $this->out->elementEnd('dl');
    }
    function the_tags() {
        $this->out->element('dt', null, _m('Tags'));
        // a bunch of dd with the user's tags
    }
	function the_userinfo() {
		$this->out->element('dt', null, _m('Full name'));
		$this->out->element('dd', 'fn', $this->get_name());
        $this->out->element('dt', null, _m('Webfinger ID'));
		$this->out->elementStart('dd', 'webfinger');
		$this->out->element('a', array('href'=>$this->item->profileurl, 'class' => 'url'), $this->item->getWebfinger());
		$this->out->elementEnd('dd');
		$this->out->element('dt', null, _m('Homepage'));
		$this->out->elementStart('dd');
		$this->out->element('a', array('href'=>$this->item->homepage, 'rel'=>'nofollow external', 'class' => 'url'), $this->item->homepage);
		$this->out->elementEnd('dd');
	}
    function the_actions() {
        ProfileactionsWidget::run(array('item'=>$this->item, 'scoped'=>$this->scoped));
    }
    function the_vcard() {
		$class = 'vcard author' . ($this->mini ? ' mini' : '');
        $this->out->elementStart('span', $class);
        $this->out->elementStart('a', array('href'=>$this->get_profile_url()));
        if (!Event::handle('GetAvatarElement', array(
								&$element, $this->item, $this->avatarSize
							))) {
			$this->out->element($element['tag'], $element['args']);
		}
        $this->mini && $this->out->element('span', 'fn', $this->get_name());
        $this->out->elementEnd('a');
//        $this->webfinger && $this->out->element('a', array('rel'=>'webfinger','href'=>'acct:'.$this->item->getWebfinger()), $this->item->getWebfinger());
		if (!$this->mini) {
			$this->the_metadata();
		}
        $this->out->elementEnd('span');
    }
}

?>
