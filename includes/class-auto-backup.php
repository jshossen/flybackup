<?php
/**
 * Main Auto Backup Class
 *
 * @package Auto_Backup
 */

if (!defined('ABSPATH')) {
    exit;
}

final class Auto_Backup {
    
    private static $instance = null;
    
    public $database;
    public $backup_engine;
    public $restore_engine;
    public $scheduler;
    public $logger;
    public $retention_manager;
    public $health_check;
    public $ajax_handler;
    public $rest_api;
    public $comparison;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    private function load_dependencies() {
        require_once AUTO_BACKUP_PLUGIN_DIR . 'includes/class-database.php';
        require_once AUTO_BACKUP_PLUGIN_DIR . 'includes/class-logger.php';
        require_once AUTO_BACKUP_PLUGIN_DIR . 'includes/class-zip-manager.php';
        require_once AUTO_BACKUP_PLUGIN_DIR . 'includes/class-backup-engine.php';
        require_once AUTO_BACKUP_PLUGIN_DIR . 'includes/class-restore-engine.php';
        require_once AUTO_BACKUP_PLUGIN_DIR . 'includes/class-scheduler.php';
        require_once AUTO_BACKUP_PLUGIN_DIR . 'includes/class-retention-manager.php';
        require_once AUTO_BACKUP_PLUGIN_DIR . 'includes/class-health-check.php';
        require_once AUTO_BACKUP_PLUGIN_DIR . 'includes/class-ajax-handler.php';
        require_once AUTO_BACKUP_PLUGIN_DIR . 'includes/class-rest-api.php';
        require_once AUTO_BACKUP_PLUGIN_DIR . 'includes/class-backup-comparison.php';
        require_once AUTO_BACKUP_PLUGIN_DIR . 'includes/helpers.php';
        
        require_once AUTO_BACKUP_PLUGIN_DIR . 'admin/class-admin-menu.php';
        require_once AUTO_BACKUP_PLUGIN_DIR . 'admin/class-dashboard-widget.php';
        
        if (file_exists(AUTO_BACKUP_PLUGIN_DIR . 'pro/class-pro-manager.php')) {
            require_once AUTO_BACKUP_PLUGIN_DIR . 'pro/class-pro-manager.php';
        }
    }
    
    private function init_hooks() {
        add_action('plugins_loaded', array($this, 'init'));
        add_action('init', array($this, 'load_textdomain'));
    }
    
    public function init() {
        $this->database = new Auto_Backup_Database();
        $this->logger = new Auto_Backup_Logger();
        $this->backup_engine = new Auto_Backup_Backup_Engine();
        $this->restore_engine = new Auto_Backup_Restore_Engine();
        $this->scheduler = new Auto_Backup_Scheduler();
        $this->retention_manager = new Auto_Backup_Retention_Manager();
        $this->health_check = new Auto_Backup_Health_Check();
        $this->ajax_handler = new Auto_Backup_Ajax_Handler();
        $this->rest_api = new Auto_Backup_Rest_API();
        $this->comparison = new Auto_Backup_Comparison();
        
        if (is_admin()) {
            new Auto_Backup_Admin_Menu();
            new Auto_Backup_Dashboard_Widget();
        }
        
        if (class_exists('Auto_Backup_Pro_Manager')) {
            new Auto_Backup_Pro_Manager();
        }
    }
    
    public function load_textdomain() {
        load_plugin_textdomain('auto-backup', false, dirname(AUTO_BACKUP_PLUGIN_BASENAME) . '/languages');
    }
    
    public static function activate() {
        require_once AUTO_BACKUP_PLUGIN_DIR . 'includes/class-database.php';
        Auto_Backup_Database::create_tables();
        
        if (!file_exists(AUTO_BACKUP_BACKUP_DIR)) {
            wp_mkdir_p(AUTO_BACKUP_BACKUP_DIR);
            file_put_contents(AUTO_BACKUP_BACKUP_DIR . '.htaccess', 'deny from all');
            file_put_contents(AUTO_BACKUP_BACKUP_DIR . 'index.php', '<?php // Silence is golden');
        }
        
        add_option('auto_backup_version', AUTO_BACKUP_VERSION);
        add_option('auto_backup_retention_count', 5);
        add_option('auto_backup_settings', array(
            'email_notifications' => false,
            'notification_email' => get_option('admin_email'),
            'backup_items' => array(
                'database' => true,
                'uploads' => true,
                'plugins' => true,
                'themes' => true,
                'wp_config' => true
            )
        ));
        
        if (!wp_next_scheduled('auto_backup_cleanup_old_backups')) {
            wp_schedule_event(time(), 'daily', 'auto_backup_cleanup_old_backups');
        }
        
        flush_rewrite_rules();
    }
    
    public static function deactivate() {
        wp_clear_scheduled_hook('auto_backup_cleanup_old_backups');
        
        $schedules = get_posts(array(
            'post_type' => 'auto_backup_schedule',
            'posts_per_page' => -1
        ));
        
        foreach ($schedules as $schedule) {
            wp_clear_scheduled_hook('auto_backup_scheduled_backup_' . $schedule->ID);
        }
        
        flush_rewrite_rules();
    }
}
