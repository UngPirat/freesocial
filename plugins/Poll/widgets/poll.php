<?php

class PollWidget extends NoticeWidget {
    protected $widgetClass = 'notice poll';

    static function run(array $args=array()) {
        $class = get_class();
        $widget = new $class($args);    // runs validate()
        $widget->show();
		Event::handle('EndRunNoticeWidget', array($widget));
    }

	function initialize() {
		parent::initialize();

        $this->poll = Poll::getByNotice($this->item);
	}

	function get_verb() {
		return _m('polled');
	}
    function the_content() {
		$this->out->flush();	// PHP crashes (memory limit?) if we don't flush once in a while

        if (empty($this->poll)) {
            common_log(LOG_ERR, 'No poll for notice '.$this->get_notice_id());
			parent::the_content();
            return;
        }

        $this->out->elementStart('span', array('class' => 'content'));

        $attrs = array('href' => $poll->url,
                       'class' => 'poll-title',
					   'rel' => 'nofollow external');

        $this->out->elementStart('h3');
        $this->out->element('a',
                      $attrs,
                      $happening->title);
        $this->out->elementEnd('h3');

		if ($poll->getResponse($this->scoped)) {
			PollResultWidget::run(array('item'=>$poll, 'scoped'=>$this->scoped));
		} else {
			PollForm::run(array('item'=>$poll, 'scoped'=>$this->scoped));
		}

        $this->out->elementEnd('span');
    }
}

