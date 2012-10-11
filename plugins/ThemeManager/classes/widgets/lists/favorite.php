<?php

class FavoriteListWidget extends NoticeListWidget {
    protected $offset = 0;
    protected $num    = 2;

    protected $itemClass   = 'favorite notice';
    protected $widgetClass = 'list favorites';

    protected $profile;

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

    function get_list() {
        $faves   = Fave::byProfile($this->profile->id, $this->offset, $this->num);
        $notices = array();
        foreach($faves->fetchAll() as $fave) :
            $notices[] = Notice::staticGet('id', $fave->notice_id);
        endforeach;
        return $notices;
    }

    function the_more() {
        $this->out->elementStart('div', 'list-more');
        $this->out->element('a', array('href'=>common_local_url('favorites', array('nickname'=>$this->profile->nickname))),
                            _m('...show all faves.'));
        $this->out->elementEnd('div');
    }
}

?>
