<?php

class NoticeactionsWidget extends ThemeWidget {
    // these values will be set by default or $args-supplied values
    protected $item;

    protected $widgetClass = '';
    protected $widgetTag   = 'aside';

    static function run(array $args=array()) {
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

    function get_actions() {
        $items = array();
         
        if (Event::handle('StartShowNoticeActions', array(&$items, $this->item, $this))) {
            $items['favor'] = ($this->scoped->hasFave($this->item)
                        ? new DisfavorForm($this->out, $this->item)
                        : new FavorForm($this->out, $this->item));
    
            if (in_array($this->item->scope, array(Notice::PUBLIC_SCOPE, Notice::SITE_SCOPE))) {
                if ($this->scoped->hasRepeated($this->item->id)) {
                    $items['repeat'] = array('element' => 'span',
                                                'args'   => array('class'=>'action final repeated',
                                                                'title'=>_m('You repeated this')),
                                                'content'=> ' ' . _m('BUTTON', 'Repeated'), // '',
                                                );
                } else {
                    $items['repeat'] = new RepeatForm($this->out, $this->item);
                }
            }
            if (!empty($this->item->repeat_of)) {
                $replyto = $this->item->profile_id;
                $inreplyto = $this->item->repeat_of;
            } else {
                $replyto = $this->item->profile_id;
                $inreplyto = $this->item->id;
            }
            $items['reply']  = array('element'=>'a',
                                        'args'=>array('href'   => common_local_url('reply', array(
                                                                    'id' => $inreplyto)),
                                                       'id'    => 'reply-'.$this->item->id,
                                                       'class' => 'action reply',
                                                    // TRANS: Link title in notice list item to reply to a notice.
                                                    'title' => _('Reply to this notice.')),
                                        'content'=>_m(' Reply'),    // ↩
                                        );
            if (!empty($this->scoped) &&
                ($this->item->profile_id == $this->scoped->id || $this->scoped->hasRight(Right::DELETEOTHERSNOTICE))) {
                $items['delete'] = array('element'=>'a',
                                        'args'=>array('href'  => common_local_url('delete', array('id' => $this->item->id)),
                                                      'id'    => 'delete-'.$this->item->id,
                                                      'class' => 'action delete',
                                               // TRANS: Link title in notice list item to delete a notice.
                                               'title'  => _('Delete this notice from the timeline.')),
                                           'content'=> _m(' Delete'),
                                           );
            }
            Event::handle('EndShowNoticeActions', array(&$items, $this->item, $this));
        }
        return $items;
    }

    function show() {
        $this->out->elementStart($this->widgetTag, "actions {$this->widgetClass}");
        foreach ($this->get_actions() as $action=>$data) {
            if (is_a($data, 'Form')) {
                $data->show();
            } elseif (is_array($data)) {
                $this->out->element($data['element'], $data['args'], $data['content']);
            }
        }
        $this->out->elementEnd($this->widgetTag);
    }
}

?>
