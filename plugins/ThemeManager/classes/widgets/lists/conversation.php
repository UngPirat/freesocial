<?php

class ConversationListWidget extends NoticeListWidget {
    protected $loopClass  = 'conversations';
    protected $loopId     = 'stream';
    protected $num        = -1;
    protected $pagination = false;
    protected $loopType   = 'Conversation';

    protected $convArgs   = array();

    static function run(array $args=array()) {
        $class = get_class();
        $widget = new $class($args);    // runs validate()
        $widget->show();
    }
    
    function the_item($item) {
        // $item here is an array of all the Notice objects in the conversation
        $this->itemTag && $this->out->elementStart($this->itemTag, $this->itemClass);
        ConversationWidget::run(array('list'=>$item, 'loopArgs'=>$this->convArgs, 'pagination'=>false));
        $this->itemTag && $this->out->elementEnd($this->itemTag);
    }
}

?>
