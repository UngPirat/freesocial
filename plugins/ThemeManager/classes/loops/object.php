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
		if ($this->num > $childCount) {
			// list is less than desired num
		} elseif ($this->num>=0 && !$this->saveFirst) {
			$this->list = array_slice($this->list, $this->offset, $this->num);
		} elseif ($this->num==0 && $this->saveFirst) {
			$this->list = array();
		} elseif ($this->num>0 && $this->saveFirst) {
			$this->list = array_slice($this->list, 0-$this->num);
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

    function get_paging($page) {
        $page  = (0+$page === 0 ? 1 : 0+$page);    // convert to (int)
        if ($page < 1) {
            throw new ClientException('Invalid paging arguments');
        }

        $pages = array();
        if ($page > 1) {
            $pages['next'] = $page - 1;
        }
        if ($this->count() >= $this->num) {
            $pages['prev'] = $page + 1;
        }
        if (isset($pages['next']) || isset($pages['prev'])) {
            $pages['current'] = $page;
        }
        return $pages;
    }

    function count() {
		if (empty($this->count)) {
			$this->count = count($this->list);
		}
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
		$this->count = count($this->list);
        return reset($this->list);
    }

    function prefill() {
        // When you need to fetch stuff like profiles or avatars
    }

}
