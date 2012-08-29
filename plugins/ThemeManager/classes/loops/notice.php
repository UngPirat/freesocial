<?php

class NoticeLoop extends ObjectLoop {
    function prefill() {
        if (Event::handle('StartNoticeListPrefill', array(&$this->list, AVATAR_STREAM_SIZE))) {
            Notice::fillAttachments($this->list);
            Notice::fillFaves($this->list);
            Notice::fillRepeats($this->list);
            $profiles = Notice::fillProfiles($this->list);

            Event::handle('EndNoticeListPrefill', array(&$this->list, &$profiles, AVATAR_STREAM_SIZE));
        }
    }
}
