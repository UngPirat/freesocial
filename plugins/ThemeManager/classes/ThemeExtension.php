<?php

abstract class ThemeExtension {
    protected $scoped = null;

    function __construct($args=null) {
        // iterate class variables and set to either default or given
        foreach (get_class_vars(get_class($this)) as $key=>$val) {
            $this->$key = isset($args[$key]) ? $args[$key] : $val;
        }

        if (!$this->validate()) {
            throw new Exception('Could not validate args');
        }

        $this->initialize();
    }

    protected function validate() {
        if (is_null($this->scoped)) {
            $user = common_current_user();
            if (!is_null($user)) {
                $this->scoped = $user->getProfile();
            }
        }
    }

    protected function initialize() {
        return true;
    }

}
