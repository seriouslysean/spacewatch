<?php
/*
Plugin Name: Spacewatch
Plugin URI: https://github.com/seriouslysean/spacewatch
Description: Spacewatch monitors the available space on your webserver and sends an alert you when you reach a specified threshold.
Author: Sean Kennedy
Author URI: http://devjunkyard.com
Version: 1.0.5
License: MIT
*/
if (!defined('ABSPATH')) exit;
if (!class_exists('Spacewatch')):
class Spacewatch {

    /*************************************************
     * VARIABLES
     ************************************************/ 

    const NAME = 'Spacewatch';
    const NAME_LOWER = 'spacewatch';
    const VERSION = '1.0.5';
    const SLUG = 'spacewatch';
    const CRON = 'spacewatch_cron';
    const DOWNLOAD_URL = 'http://wordpress.org/plugins/spacewatch';
    const SYSTEM_ROOT = '/';
    const DASHBOARD_CLASS = 'spacewatch';

    static $instance;

    protected $_wpVersion;
    protected $_pluginPath;
    protected $_pluginUrl;
    protected $_option;
    protected $_optionsPageHook;
    protected $_optionsPageUrl;
    protected $_totalSpace;
    protected $_totalSpaceReadable;
    protected $_freeSpace;
    protected $_freeSpacePercent;
    protected $_freeSpaceReadable;
    protected $_usedSpace;
    protected $_usedSpacePercent;
    protected $_usedSpaceReadable;
    protected $_warningLevel;



    /*************************************************
     * INITIALIZE / CONFIGURE
     ************************************************/ 

    static function load() {
        if (!self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    private function __construct() {
        $this->_init();
        $this->_cron();
        $this->_hook();
    }

    protected function _init() {
        $this->_setWpVersion();
        $this->_setPluginPath();
        $this->_setPluginUrl();
        $this->_setTotalSpace();
        $this->_setFreeSpace();
        $this->_setUsedSpace();
        $this->_setWarningLevel();
        $this->_setAllOptions();
        $this->_setOptionsPageUrl();
    }

    protected function _cron() {
        if (!wp_next_scheduled(self::CRON))
            wp_schedule_event(current_time('timestamp'), 'daily', self::CRON);
    }

    public function cron_daily() {
        $this->warning_email();
    }

    public function warning_email() {
        if ($this->getOption('email') && $this->getWarningLevel() >= $this->getOption('threshold')) {
            $from = is_email('spacewatch@'.$_SERVER['HTTP_HOST']);
            if (!$from)
                $from = $this->getOption('email');
            require_once $this->getPluginPath.'templates/email.php';
            $email = ob_get_clean();
            $headers = 'From: The Spacewatch Robot <'.$from.'>' . "\r\n";
            wp_mail(
                $this->getOption('email'),
                "Spacewatch: ".get_bloginfo('title')." Notification",
                $email,
                $headers
            );
        }
    }

    protected function _hook() {
        switch (true) {
            case ($this->getWpVersion() < 3.8):
                $this->_hook_37X();
                break;
            default:
                $this->_hook_38X();
                break;
        }
        add_action('admin_enqueue_scripts', array($this, 'styles'), 9000);
        add_action('wp_dashboard_setup', array($this, 'dashboard_standalone'), 9000);
        add_action('admin_menu', array($this, 'settings_menu'), 9000);
        add_filter('plugin_action_links_'.plugin_basename(__FILE__), array($this, 'settings_menu_link'), 9000);
        add_action('admin_init', array($this, 'settings'), 9000);
        add_action(self::CRON, array($this, 'cron_daily'), 9000);
    }

    protected function _hook_37X() {

    }

    protected function _hook_38X() {
        add_action('dashboard_glance_items', array($this, 'dashboard'), 9000);
    }

    public static function defaults() {
        if (!get_option(self::SLUG)) {
            // Options
            update_option(
                self::SLUG,
                array(
                    'email' => get_option('admin_email'),
                    'alerts' => 1,
                    'threshold' => 3,
                    'version' => self::VERSION
                )
            );
            // Hide dashboard widget
            $administrators = get_users(array('role'=>'administrator'));
            foreach ($administrators as $user) {
                $panels = get_user_option('metaboxhidden_dashboard', $user->ID);
                if (is_array($panels) && !in_array(self::SLUG, $panels)) {
                    $hidden = array_merge($panels, array(self::SLUG));
                    update_user_option($user->ID, 'metaboxhidden_dashboard', $hidden, true);
                }
            }
        }
    }

    public static function activate() {
        self::defaults();
    }

    public static function deactivate() {
        wp_clear_scheduled_hook(self::CRON);
    }

    public static function uninstall() {
        delete_option(self::SLUG);
    }

    public function styles($hook) {
        if ($hook == 'index.php')
            wp_enqueue_style(self::SLUG.'-dashboard', $this->getPluginUrl().'css/dashboard.css', array(), self::VERSION);
        if ($hook == $this->_optionsPageHook)
            wp_enqueue_style(self::SLUG.'-settings', $this->getPluginUrl().'css/settings.css', array(), self::VERSION);
    }

    public function dashboard() {
        require $this->getPluginPath.'templates/dashboard.php';
    }

    public function dashboard_standalone() {
        wp_add_dashboard_widget(
            self::SLUG,
            self::NAME,
            array($this, 'dashboard')
        );
    }



    /*************************************************
     * OPTIONS / SETTINGS
     ************************************************/

    public function settings() {
        register_setting(self::SLUG, self::SLUG, array($this, 'settings_validate'));
        add_settings_section(self::SLUG, '', '', self::SLUG);
        add_settings_field(self::SLUG.'-email', 'Email', array($this, 'settings_page_fields_email'), self::SLUG, self::SLUG);
        add_settings_field(self::SLUG.'-alerts', 'Email Alerts', array($this, 'settings_page_fields_alerts'), self::SLUG, self::SLUG);
        add_settings_field(self::SLUG.'-threshold', 'Warning Threshold', array($this, 'settings_page_fields_threshold'), self::SLUG, self::SLUG);
    }

    public function settings_menu() {
        $this->_optionsPageHook = add_management_page(
            self::NAME,
            self::NAME,
            'manage_options',
            self::SLUG,
            array($this, 'settings_page')
        );
    }

    public function settings_menu_link($links) {
        $links[] = '<a href="'.$this->getOptionsPageUrl().'">' . __('Settings') .'</a>';
        return $links;
    }

    public function settings_page() {
        require_once $this->getPluginPath.'templates/settings.php';
    }

    public function settings_page_fields_email() {
        ?>
        <input name="<?php echo self::SLUG ?>[email]" value="<?php echo $this->getOption('email') ?>" />
        <?php
    }

    public function settings_page_fields_alerts() {
        ?>
        <select name="<?php echo self::SLUG ?>[alerts]">
            <option value="0"<?php echo ($this->getOption('alerts')===0?' selected':'') ?>>Disable</option>
            <option value="1"<?php echo ($this->getOption('alerts')===1?' selected':'') ?>>Enable</option>
        </select>
        <p class="description">If enabled, you will receive warning emails from <?php echo self::NAME_LOWER ?></p>
        <?php
    }

    public function settings_page_fields_threshold() {
        ?>
        <select name="<?php echo self::SLUG ?>[threshold]">
            <option value="0"<?php echo ($this->getOption('threshold')===0?' selected':'') ?>>Receive daily notifications</option>
            <option value="1"<?php echo ($this->getOption('threshold')===1?' selected':'') ?>>41%-60% free space remaining</option>
            <option value="2"<?php echo ($this->getOption('threshold')===2?' selected':'') ?>>21%-40% free space remaining</option>
            <option value="3"<?php echo ($this->getOption('threshold')===3?' selected':'') ?>>0%-20% free space remaining</option>
        </select>
        <p class="description">You will receive a daily notification when your free space falls within the specified threshold</p>
        <?php
    }

    public function settings_validate($input) {
        if (is_array($input)) {
            foreach ($input as $field => $value) {
                switch ($field) {
                    case 'email':
                        $this->_options[$field] = is_email($value);
                        break;
                    case 'alerts':
                    case 'threshold':
                        $this->_options[$field] = (int)$value;
                        break;
                }
            }
        }
        return $this->_options;
    }



    /*************************************************
     * SETTERS
     ************************************************/ 

    private function _setWpVersion() {
        $this->_wpVersion = floatval(get_bloginfo('version'));
    }

    private function _setPluginUrl() {
        $this->_pluginUrl = plugin_dir_url(__FILE__);
    }

    private function _setPluginPath() {
        $this->_pluginPath = plugin_dir_path(__FILE__);
    }

    private function _setTotalSpace() {
        $this->_totalSpace = (int)disk_total_space(self::SYSTEM_ROOT);
        $this->_setTotalSpaceReadable();
    }

    private function _setTotalSpaceReadable() {
        $this->_totalSpaceReadable = $this->getHumanReadableSpace($this->getTotalSpace());
    }

    private function _setFreeSpace() {
        $this->_freeSpace = (int)disk_free_space(self::SYSTEM_ROOT);
        $this->_setFreeSpacePercent();
        $this->_setFreeSpaceReadable();
    }

    private function _setFreeSpacePercent() {
        $this->_freeSpacePercent = (int)((100*$this->getFreeSpace())/$this->getTotalSpace());
    }

    private function _setFreeSpaceReadable() {
        $this->_freeSpaceReadable = $this->getHumanReadableSpace($this->getFreeSpace());
    }

    private function _setUsedSpace() {
        $this->_usedSpace = (int)($this->_totalSpace-$this->_freeSpace);
        $this->_setUsedSpacePercent();
        $this->_setUsedSpaceReadable();
    }

    private function _setUsedSpacePercent() {
        $this->_usedSpacePercent = (int)((100*$this->getUsedSpace())/$this->getTotalSpace());
    }

    private function _setUsedSpaceReadable() {
        $this->_usedSpaceReadable = $this->getHumanReadableSpace($this->getUsedSpace());
    }

    private function _setWarningLevel() {
        switch (true) {
            default:
                $this->_warningLevel = 0;
                break;
            case (in_array($this->_freeSpacePercent, range(41,60))):
                $this->_warningLevel = 1;
                break;
            case (in_array($this->_freeSpacePercent, range(21,40))):
                $this->_warningLevel = 2;
                break;
            case (in_array($this->_freeSpacePercent, range(0,20))):
                $this->_warningLevel = 3;
                break;
        }
    }

    private function _setAllOptions() {
        $options = get_option(self::SLUG);
        if (!$options)
            $this->defaults();
        $this->_option = get_option(self::SLUG);
    }

    private function _setOption($key, $value) {
        $this->_option[$key] = $value;
        return update_option(self::SLUG, $this->_option);
    }

    private function _setOptionsPageUrl() {
        $this->_optionsPageUrl = 'tools.php?page='.self::SLUG;
    }


    
    /*************************************************
     * GETTERS
     ************************************************/ 

    public function getWpVersion() {
        return $this->_wpVersion;
    }

    public function getPluginUrl() {
        return $this->_pluginUrl;
    }

    public function getPluginPath() {
        return $this->_pluginPath;
    }

    public function getTotalSpace() {
        return $this->_totalSpace;
    }

    public function getTotalSpaceReadable() {
        return $this->_totalSpaceReadable;
    }

    public function getFreeSpace() {
        return $this->_freeSpace;
    }

    public function getFreeSpacePercent() {
        return $this->_freeSpacePercent;
    }

    public function getFreeSpaceReadable() {
        return $this->_freeSpaceReadable;
    }

    public function getUsedSpace() {
        return $this->_usedSpace;
    }

    public function getUsedSpacePercent() {
        return $this->_usedSpacePercent;
    }

    public function getUsedSpaceReadable() {
        return $this->_usedSpaceReadable;
    }

    public function getWarningLevel() {
        return $this->_warningLevel;
    }

    public function getHumanReadableSpace($bytes, $precision=2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB'); 

        $bytes = max($bytes, 0); 
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
        $pow = min($pow, count($units) - 1); 

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    public function getAllOptions() {
        return $this->_option;
    }

    public function getOption($key) {
        if (!isset($this->_option[$key]))
            return false;
        return $this->_option[$key];
    }

    public function getOptionsPageUrl() {
        return $this->_optionsPageUrl;
    }

}
register_activation_hook( __FILE__, array('Spacewatch', 'activate'));
register_deactivation_hook( __FILE__, array('Spacewatch', 'deactivate'));
register_uninstall_hook(__FILE__, array('Spacewatch', 'uninstall'));
add_action('plugins_loaded', array('Spacewatch', 'load'), 9000);
endif;