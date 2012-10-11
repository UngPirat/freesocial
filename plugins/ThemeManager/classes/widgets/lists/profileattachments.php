<?php

class ProfileattachmentsListWidget extends ListWidget {
    protected $offset = 0;
    protected $num    = 10;

    protected $profile;

    static function run(array $args=array()) {
        $class = get_class();
        $widget = new $class($args);    // runs validate()
        $widget->show();
    }

    protected function validate() {
        if (!is_a($this->profile, 'Profile')) {
            return false;
        }
        return parent::validate();
    }

    function get_list() {
        $ids = array();
        $this->notices = array();
        do {
            $f2p = new File_to_post;
            $f2p->protected = null;
            @$f2p->joinAdd(array('post_id', 'notice:id'));
            @$f2p->joinAdd(array('file_id', 'file:id'));
            @$f2p->joinAdd(array('file_id', 'file_thumbnail:file_id'));
            $f2p->whereAdd('profile_id = '.$this->profile->id);
            $f2p->whereAdd('mimetype LIKE "image/%"');
            $f2p->groupBy('post_id');
            $f2p->orderBy('post_id DESC');
            $f2p->limit($this->offset, $this->offset+$this->num+1);

            if ($f2p->find()) :
                while ($f2p->fetch()) :
                    if (count($ids) == $this->num) {
                        break;
                    }
                    $notice = Notice::staticGet('id', $f2p->post_id);
                    if (!$notice->inScope($this->scoped)) {
                        continue;
                    }
                    $this->notices[$f2p->file_id][] = $notice;
                    $ids[$f2p->file_id] = true;
                endwhile;
            endif;
            $this->offset += $f2p->N;
        } while (count($ids) < $this->num && $f2p->N > $this->num);
        
        return Memcached_DataObject::multiGet('File', 'id', array_keys($ids));
    }

    function the_item($item) {
        PreviewWidget::run(array('item'=>$item, 'notices'=>$this->notices[$item->id]));
    }
}

?>
