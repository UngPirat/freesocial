<?php

class ThemeManager extends ThemeSite {
    protected $boxes;
    protected $name;

    static protected $htmloutputter = null;

    function __construct($action)
    {
        // get possible user config and set $theme to user's theme
        $theme_name = common_config('site', 'theme');

        $this->name = ucfirst($theme_name).'Theme';
        $this->sysdir = INSTALLDIR . "/theme/{$this->name}";
        $this->urldir = 'theme/' . urlencode($this->name);

        if (is_null(self::$htmloutputter)) {
            self::$htmloutputter = $action;
        }
        $this->out = self::$htmloutputter;

        parent::__construct($action);
    }

    static public function getOut()
    {
        if (is_null(self::$htmloutputter)) {
            self::$htmloutputter = new HTMLOutputter;
        }
        return self::$htmloutputter;
    }

	function loggedIn() {
		return isset($this->profile) && !empty($this->profile);
	}

    function run() {
        if (empty($type)) {
            $httpaccept = isset($_SERVER['HTTP_ACCEPT']) ?
              $_SERVER['HTTP_ACCEPT'] : null;

            // XXX: allow content negotiation for RDF, RSS, or XRDS

            $cp = common_accept_to_prefs($httpaccept);
            $sp = common_accept_to_prefs(PAGE_TYPE_PREFS);

            $type = common_negotiate_type($cp, $sp);

            if (!$type) {
                // TRANS: Client exception 406
                throw new ClientException(_('This page is not available in a '.
                                            'media type you accept'), 406);
            }
        }

        header('Content-Type: '.$type);

        // Output anti-framing headers to prevent clickjacking (respected by newer browsers).
        if (common_config('javascript', 'bustframes')) {
            header('X-XSS-Protection: 1; mode=block'); // detect XSS Reflection attacks
            header('X-Frame-Options: SAMEORIGIN'); // no rendering if origin mismatch
        }

        $this->action->extraHeaders();    // http headers

		$this->render();

        return true;
    }

	function render() {
		if ($this->action->boolean('ajax')) {
			$this->ajax();
		} else {
			$this->box('header');
            include $this->get_template_file();    // we can do stuff like $this-> inside the template!
			$this->box('footer');
		}
		$this->out->flush();
	}

	function ajax() {
        header('Content-Type: text/xml;charset=utf-8');
        $this->out->xw->startDocument('1.0', 'UTF-8');
/*        $this->out->elementStart('html');
        $this->out->elementStart('head');
        $this->out->element('title', null, $this->get_title());
        $this->out->elementEnd('head');
        $this->out->elementStart('body');
		$this->out->flush();*/
		$this->content($this->get_content_name());
/*        $this->out->elementEnd('body');
        $this->out->elementEnd('html');*/
	}

    function head() {
        if (Event::handle('StartShowHeadElements', array($this->action))) {
            if (Event::handle('StartTmStyles', array('action'=>$this->action))) {
                $this->the_styles();
                Event::handle('EndTmHeadStyles', array('action'=>$this->action));
            }
            if (Event::handle('StartTmHeadScripts', array('action'=>$this->action))) {
                $this->the_scripts();
                Event::handle('EndTmHeadScripts', array('action'=>$this->action));
            }
            $this->the_feeds();
			$this->action->extraHead();
            Event::handle('EndShowHeadElements', array($this->action));
        }
        $this->out->flush();
    }
    function foot() {
        if (Event::handle('StartTmFootScripts', array('action'=>$this->action))) {
        	$this->action->showTmScripts();
            Event::handle('EndTmFootScripts', array('action'=>$this->action));
		}
	}

    function content($type) {
        if (Event::handle('StartShowContentBlock', array($this->action))) {
            $content = $this->sysdir . '/content/' . basename($type) . '.php';
            include $content;
            Event::handle('EndShowContentBlock', array($this->action));
        }
        $this->out->flush();
    }

    function get_title() {
        return $this->action->title();
    }
    function get_lang() {
        return common_config('site', 'language');
    }

    function box($name, $args=array()) {
        $box = $this->sysdir . '/boxes/' . basename($name) . '.php';
        if (!file_exists($box)) {
            throw new Exception('Box not found', 404);    // kills the script in Action
        }

        $this->parse_args($args);
        include $box;
        $this->out->flush();
    }

    function loop($args, $type='Object') {
        $class = ucfirst($type).'Loop';
        $loop = new $class($args);
        return $loop;
    }
    static function pagination(array $pages, array $action=array()) {
		$action = self::getOut();	//TODO: this has to be improved for the action data
        if (!isset($pages['current'])) {
            return;
        }
        $action->elementStart('nav', 'paging');
        foreach(array('prev'=>_m('Older'), 'next'=>_m('Newer')) as $key=>$trans) {
            $action->elementStart('span', $key);
            if (isset($pages[$key])) {
                $href = common_local_url($action->args['action'], $action->args, array('page'=>$pages[$key]));
                $action->element('a', array('class'=>$key, 'href'=>$href, 'rel'=>$key), $trans);
            } else {
                $action->text($trans);
            }
            $action->elementEnd('span');
        }
        $action->elementEnd('nav');
    }

    function menu($name, array $args=array()) {
        if (!preg_match('/Menu$/', $name)) {
            $name .= 'Menu';
        }
        if (!is_subclass_of($name, 'ThemeMenu') && !is_subclass_of($name, 'Menu')) {
            throw new Exception('Not a menu');
        } elseif (is_subclass_of($name, 'Menu')) {
            $menu = new $name($this->action);    // getting rid of this in the future
        } else {
			$args['action'] = $this->action;
            $menu = new $name($args);    // new style menus
        }
        $menu->show();
        $this->out->flush();
    }

    function menus(array $list, array $args=array()) {
        $this->out->elementStart('nav', 'menu-container'.(isset($args['navClass'])?" {$args['navClass']}":''));
            $this->out->elementStart('ul', 'menu');
		$args = array_merge($args, array('loopClass'=>'sub-menu', 'widgetTag'=>'li'));
        foreach ($list as $menu) :
            try {
                $this->menu($menu, $args);    // no args allowed in multi-call... for now at least
            } catch (Exception $e) {
            }
        endforeach;
            $this->out->elementEnd('ul');
        $this->out->elementEnd('nav');
    }

    function widget($name, $args=null) {
        if (!preg_match('/^(\w+)Widget$/', $name)) {
            $name .= 'Widget';
        }
        if (!is_subclass_of($name, 'ThemeWidget')) {
            throw new Exception('Not a widget');
        }
        $name::run($args);
        $this->out->flush();
    }

    function widgets(array $list) {
        foreach($list as $name=>$args) {
            if (is_subclass_of($name, 'ThemeWidget')) {    // new style widgets
                $this->widget($name, $args);
            } elseif (is_subclass_of($name, 'Widget')) {    // old style widgets
                // oh man, this is ugly, but at least doesn't return errors. Glad to be getting rid of it.
                // call_user_func_array won't work will it?
                switch (count($args)) {
                case 0:    $name = new $name(); break;
                case 1:    $name = new $name($args[0]); break;
                case 2:    $name = new $name($args[0], $args[1]); break;
                case 3:    $name = new $name($args[0], $args[1], $args[2]); break;
                default: throw new Exception('Bad number of arguments');
                }
                $name->show();
            } else {
                throw new Exception('Not a widget');
            }
        }
    }

    function parse_args(&$args) {
        if (!is_array($args)) {
            parse_str($args, $args);
        }
    }
}

class ThemeManagerAdapter {
    protected $items = array();

    function __construct(&$items, $action) {
        $this->items =& $items;
        $this->action = $action;
        if (is_a($this->action, 'Action')) {
            $this->actionName = $action->trimmed('action');
        }
        $this->out    = $this;
    }
    
    function trimmed($action) {
        return $this->action->trimmed($action);
    }
    function menuItem($url, $label, $description, $current=false) {
        $item = array();
        foreach(array('url', 'label', 'description', 'current') as $arg) {
            $item[$arg]  = $$arg;
        }    // the above should be put into items instead of below array
        $this->items[] = $item;
    }
    function getOut() {
        return $this->action;
    }
}
