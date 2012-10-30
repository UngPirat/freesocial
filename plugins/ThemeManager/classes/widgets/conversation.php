<?php

class ConversationWidget extends NoticeListWidget {
    // these values will be set by default or $args-supplied values
    protected $widgetClass = 'conversation notices';
	protected $id = null;

    static function run(array $args=array()) {
        $class = get_class();
        $widget = new $class($args);    // runs validate()
        $widget->show();
    }

    protected function validate() {
		// prefer the given notice list over the conversation id
        if (is_null($this->list) && empty($this->id)) {
			return false;
		} elseif (is_null($this->list)) {
			$this->list = Conversation::getNotices($this->id);
		} else {
			// assume the conversation id is the first notice's conversation
			$this->id = $this->list[0]->conversation;
		}
		$this->widgetId = 'conversation-'.$this->id;

        return parent::validate();
    }

    function the_item($item) {
		$avatarSize = $this->key()>0 ? Avatar::REPLY_SIZE : Avatar::STREAM_SIZE;
        NoticeWidget::run(array('item'=>$item, 'avatarSize'=>$avatarSize));
        if (isset($item->showmore)) {
            $this->out->elementStart('aside', array('id'=>'more-'.$item->conversation, 'class'=>'show-more'));
            $href = common_local_url('conversation', array('id'=>$item->conversation)).'#notice-'.$item->id;
            $this->out->element('a', array('href'=>$href), sprintf(_m('Show %d hidden replies'), $item->showmore));
            $this->out->elementEnd('aside');
        }
    }
}
