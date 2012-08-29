<?php

class GrouplistWidget extends ListWidget {
    protected $num = 5;
    protected $itemClass   = 'group vcard';
    protected $widgetClass = 'groups';

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

    function get_list() {
        return $this->profile->getGroups(0, 0+$this->num, true);
    }

    function the_more() {
        $this->out->elementStart('div', 'list-more');
        $this->out->element('a', array('href'=>common_local_url('usergroups', array('nickname'=>$this->profile->nickname))),
                            _m('...show all groups.'));
        $this->out->elementEnd('div');
    }

    function the_item($item) {
        $this->out->elementStart('li', "list-item {$this->itemClass}");
        $this->out->elementStart('a', array('href'=>$item->uri, 'class'=>'url'));
        $this->out->element('img', array('src'=>$item->getAvatar(AVATAR_STREAM_SIZE), 'alt'=>'', 'class'=>'photo avatar'));
        $this->out->element('span', 'fn description', $item->fullname);
        $this->out->elementEnd('a');
        $this->out->elementEnd('li');
    }
}

?>
