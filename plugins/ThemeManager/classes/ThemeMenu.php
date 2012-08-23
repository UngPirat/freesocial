<?php

class ThemeMenu extends ThemeExtension {
    protected $items = array();
    protected $title = null;
    protected $submenu = false;

    protected function validate() {
        if (!is_array($this->items)) {
            return false;
        }
        return true;
    }

    function countItems() {
        return count($this->items);
    }

    function getItems() {	// set stuff that shouldn't be set by $args
        return $this->items;
    }

    function getTitle() {	// set stuff that shouldn't be set by $args
        return $this->title;
    }

    function get_class() {
        return $this->submenu ? 'sub-menu' : 'menu';
    }
}
