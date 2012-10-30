<?php

class ActivityWidget extends NoticeWidget {
    protected $widgetClass = 'notice activity';

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
        $this->widgetTag && $this->out->elementStart($this->widgetTag, array('id'=>'notice-'.$this->get_notice_id(), 'class'=>$this->widgetClass));
		$this->the_header();
		$this->the_content();
        $this->widgetTag && $this->out->elementEnd($this->widgetTag);
    }
}

?>
