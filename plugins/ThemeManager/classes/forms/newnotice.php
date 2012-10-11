<?php

class NewnoticeForm extends ThemeForm {
    protected $content   = null;
    protected $returnto = null;
    protected $inreplyto = null;
    protected $private   = null;
    protected $charlimit  = null;

    static function run(array $args=array()) {
        $class = get_class();
        $widget = new $class($args);    // runs validate()
        $widget->show();
    }

    function initialize() {
        parent::initialize();

        $this->attributes['action'] = common_local_url('newnotice');

        if (empty($this->charlimit)) {
            $this->charlimit = Notice::maxContent();
        }
    }

    function the_elements() {
        $els = new FormElements($this->out);
        $legend = !empty($this->inreplyto)
                    ? _m('Reply to notice')
                    : _m('Send a notice');
        $els->legend($legend);

		$els->hidden('token', common_session_token());
        $els->textarea('status_textarea', array('class'=>'notice_data-text', 'cols'=>35, 'rows'=>4, 'id'=>'newnotice'), $this->content);
        $els->span('count', $this->charlimit);

        $els->submit('newnotice-submit', 'submit', (empty($this->inreplyto) ? _m('BUTTON', 'Send') : _m('BUTTON', 'Reply')));

        if (common_config('attachments', 'uploads')) {
			$this->out->elementStart('div', 'attachments');
            $els->hidden('MAX_FILE_SIZE', common_config('attachments', 'file_quota'));
            $els->file('attach[]', array('id'=>'newnotice-attach', 'multiple'=>'multiple'), _m('Attach a file.'));
			$this->out->elementEnd('div');
        }
        if (!empty($this->returnto)) {
            $els->hidden('returnto', $this->returnto, 'newnotice-returnto');
        }
        $els->hidden('inreplyto', $this->inreplyto, 'newnotice-inreplyto');
        return $els;
    }
}

?>
