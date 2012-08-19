<?php

class MenuWidget extends ThemeWidget {
    // these values will be set by default or $args-supplied values
    protected $action;
    protected $items;

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
        return parent::validate();
    }

    function show() {
        $this->out->elementStart('ul', 'menu');
        foreach($this->items as $item) :
            list($actionName, $args, $label, $description) = $item;
            $actionUrl = common_local_url($actionName, $args);
            $isCurrent = $action == $this->action->args['name'];
            $this->out->elementStart('li', array('id'=>$id, 'class'=>'menu-item' . ($isCurrent ? 'current' : '')));
            $this->out->element('a', array('href'=>$actionUrl, 'title'=>$description), $label);
            $this->out->elementEnd('li');
        endforeach;
        $this->out->elementEnd('ul');
        $this->out->flush();
    }

}

?>
