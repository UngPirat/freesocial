<?php

class ConversationWidget extends NoticeListWidget {
    // these values will be set by default or $args-supplied values
    protected $widgetClass = 'conversation notices';

    static function run(array $args=array()) {
        $class = get_class();
        $widget = new $class($args);    // runs validate()
        $widget->show();
    }

    function the_item($item) {
		$avatarSize = $this->key()>0 ? Avatar::STREAM_SIZE : Avatar::PROFILE_SIZE;
        NoticeWidget::run(array('item'=>$item, 'avatarSize'=>$avatarSize));
        if (isset($item->showmore)) {
            $this->out->elementStart('aside', array('id'=>'more-'.$item->conversation, 'class'=>'show-more'));
            $href = common_local_url('conversation', array('id'=>$item->conversation)).'#notice-'.$item->id;
            $this->out->element('a', array('href'=>$href),
                                sprintf(_m('Show %d hidden replies'), $item->showmore));
            $this->out->elementEnd('aside');
        }
    }
}

?>
