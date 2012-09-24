<?php

class PreviewWidget extends ThemeWidget {
    // these values will be set by default or $args-supplied values
    protected $item;
    protected $notices = array();

    protected $itemClass   = 'attachment preview';
    protected $widgetClass = 'previews';

    static function run($args=null) {
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
        $thumb = $this->item->getThumbnail(Avatar::PROFILE_SIZE);
        if (empty($thumb)) {
            return false;
        }

        $this->out->elementStart('li', "list-item {$this->itemClass}");
        $this->out->elementStart('a', array('href'=>common_local_url('attachment', array('attachment'=>$this->item->id)), 'class'=>'url'));
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
}

?>
