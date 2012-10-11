<?php

class MenuListWidget extends ListWidget {
    // these values will be set by default or $args-supplied values
    protected $action;
    protected $menus;

    protected $itemClass = 'menu-item';
    protected $loopClass = 'menu';
    protected $widgetClass = 'list menu-container';

    static function run(array $args=array()) {
        $class = get_class();    // this seems to work as the ThemeWidget class is abstracted!
        $widget = new $class($args);
        $widget->show();
    }

    // always gets run on __construct, which is also called on ::run()
    protected function validate() {
        if (!is_a($this->action, 'Action')) {
            return false;
        }
        foreach($this->menus as $key=>$menu) {
            if (!is_subclass_of($menu, 'ThemeMenu')) {
                return false;
            }
        }

        return parent::validate();
    }
    
    function get_list() {
        return $this->menus;
    }

    function the_loop() {
        $this->out->elementStart('ul', $this->loopClass);
        do {
            $this->the_item($this->loop->current());
        } while ($this->loop->next());
        $this->out->elementEnd('ul');
    }

    function the_item($item) {
        try {
            $menu = new $item;
        } catch (Exception $e) {
            return false;
        }
        if (!$menu->count()) {
            return false;
        }

        $this->out->elementStart('li', $this->itemClass);
        $menu->title && $this->out->element('span', array('class'=>'menu-title', 'id'=>'menu-'.common_canonical_tag($this->title)), $this->title);
//            $this->out->element('a', array('href'=>'#', 'id'=>'menu-'.common_canonical_tag($title)), $title);
        $this->out->elementStart('ul', $menu->get_class());
        foreach($menu->getItems() as $item) :
            list($actionName, $args, $label, $description) = $item;
            $actionUrl = common_local_url($actionName, $args);
            $this->out->elementStart('li', array('class'=>'menu-item' .
                                                          ($actionName == $this->action->trimmed('action') ? ' current-menu-item' : '')
                                                ));
            $this->out->element('a', array('href'=>$actionUrl, 'title'=>$description), $label);
            $this->out->elementEnd('li');
        endforeach;
        $this->out->elementEnd('ul');
        $this->out->elementEnd('li');
    }

}

?>
