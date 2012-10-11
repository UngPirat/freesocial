<?php

class AttachmentWidget extends ThemeWidget {
    // these values will be set by default or $args-supplied values
    protected $item;
    protected $notices = array();

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
        }
        return parent::validate();
    }

    function show() {
        $this->out->elementStart('marquee', array('direction'=>'right','scrolldelay'=>'50'));
        $this->out->elementStart('a', array('href'=>$this->item->url, 'alt'=>_m('Full size'), 'class'=>'url'));
        $this->out->element('img', array('src'=>$this->item->url, 'alt'=>'', 'class'=>'photo'));
        if (!empty($this->notices)) {
            $this->out->elementStart('div', 'description');
            foreach($this->notices as $notice) {
                $this->out->element('span', 'notice', $notice->content);
            }
            $this->out->elementEnd('div');
        }
        $this->out->elementEnd('a');
        $this->out->elementEnd('marquee');
    }
}

?>
