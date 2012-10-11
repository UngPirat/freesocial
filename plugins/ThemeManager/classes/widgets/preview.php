<?php

class PreviewWidget extends ThemeWidget {
    // these values will be set by default or $args-supplied values
    protected $item;
    protected $notices = array();

    protected $itemClass   = 'attachment preview';
    protected $widgetClass = 'previews';

    static function run(array $args=array()) {
        $class = get_class();
        $widget = new $class($args);    // runs validate()
        $widget->show();
    }

    // always gets run on __construct, which is also called on ::run()
    protected function validate() {
        if (!is_a($this->item, 'File')) {
            return false;
        }
        foreach($this->notices as $notice) {
            if (!is_a($notice, 'Notice')) {
                return false;
            }
            if (!$notice->inScope($this->scoped)) {
                return false;
            }
        }
        return parent::validate();
    }

    function show() {
		if (false===$this->show_thumb()) {
			$this->show_link();
		}
    }

	function show_thumb() {
        $thumb = $this->item->getThumbnail(Avatar::PROFILE_SIZE);
        if (empty($thumb)) {
            return false;
        }
        $this->out->elementStart('li', "thumb {$this->itemClass}");
//        $this->out->elementStart('a', array('href'=>$this->item->url, 'class'=>'fancybox'));
        $this->out->elementStart('a', array('href'=>common_local_url('attachment', array('attachment'=>$this->item->id)), 'class'=>'url thumb'));
        $this->out->element('img', array('src'=>$thumb->url));
        if (!empty($this->notices)) {
            $this->out->elementStart('div', 'description');
            foreach($this->notices as $notice) {
                $this->out->element('span', 'notice', $notice->content);
            }
            $this->out->elementEnd('div');
        }
        $this->out->elementEnd('a');
        $this->out->elementEnd('li');
	}

	function show_link() {
        $this->out->elementStart('li', " link {$this->itemClass}");
		$title = !empty($this->item->title) ? $this->item->title : $this->item->url;
        $this->out->element('a', array('href'=>$this->item->url, 'rel'=>'external', 'class'=>'url link'), $title);

        $this->out->elementEnd('li');
	}
}

?>
