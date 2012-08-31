<?php

class NoticeoptionsWidget extends ThemeWidget {
    // these values will be set by default or $args-supplied values
    protected $item;

    static function run($args=null) {
        $class = get_class();
        $widget = new $class($args);    // runs validate()
        $widget->show();
    }

    protected function validate() {
		if (!common_logged_in()) {
			return false;
		}
        if (!is_a($this->item, 'Notice')) {
            return false;
        }
        return parent::validate();
    }

	function get_options() {
		$options = array (
			'favor' => ($this->scoped->hasFave($this->item)
						? new DisfavorForm($this->out, $this->item)
						: new FavorForm($this->out, $this->item)),

			);
		if (in_array($this->item->scope, array(Notice::PUBLIC_SCOPE, Notice::SITE_SCOPE))) {
			if ($this->scoped->hasRepeated($this->item->id)) {
				$options['repeat'] = array('element' => 'span',
											'args'   => array('class'=>'option final repeated',
															'title'=>_m('You repeated this')),
											'content'=> '♻',
											);
			} else {
				$options['repeat'] = new RepeatForm($this->out, $this->item);
            }
		}
		if (!empty($this->item->repeat_of)) {
			$replyto = $this->item->profile_id;
			$inreplyto = $this->item->repeat_of;
		} else {
			$replyto = $this->item->profile_id;
			$inreplyto = $this->item->id;
		}
	    $options['reply']  = array('element'=>'a',
									'args'=>array('href' => common_local_url('newnotice', array(
																'replyto' => $replyto,
																'inreplyto' => $inreplyto)),
	           									'class' => 'option reply',
		                                        // TRANS: Link title in notice list item to reply to a notice.
        		                                'title' => _('Reply to this notice.')),
									'content'=>'↩',
									);
		return $options;
	}

    function show() {
		$this->out->elementStart('aside', 'notice-options');
		foreach ($this->get_options() as $action=>$data) {
			if (is_a($data, 'Form')) {
				$data->show();
			} elseif (is_array($data)) {
				$this->out->element($data['element'], $data['args'], $data['content']);
			}
		}
		$this->out->elementEnd('aside');
    }
}

?>
