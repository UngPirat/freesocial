<?php

class ProfilestreamWidget extends NoticestreamWidget {
    protected $profile;    // used for scoping

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
        $stream = new ProfileNoticeStream($this->profile, $this->scoped);
        return $stream;
    }
}

?>
