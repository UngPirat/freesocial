<?php

class ThemeManager extends ThemeSite {
    protected $boxes;
    protected $name;

    protected $urldir;
    protected $sysdir;

    protected $out;

    function __construct($action)
    {
        // get possible user config and set $theme to user's theme
        $theme_name = common_config('site', 'theme');

        $this->name = ucfirst($theme_name).'Theme';
        $this->sysdir = INSTALLDIR . "/theme/{$this->name}";
        $this->urldir = '/theme/' . urlencode($this->name);

        $this->out = new HTMLOutputter;	// ...not sure if action should stay or go... sorry...

        parent::__construct($action);
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

        include $this->get_template();	// we can do stuff like $this-> inside the template!
        
        return true;
    }

    function head() {
        if (Event::handle('StartShowHeadElements', array($this->action))) {
            $this->stylesheets();
            $this->the_feeds();
            $this->action->extraHead();	// html head tags
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

    function the_title() {
        echo htmlspecialchars($this->action->title());
    }
    function the_lang() {
        echo htmlspecialchars(common_config('site', 'language'));
    }

    function the_feeds()
    {
        foreach ((array)$this->action->getFeeds() as $feed) {	// should we get these as an event?
            $this->out->element('link', array('rel' => $feed->rel(),
                                         'href' => $feed->url,
                                         'type' => $feed->mimeType(),
                                         'title' => $feed->title));
        }
        $this->out->flush();
    }

    function stylesheets() {
        if (Event::handle('StartShowStyles', array($this->action))) {
            if (Event::handle('StartShowStatusNetStyles', array($this->action))) {
                $this->action->flush();	//...sigh, I haven't even bothered to look for autoflushing
                $this->the_style();
                Event::handle('EndShowStatusNetStyles', array($this->action));
            }
            Event::handle('EndShowStyles', array($this->action));
        }
        $this->action->flush();	//...sigh, I haven't even bothered to look for autoflushing
    }

    function the_style() {
        $this->out->element('link', array('rel' => 'stylesheet',
                                            'type' => 'text/css',
                                            'href' => $this->urldir . '/css/main.css'));
        $this->out->flush();
    }

    function box($name, $args=array()) {
        $box = $this->sysdir . '/boxes/' . basename($name) . '.php';
        if (!file_exists($box)) {
            throw new Exception('Box not found', 404);	// kills the script in Action
        }

        $this->parse_args($args);
        include $box;
    }

    function loop($list, $type='Object') {
        $class = ucfirst($type).'Loop';
        $loop = new $class($list);
        return $loop;
    }

    function menu($name, array $args=array()) {
        if (!is_subclass_of($name, 'ThemeMenu') && !is_subclass_of($name, 'Menu')) {
            throw new Exception('Not a menu');
        } elseif (is_subclass_of($name, 'Menu')) {
            $menu = new $name($this->action);	// getting rid of this in the future
        } else {
            $menu = new $name($args);	// new style menus
        }
        $this->widget('MenuWidget', array('action'=>$this->action, 'menu'=>$menu, 'out'=>$this->out));
    }

    function menus(array $list, array $args=array()) {
        $args['submenu'] = true;
        $this->out->elementStart('ul', 'menu');
        foreach ($list as $menu) :
            $this->out->elementStart('li', 'menu-item');
            $this->menu($menu, $args);	// no args allowed in multi-call... for now at least
            $this->out->elementEnd('li');
        endforeach;
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
