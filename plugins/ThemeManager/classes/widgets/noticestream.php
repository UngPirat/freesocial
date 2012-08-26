<?php

abstract class NoticestreamWidget extends ThemeWidget {
    protected $num = 2;

    protected $noticeClass;
    protected $widgetClass;

    function show() {
        $this->the_stream();
        $this->out->flush();
    }

    abstract function get_stream();

    function the_stream() {
		$loop = new NoticeLoop($this->get_stream()->getNotices(0, $this->num));
        $this->out->elementStart('ul', "noticelist widget {$this->widgetClass}");
        while ($loop->next()) :
            $this->out->elementStart('li', "notice {$this->noticeClass}");
            NoticeWidget::run(array('notice'=>$loop->current(), 'out'=>$this->out, 'avatarSize'=>48));
            $this->out->elementEnd('li');
        endwhile;
        $this->out->elementEnd('ul');
    }
}

?>
