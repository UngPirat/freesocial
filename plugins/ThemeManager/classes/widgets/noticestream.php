<?php

abstract class NoticestreamWidget extends ListWidget {
    protected $offset = 0;
    protected $num    = 5;

    protected $title = null;
    protected $itemClass   = 'notice';
    protected $widgetClass = 'notices';

    abstract function get_stream();

    function get_list() {
        return $this->get_stream()->getNotices($this->offset, $this->num);
    }

    function the_item($item) {
        NoticeWidget::run(array('notice'=>$item));
    }
}

?>
