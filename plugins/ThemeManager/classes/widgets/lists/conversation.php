<?php

class ConversationListWidget extends NoticeListWidget {
	protected $widgetClass = 'conversations';
	protected $loopType = 'Conversation';
	protected $convArgs = array();

    static function run($args=null) {
        $class = get_class();
        $widget = new $class($args);    // runs validate()
        $widget->show();
    }

	function the_content() {
        try {
            $pages = $this->loop->get_paging($this->page);
        } catch (Exception $e) {
            $pages = array();
        }

		ThemeManager::pagination($pages);
		parent::the_content();
		ThemeManager::pagination($pages);
	}
	
    function the_item($item) {
		// $item here is an array of all the Notice objects in the conversation
        ConversationWidget::run(array('list'=>$item, 'loopArgs'=>$this->convArgs));
    }
}

?>
