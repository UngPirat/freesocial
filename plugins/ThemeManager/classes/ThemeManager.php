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

        $this->action->extraHeaders();	// http headers

        include $this->get_template_file();	// we can do stuff like $this-> inside the template!
		$this->out->flush();
        
        return true;
    }

    function head() {
        if (Event::handle('StartShowHeadElements', array($this->action))) {
            if (Event::handle('StartTmStyles', array('action'=>$this->action))) {
                $this->the_styles();
                Event::handle('EndTmStyles', array('action'=>$this->action));
            }
            if (Event::handle('StartTmScripts', array('action'=>$this->action))) {
    			$this->the_scripts();
                Event::handle('EndTmScripts', array('action'=>$this->action));
    		}
            $this->the_feeds();
        	$this->out->flush();
            Event::handle('EndShowHeadElements', array($this->action));
        }
        $this->action->flush();	// I want to get rid of $this->action as output element!
    }

    function content($type) {
        if (Event::handle('StartShowContentBlock', array($this->action))) {
            $this->action->flush();
            $content = $this->sysdir . '/content/' . basename($type) . '.php';
            include $content;
            Event::handle('EndShowContentBlock', array($this->action));
        }
        $this->action->flush();	//...sigh, I haven't even bothered to look for autoflushing
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
            throw new Exception('Box not found', 404);	// kills the script in Action
        }

        $this->parse_args($args);
        include $box;
    }

    function loop($args, $type='Object') {
        $class = ucfirst($type).'Loop';
        $loop = new $class($args);
        return $loop;
    }
    function pagination(array $pages) {
        if (!isset($pages['current'])) {
            return;
        }
        $this->out->elementStart('nav', 'paging');
        foreach(array('prev'=>_m('Older posts'), 'next'=>_m('Newer posts')) as $key=>$trans) {
            if (!isset($pages[$key])) {
                continue;
            } else {
                $href = common_local_url($this->action->args['action'], $this->action->args, array('page'=>$pages[$key]));
            }
            $this->out->elementStart('span', $key);
            $this->out->element('a', array('href'=>$href, 'rel'=>$key), $trans);
            $this->out->elementEnd('span');
        }
        $this->out->elementEnd('nav');
        $this->out->flush();
    }

    function menu($name, array $args=array()) {
		if (!preg_match('/Menu$/', $name)) {
			$name .= 'Menu';
		}
        if (!is_subclass_of($name, 'ThemeMenu') && !is_subclass_of($name, 'Menu')) {
            throw new Exception('Not a menu');
        } elseif (is_subclass_of($name, 'Menu')) {
            $menu = new $name($this->action);	// getting rid of this in the future
        } else {
            $menu = new $name($args);	// new style menus
        }
        $menu->show();
    }

    function menus(array $list, array $args=array()) {
        $this->out->elementStart('nav', 'menu-container');
            $this->out->elementStart('ul', 'menu');
        foreach ($list as $menu) :
            try {
				$this->menu($menu, $args);	// no args allowed in multi-call... for now at least
			} catch (Exception $e) {
			}
        endforeach;
            $this->out->elementEnd('ul');
        $this->out->elementEnd('nav');
        $this->out->flush();
    }

    function widget($name, $args=null) {
        if (!preg_match('/^(\w+)Widget$/', $name)) {
            $name .= 'Widget';
        }
        if (!is_subclass_of($name, 'ThemeWidget')) {
            throw new Exception('Not a widget');
        }
        $name::run($args);
    }

    function widgets(array $list) {
        foreach($list as $name=>$args) {
            if (is_subclass_of($name, 'ThemeWidget')) {	// new style widgets
                $this->widget($name, $args);
            } elseif (is_subclass_of($name, 'Widget')) {	// old style widgets
                // oh man, this is ugly, but at least doesn't return errors. Glad to be getting rid of it.
                // call_user_func_array won't work will it?
                switch (count($args)) {
                case 0:	$name = new $name(); break;
                case 1:	$name = new $name($args[0]); break;
                case 2:	$name = new $name($args[0], $args[1]); break;
                case 3:	$name = new $name($args[0], $args[1], $args[2]); break;
                default: throw new Exception('Bad number of arguments');
                }
                $name->show();
                $this->action->flush();
                $this->out->flush();
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
