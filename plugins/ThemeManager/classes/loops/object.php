<?php

class ObjectLoop extends ThemeExtension {    // might extend Iterator in the future
    protected $list   = array();
    protected $offset = 0;
    protected $num    = -1;
	protected $saveFirst = false;	// good for conversations where first post should be visible

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

		$first = $this->saveFirst ? array_shift($this->list) : null;
		$childCount = count($this->list);
        if ($this->num>=0 && $this->offset==0) {
			$this->list = array_slice($this->list, 0-$this->num);
		} elseif ($this->num>=0 && $this->offset!=0) {
			$this->list = array_slice($this->list, $this->offset, $this->num);
		} elseif ($this->num<0 && $this->offset!=0) {
			$this->list = array_slice($this->list, $this->offset);
		}

		if (!is_null($first)) {
			$showCount = count($this->list);
			if ($showCount < $childCount) {
				$first->showmore = $childCount-$showCount;
			}
			array_unshift($this->list, $first);
		}

        $this->prefill();
        $this->reset();
        $this->count = count($this->list);
    }

    function count() {
        return $this->count;
    }

	function key() {
		return key($this->list);
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
