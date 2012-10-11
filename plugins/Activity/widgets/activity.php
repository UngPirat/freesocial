<?php

class ActivityWidget extends NoticeWidget {
    protected $itemClass = 'notice activity';
    protected $itemTag = 'article';

    static function run(array $args=array()) {
        $class = get_class();
        $widget = new $class($args);    // runs validate()
        $widget->show();
		Event::handle('EndRunNoticeWidget', array($widget));
    }

	function get_verb() {
		return _m('done');
	}
    function the_item() {
        $this->itemTag && $this->out->elementStart($this->itemTag, array('id'=>'notice-'.$this->get_notice_id(), 'class'=>$this->itemClass));
		$this->the_content();
		$this->the_metadata();
		$this->the_actions();
        $this->itemTag && $this->out->elementEnd($this->itemTag);
    }
}

?>
