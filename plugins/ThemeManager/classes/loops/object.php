<?php

class ObjectLoop extends ThemeExtension {	// might extend Iterator in the future
    protected $list   = array();
	protected $offset = 0;
    protected $num    = 5;

    private $count  = null;

    public function validate() {
        if (is_a($this->list, 'ArrayWrapper')) {
            $this->list = $this->list->fetchAll();
        }

        if (!is_array($this->list)) {
            return false;
        }

        return true;
    }

    public function initialize() {
        parent::initialize();

        $this->prefill();
		$this->reset();
        $this->count = count($this->list);
    }

    function count() {
        return $this->count;
    }

    function merge(array $list) {
        $this->list = array_merge($this->list, $list);
        $this->reset();
		return $this->list;
    }

    function next() {
        return next($this->list);
    }

    function current() {
        return current($this->list);
    }

    function reset() {
        return reset($this->list);
    }

    function prefill() {
        // When you need to fetch stuff like profiles or avatars
    }

    function get_id() {
        return $this->current()->id;
    }

}
