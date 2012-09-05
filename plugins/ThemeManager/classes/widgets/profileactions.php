<?php

class ProfileactionsWidget extends ThemeWidget {
    // these values will be set by default or $args-supplied values
    protected $item;

    protected $widgetClass = '';
    protected $widgetTag   = 'section';

    static function run($args=null) {
        $class = get_class();
        $widget = new $class($args);    // runs validate()
        $widget->show();
    }

    protected function validate() {
        if (!is_a($this->item, 'Profile')) {
            return false;
        }
        return parent::validate();
    }

    function get_actions() {
        $actions = array (
            'subscribe' => new SubscribeForm($this->out, $this->item),
            );
        return $actions;
    }

    function show() {
        $this->out->elementStart($this->widgetTag, "actions {$this->widgetClass}");
        foreach ($this->get_actions() as $action=>$data) {
            if (is_a($data, 'Form')) {
                $data->show();
            } elseif (is_array($data)) {
                $this->out->element($data['element'], $data['args'], $data['content']);
            }
        }
        $this->out->elementEnd($this->widgetTag);
    }
}

?>
