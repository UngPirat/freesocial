<?php

class ThemeMenu extends ListWidget {
    protected $items  = array();
    protected $action = null;

    protected $menuClass  = 'sub-menu';
    protected $itemClass  = 'menu-item';
    protected $itemTag    = 'li';
    protected $loopClass  = 'sub-menu';
    protected $loopMenu   = 'menu';
    protected $loopTag    = 'ul';
    protected $titleClass = 'menu-title';
    protected $widgetClass  = 'menu-item';
    protected $widgetTag  = 'li';

    static function run($args=null) {
        $class = get_class();    // this seems to work as the ThemeWidget class is abstracted!
        $widget = new $class($args);
        $widget->show();
    }

    function get_list() {    // set stuff that shouldn't be set by $args
        return $this->items;
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
        if (is_subclass_of($item, 'ThemeMenu')) {
            try {
                $menu = new $item(array('action'=>$this->action));
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

        self::menuItem($item, $this->out, $this->action);
    }
    static function menuItem($item, $out, $action=null) {
        foreach(array('url', 'args', 'label', 'description', 'current') as $arg) {
            $$arg = isset($item[$arg]) ? $item[$arg] : null;
        }
        
        $currentItem = (!empty($current) || $url == $action->trimmed('action'))
                        ? ' current-menu-item'
                        : '';
        $url = (null===parse_url($url, PHP_URL_SCHEME)
                    ? common_local_url($url, (array)$args)
                    : $url);

        $out->elementStart('li', "menu-item$currentItem");
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
            if (!empty($this->title)) {
                $this->out->element('h3', 'menu-title', $this->title);
            }
            $this->the_loop();
            $this->the_more();
        } else {
            $this->the_empty();
        }
        $this->widgetTag && $this->out->elementEnd($this->widgetTag);
    }
}
