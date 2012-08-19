<?php

abstract class ThemeExtension {

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

    abstract protected function validate();

    protected function initialize() {
        return true;
    }

}
