<?php

class ThemeSite {
    protected $action;    // Action
    protected $profile;    // Profile

    protected $urldir;
    protected $sysdir;

    static private $supported = array();
    private $template  = null;

    function __construct($action) {
        $user = common_current_user();
        $this->profile = $user ? $user->getProfile() : null;

/* FIXME: errr, urldir and sysdir should be set here... */

        $this->action = $action;

        $supported = array();
        if (Event::handle('GetTmSupported', array(&$supported))) {
            $this->add_supported($supported);
        }

if ( isset($this->action->args['tm']))        self::$supported['legacy'] = "{$this->sysdir}/actions/legacy.php";
if ( isset($this->action->args['notm']))        self::$supported = array();
        $this->set_template($this->action);
    }
    
    private function add_supported(array $supported) {
        self::$supported = array_merge(self::$supported, $supported);
    }

    private function set_template($action) {
        $class = get_class($action);
        do {    // get the closest match to current action and set that template
            $template = strtolower(basename(preg_replace('/^(\w+)Action$/', '\1', $class)));
            if (isset(self::$supported[$template])) {
                $this->template = $template;
                $this->template_file = self::$supported[$this->template];
                break;
            } elseif (isset(self::$supported['legacy']) &&
                        file_exists("{$this->sysdir}/content/{$template}.php")) {
                $this->template = $template;
                $this->template_file = "{$this->sysdir}/actions/legacy.php";
            }
        } while ($class = get_parent_class($class));

        if (empty($this->template) && isset(self::$supported['legacy'])) {
            $this->template = 'legacy';
            $this->template_file = self::$supported[$this->template];
        }
        if (!empty($this->template) && !preg_match('/^\//', $this->template_file)) {
            $this->template_file = "{$this->sysdir}/actions/{$this->template_file}.php";
        }
        if (empty($this->template) || !file_exists($this->template_file)) {
            define('THEME_MANAGER', false);
            throw new Exception('Template not supported', 302);
        }

        define('THEME_MANAGER', true);
    }

    function url($relpath) {
        return common_path($this->urldir . '/' . $relpath);
    }

    function get_template() {
        return $this->template;
    }
    function get_template_file() {
        return $this->template_file;
    }

    function get_siteinfo($param='') {
        switch ($param) {
        case 'name':
        case 'server':
        case 'ssl':
            $info = common_config('site', $param);
            break;
        case 'url':
            $info = common_local_url('public');
            break;
        }
        return $info;
    }

    function is_action($action=null) {
        if (!is_null($action)) {
            return is_a($this->action, ucfirst($action.'Action'));
        }

        $next = get_class($this->action);
        $class = strtolower(basename(preg_replace('/^(\w+)Action$/', '\1', $next))).'-action';
        while(!empty($next) && 'Action' != ($next = get_parent_class($next))) :
            // get top-type action
            $class .= ' '.strtolower(basename(preg_replace('/^(\w+)Action$/', '\1', $next))) . '-action';
        endwhile;
        return $class;
    }
    function is_single() {
        return (isset($this->action->user) || isset($this->action->subject))
            || $this->is_action('showstream');
    }


    function the_feeds()
    {
        foreach ((array)$this->action->getFeeds() as $feed) {    // should we get these as an event?
            $this->out->element('link', array('rel' => $feed->rel(),
                                         'href' => $feed->url,
                                         'type' => $feed->mimeType(),
                                         'title' => $feed->title));
        }
    }
    function the_scripts() {
        $this->out->script($this->url('js/jquery-1.8.1.min.js'));
        $this->out->script($this->url('js/fancybox/jquery.fancybox.pack.js'));
        $this->out->script($this->url('js/init.js'));
        $this->out->script($this->url('js/interaction-basics.js'));
    }
    function the_styles() {
        $this->out->element('link', array('rel' => 'stylesheet',
                                            'type' => 'text/css',
                                            'href' => $this->url('css/main.css')));
        $this->out->element('link', array('rel' => 'stylesheet',
                                            'type' => 'text/css',
                                            'href' => $this->url('js/fancybox/jquery.fancybox.css')));
    }
}

?>
