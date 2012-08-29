<?php

abstract class NoticelistWidget extends ListWidget {
    static function run($args=null) {
        $class = get_class();
        $widget = new $class($args);    // runs validate()
        $widget->show();
    }

    function the_item($item) {
        NoticeWidget::run(array('notice'=>$item, 'out'=>$this->out));
    }
}

?>
