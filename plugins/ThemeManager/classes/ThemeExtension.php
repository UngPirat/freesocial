<?php

abstract class ThemeExtension {
    protected $scoped = null;    // Profile

    function __construct(array $args=array()) {
        // iterate class variables and set to either default or given
        foreach (get_class_vars(get_class($this)) as $key=>$val) {
            $this->$key = isset($args[$key]) ? $args[$key] : $val;
        }

        $this->prepare();

        if (!$this->validate()) {
            throw new Exception('Could not validate args');
        }

        $this->initialize();
    }

    protected function prepare() {
        if (is_null($this->scoped)) {
            $this->getScoped();
        }
    }

    protected function validate() {
        if (is_null($this->scoped) || !is_a($this->scoped, 'Profile')) {
            return false;
        }

        return true;
    }

    protected function initialize() {
        return true;
    }

    function getScoped() {
        if (empty($this->scoped)) {
            $this->scoped = Profile::current();
        }
        return $this->scoped;
    }
}
