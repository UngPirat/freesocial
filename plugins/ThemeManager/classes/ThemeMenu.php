<?php

class ThemeMenu extends ListWidget {
    protected $list  = array();
    protected $action = null;
    protected $offset = 0;
    protected $num    = -1;

    protected $menuClass   = 'sub-menu';
    protected $itemClass   = 'menu-item';
    protected $itemTag     = 'li';
    protected $loopClass   = 'menu';
    protected $loopTag     = 'ul';
    protected $titleClass  = 'menu-title';
    protected $widgetClass = 'horizontal-menu';
    protected $widgetTag   = 'nav';

    static function run(array $args=array()) {
        $class = get_class();    // this seems to work as the ThemeWidget class is abstracted!
        $widget = new $class($args);
        $widget->show();
    }

    function get_list() {    // set stuff that shouldn't be set by $args
        return $this->list;
    }
    function the_loop() {
        $this->out->elementStart('ul', $this->loopClass);
        do {
            $this->the_item($this->loop->current());
        } while ($this->loop->next());
        $this->out->elementEnd('ul');
    }
    function the_item($item) {
        $menu = null;
        if (isset($item['menu']) && is_subclass_of($item['menu'], 'ThemeMenu')) {
            try {
				$class = $item['menu'];
				$args = isset($item['args']) ? $item['args'] : array();
				foreach(array('action', 'loopClass', 'widgetTag') as $key) {
					$args[$key] = $this->$key;
				}
                $menu = new $class($args);
            } catch (Exception $e) {
                return false;
            }
            if (!$menu->count()) {
                return false;
            }
        }

        if (!is_null($menu)) {
            return $menu->show();
        }

        $this->menu_item($item, $this->out, $this->action);
    }
    function menu_item($item, $out, $action=null) {
        foreach(array('url', 'args', 'label', 'description', 'current') as $arg) {
            $$arg = isset($item[$arg]) ? $item[$arg] : null;
        }
        
        $currentItem = (!empty($current) || $url == $action->trimmed('action'))
                        ? ' current-menu-item'
                        : '';
        $url = (null===parse_url($url, PHP_URL_SCHEME)
                    ? common_local_url($url, (array)$args)
                    : $url);

        $out->elementStart('li', $this->itemClass.$currentItem);
        $out->element('a', array('href'=>$url, 'title'=>$description), $label);
        $out->elementEnd('li');
    }
    
    function the_content() {
        $args = array('class'=>$this->widgetClass);
        if (!empty($this->widgetId)) {
            $args['id'] = $this->widgetId;
        }

        if ($this->loop->count()) {
            $this->widgetTag && $this->out->elementStart($this->widgetTag, $args);
			$this->the_title();
            $this->the_loop();
            $this->the_more();
        } else {
            $this->the_empty();
        }
        $this->widgetTag && $this->out->elementEnd($this->widgetTag);
    }
}
