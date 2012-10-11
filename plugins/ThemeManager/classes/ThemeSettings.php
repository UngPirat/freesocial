<?php

class ThemeSettings extends ListWidget {
    protected $items  = array();
    protected $action = null;

    static function run(array $args=array()) {
        $class = get_class();    // this seems to work as the ThemeWidget class is abstracted!
        $widget = new $class($args);
        $widget->show();
    }

    function the_loop() {
        do {
            $this->the_item($this->loop->current());
        } while ($this->loop->next());
    }
    function the_item($item) {
        try {
            $form = new $item(array('action'=>$this->action));
            $form->show();
        } catch (Exception $e) {
            return false;
        }
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
