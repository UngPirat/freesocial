<?php

class AttachmentListWidget extends ListWidget {
    protected $offset = 0;
    protected $num    = 10;

    protected $notice = null;
	protected $widgetTag = 'ul';
	protected $widgetClass = 'list attachments';

    static function run(array $args=array()) {
        $class = get_class();
        $widget = new $class($args);    // runs validate()
        $widget->show();
    }

    protected function validate() {
        if (!is_a($this->notice, 'Notice')) {
            return false;
        }

        return parent::validate();
    }

    function get_list() {
		return $this->notice->attachments();
    }

    function the_item($item) {
        PreviewWidget::run(array('item'=>$item));
    }
}

?>
