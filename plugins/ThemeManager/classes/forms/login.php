<?php

class LoginForm extends ThemeForm {
    protected $content   = null;
    protected $returnto = null;
    protected $inreplyto = null;
    protected $private   = null;
    protected $charlimit  = null;

    static function run(array $args=array()) {
        $class = get_class();
        $widget = new $class($args);    // runs validate()
        $widget->show();
    }

    function initialize() {
        parent::initialize();

        $this->attributes['action'] = common_local_url('passwordlogin');

        if (empty($this->charlimit)) {
            $this->charlimit = Notice::maxContent();
        }
    }

    function the_elements() {
        $els = new FormElements($this->out);
        $els->legend(_m('Login to site'));

		$els->input('nickname', null, null, _m('Username'));
		$els->password('password', null, _m('Password'));

//        $els->checkbox('rememberme', _m('Remember my login'), _m('Do not use on shared computers.'));

		$els->submit('submit', null, _m('BUTTON', 'Login'));
        return $els;
    }
}

?>
