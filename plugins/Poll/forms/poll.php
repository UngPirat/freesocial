<?php

class PollForm extends ThemeForm {
    protected $item   = null;

	protected $class = 'poll';

    static function run(array $args=array()) {
        $class = get_class();
        $widget = new $class($args);    // runs validate()
        $widget->show();
    }

	function validate() {
		if (!is_a($this->item, 'Poll')) {
			return false;
		}

		return parent::validate();
	}

    function initialize() {
        parent::initialize();

        $this->attributes['action'] = common_local_url('respondpoll', array('id'=>$this->item->id));
    }

    function the_elements() {
        $els = new FormElements($this->out);
        
		$legend = _m('Poll form');

        $els->legend($legend);
		
        $els->hidden('token', common_session_token());

        $els->textarea('status_textarea', array('class'=>'notice_data-text', 'cols'=>35, 'rows'=>4, 'id'=>$txtId), $this->content);
        $els->span('count', $this->charlimit);

        $els->submit($sbmId, 'submit', (empty($this->inreplyto) ? _m('BUTTON', 'Send') : _m('BUTTON', 'Reply')));

        if (common_config('attachments', 'uploads')) {
            $this->out->elementStart('div', 'attachments');
            $els->hidden('MAX_FILE_SIZE', common_config('attachments', 'file_quota'));
            $els->file('attach[]', array('id'=>$attId, 'multiple'=>'multiple'), _m('Attach a file.'));
            $this->out->elementEnd('div');
        }
        if (!empty($this->returnto)) {
            $els->hidden('returnto', $this->returnto, $retId);
        }
        $els->hidden('inreplyto', $this->inreplyto, $irtId);
        return $els;
    }
}

?>
