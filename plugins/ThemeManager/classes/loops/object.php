<?php

class ObjectLoop {	// might extend Iterator in the future
    protected $list;

    public function __construct($list) {
        if (!is_a($list, 'ArrayWrapper')) {
            throw new Exception('Not a DataObject');
        }

        $this->list = $list;
        $this->prefill();
    }

    function next() {
        return $this->list->fetch();
    }

    function current() {
        return $this->list->_items[$this->list->_i];
    }

    function prefill() {
        // When you need to fetch stuff like profiles or avatars
    }

    function the_id() {
        if (!isset($this->list->id)) return false;
        echo htmlspecialchars($this->list->id);
    }

}
