<?php

class FavoritesWidget extends NoticestreamWidget {
    protected $noticeClass = 'favorite';
    protected $widgetClass = 'favorites';

	protected $profile;

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

    function get_stream() {
        $stream = new PopularNoticeStream($this->profile);
        return $stream;
    }
}

?>
