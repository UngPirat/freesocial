<?php

class ProfileListWidget extends ListWidget {
    protected $num = 15;
    protected $itemClass   = 'profile';
    protected $widgetClass = 'profiles';

    protected $avatarSize  = Avatar::PROFILE_SIZE;
    protected $pagination  = true;
	protected $showCount   = true;
    protected $mini        = false;
    protected $item        = null;
    protected $list        = null;

    static function run($args=null) {
        $class = get_class();
        $widget = new $class($args);    // runs validate()
        $widget->show();
    }

    protected function validate() {
        if (!is_a($this->item, 'Profile')) {
            return false;
        }

        if (!is_null($this->list) && !is_array($this->list)) {
            return false;
        }
        
        return parent::validate();
    }

	function initialize() {
		parent::initialize();

		$this->widgetClass .= ($this->mini ? ' mini-list' : ' list');
	}
	
    function get_list() {
        return $this->list;
    }

    function the_item($item) {
        $this->out->elementStart('li', "list-item {$this->itemClass}");
		ProfileWidget::run(array('item'=>$item, 'avatarSize'=>$this->avatarSize, 'mini'=>$this->mini));
        $this->out->elementEnd('li');
    }

	function get_count() {
		return false;
	}
	function the_title() {
        if (!empty($this->title)) {
            $this->out->elementStart('h3', 'widget-title');
			$this->titleLink && $this->out->elementStart('a', array('href'=>$this->titleLink));
			$this->out->text($this->title);
			$this->titleLink && $this->out->elementEnd('a');
			if ($this->showCount && $this->get_count()!==false) {
				$this->out->element('span', 'count', sprintf('(%d)', $this->get_count()));
			}
			$this->out->elementEnd('h3');
        }
	}
}
