<?php

class MenuWidget extends ThemeWidget {
    // these values will be set by default or $args-supplied values
    protected $action;
    protected $menu;

    static function run($args=null) {
        $class = get_class();	// this seems to work as the ThemeWidget class is abstracted!
        $widget = new $class($args);
        $widget->show();
    }

    // always gets run on __construct, which is also called on ::run()
    protected function validate() {
        if (!is_a($this->action, 'Action')) {
            return false;
        }
        if (!is_a($this->menu, 'ThemeMenu')) {
            return false;
        }
        return parent::validate();
    }

    function show() {
        if (!$this->menu->countItems()) {
            return false;
        }

        if ($title = $this->menu->getTitle()) {
            $this->out->element('a', array('href'=>'#', 'name'=>common_canonical_tag($title)), $title);
        }
        $this->out->elementStart('ul', $this->menu->get_class());
        foreach($this->menu->getItems() as $item) :
            list($actionName, $args, $label, $description) = $item;
            $actionUrl = common_local_url($actionName, $args);
            $this->out->elementStart('li', array('class'=>'menu-item' .
                                                          ($actionName == $this->action->args['action'] ? ' current-menu-item' : '')
                                                ));
            $this->out->element('a', array('href'=>$actionUrl, 'title'=>$description), $label);
            $this->out->elementEnd('li');
        endforeach;
        $this->out->elementEnd('ul');
        $this->out->flush();
    }

}

?>
