<?php

abstract class ListWidget extends ThemeWidget {
    protected $offset = 0;
    protected $num    = 5;
    protected $page   = 1;

    protected $title  = null;
    protected $pagination = false;
    protected $hideEmpty = false;

    protected $itemClass;
    protected $itemTag;
    protected $loopClass;
    protected $loopTag;
	protected $loopArgs = array();
	protected $loopType = 'Object';

    function show() {
        $this->the_content();
    }

    protected function validate() {
        if (!is_array($this->loopArgs)) {
			return false;
		}

        return parent::validate();
    }

    protected function initialize() {
        parent::initialize();

		if (isset($_REQUEST['page'])) {
			$this->page = 0+$_REQUEST['page'];
		}
		foreach (array('num', 'offset') as $key) :
			if (!isset($this->loopArgs[$key])) {
				$this->loopArgs[$key] = $this->$key;
			}
		endforeach;
        $this->loop = $this->get_loop($this->loopArgs);
    }

    function count() {
        return $this->loop->count();
    }
	function key() {
		return $this->loop->key();
	}

    // could be overloaded if you want stuff like prefilling notices etc.
    function get_loop(array $args=array()) {
		$type = ucfirst($this->loopType).'Loop';
		if (!class_exists($type)) {
			throw new Exception('Bad loop type');
		}
		$args['list'] = $this->get_list();
        return new $type($args);
    }

    abstract function get_list();    // returns a DataObject with multiple entries
    abstract function the_item($item);

    function the_more() {
        return;
    }

    function the_empty() {
        $this->out->element('div', 'not-found', _('No items found'));
    }

    function the_loop() {
        $this->loopTag && $this->out->elementStart($this->loopTag, $this->loopClass);
        do {
            $this->itemTag && $this->out->elementStart($this->itemTag, "list-item {$this->itemClass}");
            $this->the_item($this->loop->current());
            $this->itemTag && $this->out->elementEnd($this->itemTag);
        } while ($this->loop->next());
        $this->loopTag && $this->out->elementEnd($this->loopTag);
    }

    function the_content() {
		$this->out->flush();	// PHP crashes (memory limit?) if we don't flush once in a while
		if (!$this->loop->count() && $this->hideEmpty) {
			return false;
		}
        $args = array('class'=>"list widget {$this->widgetClass}");
        if (!empty($this->widgetId)) {
            $args['id'] = $this->widgetId;
        }

        $this->widgetTag && $this->out->elementStart($this->widgetTag, $args);
        if (!empty($this->title)) {
            $this->out->element('h3', 'widget-title', $this->title);
        }

		$pages = array();
        if ($this->pagination) {
			try {
	            $pages = $this->loop->get_paging($this->page);
    	    } catch (Exception $e) {
	        }
		}

		ThemeManager::pagination($pages);
        if ($this->loop->count()) {
            $this->the_loop();
            $this->the_more();
        } else {
            $this->the_empty();
        }
		ThemeManager::pagination($pages);
        $this->widgetTag && $this->out->elementEnd($this->widgetTag);
    }
}
