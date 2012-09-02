<?php

class ConversationWidget extends NoticeListWidget {
    // these values will be set by default or $args-supplied values
    protected $conversation;
	protected $widgetClass = 'conversation';

    static function run($args=null) {
        $class = get_class();
        $widget = new $class($args);    // runs validate()
        $widget->show();
    }

    // always gets run on __construct, which is also called on ::run()
    protected function validate() {
        if (!is_array($this->conversation)) {
            return false;
        }
        return parent::validate();
    }

    function get_list() {
        return $this->conversation;
    }

    function the_item($item) {
        NoticeWidget::run(array('notice'=>$item,'out'=>$this->out));
    }
}

?>
