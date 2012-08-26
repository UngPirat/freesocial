<?php

class ObjectLoop {	// might extend Iterator in the future
    protected $list;
    private $count = null;

    public function __construct($list) {
        if (!is_a($list, 'ArrayWrapper')) {
            throw new Exception('Not a DataObject');
        }

        $this->list = $list;
        $this->prefill();
    }

    function count() {
        return $this->list->_count;
    }

    function next() {
        return $this->list->fetch();
    }

    function current() {
        return $this->list->_items[$this->list->_i];
    }

    function get_paging($page) {
        $page  = (0+$page === 0 ? 1 : 0+$page);	// convert to (int)
        if ($page < 1) {
            throw new ClientException('Invalid paging arguments');
        }

        $pages = array();
        if ($page > 1) {
            $pages['next']   = $page - 1;
        }
        if ($this->count() > NOTICES_PER_PAGE) {
            $pages['prev']   = $page + 1;
        }
        if (isset($pages['next']) || isset($pages['prev'])) {
            $pages['current'] = (0+$this->list->_items[$this->count()-1]->id) . '..' . (0+$this->list->_items[0]->id);
        }
        return $pages;
    }

    function prefill() {
        // When you need to fetch stuff like profiles or avatars
    }

    function the_id() {
        if (!isset($this->list->id)) return false;
        echo htmlspecialchars($this->list->id);
    }

}
