<?php

class NoticeListWidget extends ListWidget {
	protected $list = null;

    static function run($args=null) {
        $class = get_class();
        $widget = new $class($args);    // runs validate()
        $widget->show();
    }
	protected function validate() {
		if (!is_null($this->list) && !is_array($this->list)) {
			return false;
		}

		return parent::validate();
	}

	function get_list() {
		return $this->list;
	}

    function the_item($item) {
        NoticeWidget::run(array('notice'=>$item));
    }
}

?>
