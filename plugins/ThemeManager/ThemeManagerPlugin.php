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
        case 'ThemeForm':
        case 'ThemeManager':
        case 'ThemeMenu':
        case 'ThemeSite':
        case 'ThemeWidget':
            $file = dirname(__FILE__) . '/classes/' . $cls . '.php';
            require_once($file);
            return false;
            break;
        }

        if (preg_match('/^(\w+)(List)(Widget)$/', $cls, $type)) {
            $type = array_map('strtolower', array_map('basename', $type));
            $file = dirname(__FILE__) . "/classes/{$type[3]}s/{$type[2]}s/{$type[1]}.php";
        } elseif (preg_match('/^(\w+)(Form|Loop|Menu|Widget)$/', $cls, $type)) {
            $type = array_map('strtolower', array_map('basename', $type));
            $file = dirname(__FILE__) . "/classes/{$type[2]}s/{$type[1]}.php";
        }

        if (!is_null($file) && file_exists($file)) {
            require_once($file);
            return false;
        }
        return true;
    }

    function onGetTmSupported(&$supported) {
        $supported = array_merge($supported, array(
                'newnotice' => 'newnotice',
                'public' => 'public',
                'settings' => 'settings',
                'showstream' => 'profile',
                ));
    }


    function onStartTmConversationList($list, $num) {
        if (defined('THEME_MANAGER') && THEME_MANAGER===true) {
            ConversationListWidget::run(array('list'=>$list, 'num'=>$num));
            return false;
        }
        return true;
    }
    function onStartTmNoticeList($list, $num) {
        if (defined('THEME_MANAGER') && THEME_MANAGER===true) {
            NoticeListWidget::run(array('list'=>$list, 'num'=>$num));
            return false;
        }
        return true;
    }
    function onStartShowNoticeItem($noticeitem) {
        if (defined('THEME_MANAGER') && THEME_MANAGER===true) {
            NoticeWidget::run(array('item'=>$noticeitem->notice, 'itemTag'=>'li'));
            return false;
        }
        return true;
    }
    function onStartOpenNoticeListItemElement($noticeitem) {
        return !(defined('THEME_MANAGER') && THEME_MANAGER===true);
    }

    function onStartCloseNoticeListItemElement($noticeitem) {
        return !(defined('THEME_MANAGER') && THEME_MANAGER===true);
    }

    function onStartInitializeRouter($m)
    {
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
