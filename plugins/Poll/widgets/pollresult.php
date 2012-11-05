<?php

class PollResultWidget extends ThemeWidget {
	protected $item;

    static function run(array $args=array()) {
        $class = get_class();
        $widget = new $class($args);    // runs validate()
        $widget->show();
    }

	function validate() {
		if (!is_object($this->item)) {
			$this->item = Notice::staticGet('id'=>$this->item);
		}
		if (is_a($this->item, 'Notice')) {
			$this->item = Poll::getByNotice($this->item);
		}
		if (!is_a($this->item, 'Poll')) {
			return false;
		}

		return parent::validate();
	}

    function the_content() {
		$votes = $this->poll->countResponses();
		$total = $this->poll->totalVotes();

		foreach ($this->poll->getOptions() as $i=>$opt) {
//			$result = $total>0 ? intval($votes[$i] / $total * 100) : 0;

			$this->out->element('meter', array('value'=>$i, 'max'=>$total,
										'title'=>sprintf(_m('%s votes'), $votes[$i])
									));
		}
	}
}

