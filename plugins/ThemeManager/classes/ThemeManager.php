<?php

class ThemeManager {
    protected $action;
    protected $boxes;
    protected $name;
    protected $supported;
    protected $template;
    protected $reldir;
    protected $sysdir;

    protected $out;

    function __construct($action)
    {
        // get possible user config and set $theme to user's theme
        $theme_name = common_config('site', 'theme');
        if ($user = common_current_user()) {
        }

        $this->action = $action;
        $this->name = ucfirst($theme_name).'Theme';
        $this->sysdir = INSTALLDIR . "/theme/{$this->name}";
        $this->url = '/theme/' . urlencode($this->name);

        $this->supported = array();
//        $this->supported = array('remoteprofile');

        if (empty($this->action->args['action'])) {
            common_debug('THEMEMANAGER unset action: '.print_r($this->action,true));
        }
        $this->setTemplate($this->action->args['action']);

        $this->out = new HTMLOutputter;	// ...not sure if action should stay or go... sorry...
    }

    private function setTemplate($template) {
        if (!in_array($template, $this->supported)) {
            throw new Exception('Template not supported', 302);
        }
        $this->template = $this->sysdir . '/actions/' . $template . '.php';
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

        include($this->template);	// we can do stuff like $this-> inside the template!
        
        return true;
    }

    function head() {
        if (Event::handle('StartShowHeadElements', array($this->action))) {
            $this->stylesheets();
            $this->feeds();
            $this->action->extraHead();	// html head tags
            Event::handle('EndShowHeadElements', array($this->action));
        }
        $this->action->flush();	// I want to get rid of $this->action as output element!
    }

    function the_notice() {
        
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

    function title() {
        echo htmlspecialchars($this->action->title());
    }
    function lang() {
        echo htmlspecialchars(common_config('site', 'language'));
    }

    function feeds()
    {
        foreach ((array)$this->action->getFeeds() as $feed) {	// should we get these as an event?
            $this->out->element('link', array('rel' => $feed->rel(),
                                         'href' => $feed->url,
                                         'type' => $feed->mimeType(),
                                         'title' => $feed->title));
        }
        $this->out->flush();
    }

    function siteinfo($param='') {
        switch ($param) {
        case 'name':
        case 'server':
        case 'ssl':
            $info = common_config('site', $param);
            break;
        }
        return htmlspecialchars($info);
    }

    function stylesheets() {
        if (Event::handle('StartShowStyles', array($this->action))) {
            if (Event::handle('StartShowStatusNetStyles', array($this->action))) {
                $this->action->flush();	//...sigh, I haven't even bothered to look for autoflushing
                $this->action->primaryCssLink(null, 'screen, projection, tv, print');
                Event::handle('EndShowStatusNetStyles', array($this->action));
            }
            Event::handle('EndShowStyles', array($this->action));
        }
        $this->action->flush();	//...sigh, I haven't even bothered to look for autoflushing
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

    function widgets(array $list) {
        foreach($list as $widget=>$args) {
            if (is_subclass_of($widget, 'ThemeWidget')) {	// new style widgets
                $widget::run($args);
            } elseif (is_subclass_of($widget, 'Widget')) {	// old style widgets
                // oh man, this is ugly, but at least doesn't return errors. Glad to be getting rid of it.
                // call_user_func_array won't work will it?
                switch (count($args)) {
                case 0:	$widget = new $widget(); break;
                case 1:	$widget = new $widget($args[0]); break;
                case 2:	$widget = new $widget($args[0], $args[1]); break;
                case 3:	$widget = new $widget($args[0], $args[1], $args[2]); break;
                default: throw new Exception('Bad number of arguments');
                }
                $widget->show();
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
