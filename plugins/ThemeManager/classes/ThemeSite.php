<?php

class ThemeSite {
    protected $action;	// Action
    protected $profile;	// Profile

    protected $urldir;
    protected $sysdir;

    private $supported = array();
    private $template  = null;

    function __construct($action) {
        $user = common_current_user();
        $this->profile = $user ? $user->getProfile() : null;

/* FIXME: errr, urldir and sysdir should be set here... */

        $this->action = $action;
        $this->supported = array('showprofile'=>"{$this->sysdir}/actions/profile.php");
if ( isset($this->action->args['tm']))        $this->supported = array('profile'=>"{$this->sysdir}/actions/profile.php",'replies'=>"{$this->sysdir}/actions/replies.php", 'public'=>"{$this->sysdir}/actions/public.php", 'attachment'=>"{$this->sysdir}/actions/single.php");
if ( isset($this->action->args['notm']))        $this->supported = array();
        $this->set_template($this->action);
    }

    private function set_template($action) {
        $class = get_class($action);
        do {	// get the closest match to current action and set that template
            $template = strtolower(basename(preg_replace('/^(\w+)Action$/', '\1', $class)));
            if (isset($this->supported[$template])) {
                $this->template = $template;
                $this->template_file = $this->supported[$this->template];
                break;
            }
        } while ($class = get_parent_class($class));

        if (empty($this->template) || !file_exists($this->template_file)) {
            throw new Exception('Template not supported', 302);
        }
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

    function the_siteinfo($param='') {
        echo htmlspecialchars($this->get_siteinfo($param));
    }

    function is_action($action=null) {
		if (is_null($action)) {
            return strtolower(basename(preg_replace('/^(\w+)Action$/', '\1', get_class($this->action))));
		}
        return is_a($this->action, ucfirst($action.'Action'));
    }
    function is_single() {
        return $this->is_action('Showprofile');
    }


    function the_feeds()
    {
        foreach ((array)$this->action->getFeeds() as $feed) {	// should we get these as an event?
            $this->out->element('link', array('rel' => $feed->rel(),
                                         'href' => $feed->url,
                                         'type' => $feed->mimeType(),
                                         'title' => $feed->title));
        }
    }
    function the_scripts() {
		$this->out->script($this->url('js/jquery-1.8.1.min.js'));
	}
    function the_styles() {
        $this->out->element('link', array('rel' => 'stylesheet',
                                            'type' => 'text/css',
                                            'href' => $this->url('css/main.css')));
    }
}

?>
