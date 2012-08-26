<?php

class ThemeSite {
    protected $action;	// Action
    protected $profile;	// Profile

    private $supported = array();
    private $template  = null;

    function __construct($action) {
        $user = common_current_user();
        $this->profile = $user ? $user->getProfile() : null;

        $this->action = $action;
if ( isset($this->action->args['tm']))        $this->supported = array('profile'=>"{$this->sysdir}/actions/profile.php",'replies'=>"{$this->sysdir}/actions/replies.php", 'public'=>"{$this->sysdir}/actions/public.php");
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
}

?>
