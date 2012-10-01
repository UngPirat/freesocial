<?php

class ProfileListWidget extends ListWidget {
    protected $num = 15;
    protected $itemClass   = 'profile';
    protected $widgetClass = 'profiles';

    protected $avatarSize  = Avatar::MINI_SIZE;
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

        if (!is_null($this->list) && !is_array($this->list)) {
            return false;
        }
        
        return parent::validate();
    }
    function get_list() {
        return $this->list;
    }

    function the_item($item) {
        $this->out->elementStart('li', "list-item {$this->itemClass}");
        VcardWidget::run(array('item'=>$item, 'avatarSize'=>$this->avatarSize));
        $this->out->elementEnd('li');
    }
}
