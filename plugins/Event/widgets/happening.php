<?php

class HappeningWidget extends NoticeWidget {
    protected $widgetClass = 'notice happening';

    static function run(array $args=array()) {
        $class = get_class();
        $widget = new $class($args);    // runs validate()
        $widget->show();
		Event::handle('EndRunNoticeWidget', array($widget));
    }

	function get_verb() {
		return _m('announced');
	}
    function the_content() {
		$this->out->flush();	// PHP crashes (memory limit?) if we don't flush once in a while
        $happening = Happening::fromNotice($this->item);

        if (empty($happening)) {
            common_log(LOG_ERR, 'No happening for notice '.$this->get_notice_id());
			parent::the_content();
            return;
        }

        $this->out->elementStart('span', array('class' => 'entry-content'));

        $attrs = array('href' => $happening->url,
                       'class' => 'happening-title',
					   'rel' => 'nofollow external');

        $this->out->elementStart('h3');
        $this->out->element('a',
                      $attrs,
                      $happening->title);
        $this->out->elementEnd('h3');

		$this->out->elementStart('ul', 'happening-data');
		foreach (array('start_time', 'end_time', 'location') as $property) :
			$this->show_property($happening, $property);
		endforeach;
		$this->out->elementEnd('ul');

        if (!empty($happening->description)) {
            $this->out->element('p',
                          array('class' => 'happening description'),
                          $happening->description);
        }

        $this->out->elementEnd('span');
    }

	function show_property(Happening $happening, $property, $tag='li') {
		$this->out->elementStart($tag, $property);
		switch ($property) {
		case 'start_time':
		case 'end_time':
			$style = _m('%e %B %Y (%A) at %R');
			$time = strftime($style, strtotime($happening->$property));
			$this->out->element('span', $property, $time);
			break;
		case 'location':
			
		}
		$this->out->elementEnd($tag);
	}
}

