<?php

class ThemeMenu extends ThemeExtension {
    protected $items = array();
    protected $title = null;

    protected function validate() {
        if (!is_array($this->items)) {
            return false;
        }
        return true;
    }

    function getItems() {	// set stuff that shouldn't be set by $args
        return $this->items;
    }
}
