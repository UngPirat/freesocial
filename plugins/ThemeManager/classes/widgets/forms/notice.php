<?php

class NoticeFormWidget extends FormWidget {
    protected $content   = null;
    protected $returnto = null;
    protected $inreplyto = null;
    protected $private   = null;
    protected $charlimit  = null;

    static function run($args=null) {
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

        $els->textarea('status_textarea', array('class'=>'notice_data-text', 'cols'=>35, 'rows'=>4, 'id'=>'newnotice'), $this->content);
        $els->span('count', $this->charlimit);
        if (common_config('attachments', 'uploads')) {
            $els->hidden('MAX_FILE_SIZE', common_config('attachments', 'file_quota'));
            $els->input('attach', 'file', _m('Attach a file.'));
        }
        if (!empty($this->returnto)) {
            $els->hidden('notice_return-to', $this->returnto, 'returnto');
        }
        $els->hidden('notice_in-reply-to', $this->inreplyto, 'inreplyto');
        return $els;
    }

    function the_submit() {
		$label = empty($this->inreplyto)
				? _m('BUTTON', 'Send')
				: _m('BUTTON', 'Reply');
        $this->out->element('input', array('id' => 'notice_action-submit',
                                           'class' => 'submit',
                                           'name' => 'status_submit',
                                           'type' => 'submit',
                                           // TRANS: Button text for sending notice.
                                           'value' => $label));
    }
}

?>
