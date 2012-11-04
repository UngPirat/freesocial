<?php

class NoticeListWidget extends ListWidget {
    protected $list = null;
	protected $num = NOTICES_PER_PAGE;
    protected $pagination = true;
    protected $widgetClass = 'list notices';

    static function run(array $args=array()) {
        $class = get_class();
        $widget = new $class($args);    // runs validate()
        $widget->show();
    }
    protected function validate() {
        if (is_a($this->list, 'ArrayWrapper')) {
            $this->list = $this->list->fetchAll();
        }

        if (!is_null($this->list) && !is_array($this->list)) {
            return false;
        }

        return parent::validate();
    }

    function get_list() {
        return $this->list;
    }

    function the_item($item) {
/*        if ($item->hasConversation()) {
            ConversationListWidget::run(array('list'=>array($item), 'pagination'=>false));
        } else {
            NoticeWidget::run(array('item'=>$item));
    	}*/
        NoticeWidget::run(array('item'=>$item, 'widgetTag'=>$this->itemTag));
    }
}
