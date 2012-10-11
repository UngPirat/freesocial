<?php

class SitefavoritesWidget extends NoticestreamWidget {
    protected $itemClass   = 'notice favorite';
    protected $widgetClass = 'favorites';

    protected $profile;    // used for scoping

    static function run(array $args=array()) {
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
        $stream = new PopularNoticeStream($this->scoped);
        return $stream;
    }
}

?>
