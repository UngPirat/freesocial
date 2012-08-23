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
        case 'ThemeExtension':
        case 'ThemeManager':
        case 'ThemeMenu':
        case 'ThemeSite':
        case 'ThemeWidget':
            $file = dirname(__FILE__) . '/classes/' . $cls . '.php';
            require_once($file);
            return false;
            break;
        }

        if (!preg_match('/^(\w+)(Loop|Menu|Widget)$/', $cls, $type)) {
            return true;
        }
        $type = array_map('strtolower', array_map('basename', $type));
        $file = dirname(__FILE__) . "/classes/{$type[2]}s/{$type[1]}.php";
        if (!file_exists($file)) {
            return true;	// keep processing
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
