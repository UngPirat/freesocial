<?php

class ThemeManagerPlugin extends Plugin {
    /**
     * Load related modules when needed
     *
     * @param string $cls Name of the class to be loaded
     *
     * @return boolean hook value; true means continue processing, false means stop.
     */
    function onAutoload($cls)
    {
        $file = null;
        switch($cls) {
        case 'ThemeExtension':
        case 'ThemeManager':
        case 'ThemeMenu':
        case 'ThemeSite':
        case 'ThemeWidget':
            $file = dirname(__FILE__) . '/classes/' . $cls . '.php';
            require_once($file);
            return false;
            break;
        case 'ShowprofileAction':
            $file = dirname(__FILE__) . '/actions/' . strtolower(substr($cls, 0, -6)) . '.php';
            require_once($file);
            return false;
            break;
        }

        if (preg_match('/^(\w+)(Form|List)(Widget)$/', $cls, $type)) {
            $type = array_map('strtolower', array_map('basename', $type));
            $file = dirname(__FILE__) . "/classes/{$type[3]}s/{$type[2]}s/{$type[1]}.php";
        } elseif (preg_match('/^(\w+)(Loop|Menu|Widget)$/', $cls, $type)) {
            $type = array_map('strtolower', array_map('basename', $type));
            $file = dirname(__FILE__) . "/classes/{$type[2]}s/{$type[1]}.php";
        }

        if (!is_null($file) && file_exists($file)) {
            require_once($file);
            return false;
        }
        return true;
    }
	function onStartShowNoticeItem($noticeitem) {
        if (THEME_MANAGER===true) {
            NoticeWidget::run(array('item'=>$noticeitem->notice, 'itemTag'=>'li'));
			return false;
        }
		return true;
	}
	function onStartOpenNoticeListItemElement($noticeitem) {
		return !(THEME_MANAGER===true);
	}

	function onStartCloseNoticeListItemElement($noticeitem) {
		return !(THEME_MANAGER===true);
	}

    function onStartInitializeRouter($m)
    {
        // legacy
        $m->connect(':nickname',
                    array('action' => 'showprofile'),
                    array('nickname' => Nickname::DISPLAY_FMT));
        $m->connect(':nickname/',
                    array('action' => 'showprofile'),
                    array('nickname' => Nickname::DISPLAY_FMT));
        return true;
    }

    function onStartShowHTML($action)
    {  
        try {
            $tm = new ThemeManager($action);
            return !$tm->run();
        } catch (Exception $e) {
            if ($e->getCode() != 302) {
                return false;
            }
        }
    }

    function onStartShowHead($action)
    {  
        return THEME_MANAGER!==true;
    }

    function onStartShowBody($action)
    {  
        return THEME_MANAGER!==true;
    }

    function onStartEndHTML($action)
    {  
        return THEME_MANAGER!==true;
    }
}
