<?php

class ProfilestreamListWidget extends NoticeListWidget {
    protected $offset = 0;
    protected $num    = 10;

    protected $profile = null;

    protected $title = null;
    protected $itemClass   = 'notice';
    protected $widgetClass = 'notices';

    static function run($args=null) {
        $class = get_class();
        $widget = new $class($args);    // runs validate()
        $widget->show();
    }
	protected function validate() {
		if (!is_a($this->profile, 'Profile')) {
			return false;
		}

		return parent::validate();
	}

    function get_list() {
		$stream = new ProfileNoticeStream($this->profile, $this->scoped); 
        return $stream->getNotices($this->offset, $this->num);
    }
}

?>
