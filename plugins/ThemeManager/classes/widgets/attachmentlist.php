<?php

class AttachmentlistWidget extends ListWidget {
    protected $num = 10;
    protected $itemClass   = 'attachment';
    protected $widgetClass = 'attachments';

	protected $profile;

    static function run($args=null) {
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
        $f2p  = new File_to_post;
        @$f2p->joinAdd(array('post_id', 'notice:id'));
        @$f2p->joinAdd(array('file_id', 'file:id'));
        $f2p->whereAdd('profile_id = '.$this->profile->id);
        $f2p->whereAdd('mimetype LIKE "image/%"');
        $f2p->orderBy('post_id DESC');
        $f2p->limit($this->offset, $this->num);

        $ids = array();
        if ($f2p->find()) :
            while ($f2p->fetch()) {
                $ids[$f2p->file_id][] = $f2p->post_id;
            }
        endif;
        $this->ids = $ids;
        
        return Memcached_DataObject::multiGet('File', 'id', array_keys($ids));
    }

    function the_item($item) {
        $thumb = $item->getThumbnail(AVATAR_PROFILE_SIZE);
        $notice = Notice::staticGet('id', $this->ids[$item->id][0]);

        $this->out->elementStart('a', array('href'=>common_local_url('attachment', array('attachment'=>$item->id)), 'class'=>'url'));
        $this->out->element('img', array('src'=>$thumb->url, 'class'=>'photo'));
        $this->out->element('span', 'title', $notice->content);
        $this->out->elementEnd('a');
    }
}

?>
