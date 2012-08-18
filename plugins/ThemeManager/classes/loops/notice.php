<?php

class NoticeLoop extends ObjectLoop {
    function prefill() {
        $notices = $this->list->fetchAll();
        if (Event::handle('StartNoticeListPrefill', array(&$notices, AVATAR_STREAM_SIZE))) {
            Notice::fillAttachments($notices);
            Notice::fillFaves($notices);
            Notice::fillRepeats($notices);
            $profiles = Notice::fillProfiles($notices);

            Event::handle('EndNoticeListPrefill', array(&$notices, &$profiles, AVATAR_STREAM_SIZE));
        }
    }

    function the_avatar($size=AVATAR_STREAM_SIZE) {
        return $this->list->profile->getAvatar($size);
        
    }

    function the_avatarurl($size=AVATAR_STREAM_SIZE) {
        echo htmlspecialchars($this->list->profile->avatarUrl($size));
    }

    function the_name() {
        echo htmlspecialchars($this->list->profile->fullname);
    }

    function the_nickname() {
        echo htmlspecialchars($this->list->profile->nickname);
    }

    function the_profileurl() {
        echo htmlspecialchars($this->list->profile->profileurl);
    }
}
