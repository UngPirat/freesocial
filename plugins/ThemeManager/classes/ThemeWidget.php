<?php

abstract class ThemeWidget {
    protected $out;

    function __construct($args=null) {
        // iterate class variables and set to either default or given
        foreach (get_class_vars(get_class($this)) as $key=>$val) {
            $this->$key = isset($args[$key]) ? $args[$key] : $val;
        }

        $this->out  = new HTMLOutputter;	// at least now always use our own output

        if (!$this->validate()) {
            throw new Exception('Widget could not validate args');
        }
        $this->initialize();
    }

	abstract static function run($args=null);

    protected function validate() {	// make sure we don't have garbage
        return true;
    }

    protected function initialize() {	// set stuff that shouldn't be set by $args
        return true;
    }
}
