<?php

class ProfileWidget extends ThemeWidget {
    // these values will be set by default or $args-supplied values
    protected $profile;
    protected $avatarSize = AVATAR_PROFILE_SIZE;

    static function run($args=null) {
        $class = get_class();
        $widget = new $class($args);    // runs validate()
        $widget->show();
    }

    // always gets run on __construct, which is also called when instantiated with ::run()
    protected function validate() {
        if (!is_a($this->profile, 'Profile')) {
            return false;
        }
        return parent::validate();
    }

    function show() {
        $this->the_vcard();
        $this->the_metadata();
        $this->out->flush();
    }

    function get_name() {
        return $this->profile->fullname
                ? $this->profile->fullname
                : $this->profile->nickname;
    }
    function get_webfinger() {
        return $this->profile->nickname . '@' . parse_url($this->profile->profileurl, PHP_URL_HOST);
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
    function the_vcard() {
        $this->out->elementStart('span', 'vcard');
        $this->out->element('img', array('src'    => $this->profile->avatarUrl($this->avatarSize),
                                         'class'  => 'photo',
                                         'alt'    => sprintf(_('Photo of %s'), $this->get_webfinger()),
                                   ));
        $this->out->element('a', array('href'  => $this->profile->profileurl,
                                       'class' => 'url fn'),
                            $this->get_name());
        $this->out->elementEnd('span');
    }
}

?>
