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
        switch($cls) {
        case 'ThemeManager':
        case 'ThemeWidget':
            $file = dirname(__FILE__) . '/classes/' . $cls . '.php';
            break;
        case 'NoticeWidget':
            $type = preg_replace('/^(\w+)Widget$/', '\1', $cls);
            if (!$type || $type == $cls) throw new Exception('Bad Widget class name');	// preg_replace hasn't extracted type!
            $file = dirname(__FILE__) . '/classes/widgets/' . strtolower($type) . '.php';
            break;
        case 'NoticeLoop':
        case 'ObjectLoop':
            $type = preg_replace('/^(\w+)Loop$/', '\1', $cls);
            if (!$type || $type == $cls) throw new Exception('Bad Loop class name');	// preg_replace hasn't extracted type!
            $file = dirname(__FILE__) . '/classes/loops/' . strtolower($type) . '.php';
            break;
        default:
            return true;
        }

        require_once($file);
        return false;
    }

    function onStartShowPage($action) {
        try {
            $tm = new ThemeManager($action);
            return !$tm->run();
        } catch (Exception $e) {
            if ($e->getCode() != 302) {
                return false;
            }
        }
    }

}
