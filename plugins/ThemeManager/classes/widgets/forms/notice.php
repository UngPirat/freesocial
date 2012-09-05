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
        $els->legend(_('Send a notice'));
        $els->textarea('status_textarea', array('class'=>'notice_data-text', 'cols'=>35, 'rows'=>4), $this->content);
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

    function the_content() {
        $this->out->elementStart('fieldset');
        $this->out->element('legend', null, _('Send a notice'));

        $this->out->element('textarea', array('class' => 'notice_data-text',
                                                  'cols' => 35,
                                                  'rows' => 4,
                                                  'name' => 'status_textarea'),
                                $this->content);
        if ($this->charlimit) {
            $this->out->element('span', array('class'=>'count'), $this->charlimit);
        }

        if (common_config('attachments', 'uploads')) {
            $this->out->hidden('MAX_FILE_SIZE', common_config('attachments', 'file_quota'));
            $this->out->elementStart('label', array('class' => 'notice_data-attach'));
            // TRANS: Input label in notice form for adding an attachment.
            $this->out->text(_('Attach'));
            $this->out->element('input', array('class' => 'notice_data-attach',
                                               'type' => 'file',
                                               'name' => 'attach',
                                               // TRANS: Title for input field to attach a file to a notice.
                                               'title' => _('Attach a file.')));
            $this->out->elementEnd('label');
        }

        if (!empty($this->returnto)) {
            $this->out->hidden('notice_return-to', $this->returnto, 'returnto');
        }

        $this->out->hidden('notice_in-reply-to', $this->inreplyto, 'inreplyto');

        $this->out->elementEnd('fieldset');
    }

    function the_submit() {
        $this->out->element('input', array('id' => 'notice_action-submit',
                                           'class' => 'submit',
                                           'name' => 'status_submit',
                                           'type' => 'submit',
                                           // TRANS: Button text for sending notice.
                                           'value' => _m('BUTTON', 'Send')));
    }
}

?>
