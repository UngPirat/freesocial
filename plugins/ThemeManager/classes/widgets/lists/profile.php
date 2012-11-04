<?php

abstract class ProfileListWidget extends ListWidget {
    protected $num = 15;
    protected $itemClass   = 'profile';
	protected $itemTag     = 'li';
    protected $widgetClass = 'profiles';

	// must be set
	protected $action = null;

    protected $avatarSize  = Avatar::PROFILE_SIZE;
    protected $pagination  = true;
	protected $showCount   = true;
    protected $mini        = false;
    protected $item        = null;
    protected $list        = null;

    static function run(array $args=array()) {
        $class = get_class();
        $widget = new $class($args);    // runs validate()
        $widget->show();
    }

    protected function validate() {
		if ($this->action == null) {
			return false;
		}

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
		$this->itemClass   .= ($this->mini ? ' vcard mini' : '');
	}
	
    function get_list() {
        return $this->list;
    }

    function the_item($item) {
		VcardWidget::run(array('item'=>$item, 'itemTag'=>$this->itemTag, 'avatarSize'=>$this->avatarSize, 'mini'=>$this->mini));
    }

	function get_page_action() {
		return $this->action;
	}

	function get_page_link() {
		return common_local_url($this->action, array('nickname'=>$this->item->nickname));
	}
	function get_count() {
		return false;
	}
	function the_title() {
        if (!empty($this->title)) {
            $this->out->elementStart($this->titleTag, 'widget-title');
			$this->out->elementStart('a', array('href'=>$this->get_page_link()));
			$this->out->text($this->title);
			if ($this->showCount && $this->get_count()!==false) {
				$this->out->element('span', 'count', sprintf('(%d)', $this->get_count()));
			}
			$this->out->elementEnd('a');
			$this->out->elementEnd($this->titleTag);
        }
	}
}
