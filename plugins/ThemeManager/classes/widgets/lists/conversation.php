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
	
    function the_item($item) {
		// $item here is an array of all the Notice objects in the conversation
        ConversationWidget::run(array('list'=>$item, 'loopArgs'=>$this->convArgs, 'pagination'=>false));
    }
}

?>
