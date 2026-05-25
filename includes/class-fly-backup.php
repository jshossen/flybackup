<?php
/**
 * Main Fly Backup Class
 *
 * @package Fly_Backup
 */

if (!defined('ABSPATH')) {
    exit;
}

final class Fly_Backup {
    
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
        require_once FLY_BACKUP_PLUGIN_DIR . 'includes/class-database.php';
        require_once FLY_BACKUP_PLUGIN_DIR . 'includes/class-logger.php';
        require_once FLY_BACKUP_PLUGIN_DIR . 'includes/class-zip-manager.php';
        require_once FLY_BACKUP_PLUGIN_DIR . 'includes/class-backup-engine.php';
        require_once FLY_BACKUP_PLUGIN_DIR . 'includes/class-restore-engine.php';
        require_once FLY_BACKUP_PLUGIN_DIR . 'includes/class-scheduler.php';
        require_once FLY_BACKUP_PLUGIN_DIR . 'includes/class-retention-manager.php';
        require_once FLY_BACKUP_PLUGIN_DIR . 'includes/class-health-check.php';
        require_once FLY_BACKUP_PLUGIN_DIR . 'includes/class-ajax-handler.php';
        require_once FLY_BACKUP_PLUGIN_DIR . 'includes/class-rest-api.php';
        require_once FLY_BACKUP_PLUGIN_DIR . 'includes/class-backup-comparison.php';
        require_once FLY_BACKUP_PLUGIN_DIR . 'includes/helpers.php';
        
        require_once FLY_BACKUP_PLUGIN_DIR . 'admin/class-admin-menu.php';
        require_once FLY_BACKUP_PLUGIN_DIR . 'admin/class-dashboard-widget.php';
        
        // Load cloud storage classes
        if (file_exists(FLY_BACKUP_PLUGIN_DIR . 'includes/cloud/class-cloud-base.php')) {
            require_once FLY_BACKUP_PLUGIN_DIR . 'includes/cloud/class-cloud-base.php';
        }
        if (file_exists(FLY_BACKUP_PLUGIN_DIR . 'includes/cloud/class-cloud-manager.php')) {
            require_once FLY_BACKUP_PLUGIN_DIR . 'includes/cloud/class-cloud-manager.php';
        }
        if (file_exists(FLY_BACKUP_PLUGIN_DIR . 'includes/cloud/class-google-drive.php')) {
            require_once FLY_BACKUP_PLUGIN_DIR . 'includes/cloud/class-google-drive.php';
        }
        if (file_exists(FLY_BACKUP_PLUGIN_DIR . 'includes/cloud/class-dropbox.php')) {
            require_once FLY_BACKUP_PLUGIN_DIR . 'includes/cloud/class-dropbox.php';
        }
        if (file_exists(FLY_BACKUP_PLUGIN_DIR . 'includes/cloud/class-amazon-s3.php')) {
            require_once FLY_BACKUP_PLUGIN_DIR . 'includes/cloud/class-amazon-s3.php';
        }
        
        if (file_exists(FLY_BACKUP_PLUGIN_DIR . 'pro/class-pro-manager.php')) {
            require_once FLY_BACKUP_PLUGIN_DIR . 'pro/class-pro-manager.php';
        }
    }
    
    private function init_hooks() {
        add_action('plugins_loaded', array($this, 'init'));
        add_action('init', array($this, 'load_textdomain'));
    }
    
    public function init() {
        $this->database = new Fly_Backup_Database();
        $this->logger = new Fly_Backup_Logger();
        $this->backup_engine = new Fly_Backup_Backup_Engine();
        $this->restore_engine = new Fly_Backup_Restore_Engine();
        $this->scheduler = new Fly_Backup_Scheduler();
        $this->retention_manager = new Fly_Backup_Retention_Manager();
        $this->health_check = new Fly_Backup_Health_Check();
        $this->ajax_handler = new Fly_Backup_Ajax_Handler();
        $this->rest_api = new Fly_Backup_Rest_API();
        $this->comparison = new Fly_Backup_Comparison();
        
        if (is_admin()) {
            new Fly_Backup_Admin_Menu();
            new Fly_Backup_Dashboard_Widget();
        }
        
        if (class_exists('Fly_Backup_Pro_Manager')) {
            new Fly_Backup_Pro_Manager();
        }
    }
    
    public function load_textdomain() {
        load_plugin_textdomain('fly-backup', false, dirname(FLY_BACKUP_PLUGIN_BASENAME) . '/languages');
    }
    
    public static function activate() {
        require_once FLY_BACKUP_PLUGIN_DIR . 'includes/class-database.php';
        Fly_Backup_Database::create_tables();
        
        if (!file_exists(FLY_BACKUP_BACKUP_DIR)) {
            wp_mkdir_p(FLY_BACKUP_BACKUP_DIR);
            file_put_contents(FLY_BACKUP_BACKUP_DIR . '.htaccess', 'deny from all');
            file_put_contents(FLY_BACKUP_BACKUP_DIR . 'index.php', '<?php // Silence is golden');
        }
        
        add_option('fly_backup_version', FLY_BACKUP_VERSION);
        add_option('fly_backup_retention_count', 5);
        add_option('fly_backup_settings', array(
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
        
        if (!wp_next_scheduled('fly_backup_cleanup_old_backups')) {
            wp_schedule_event(time(), 'daily', 'fly_backup_cleanup_old_backups');
        }
        
        flush_rewrite_rules();
    }
    
    public static function deactivate() {
        wp_clear_scheduled_hook('fly_backup_cleanup_old_backups');
        
        $schedules = get_posts(array(
            'post_type' => 'fly_backup_schedule',
            'posts_per_page' => -1
        ));
        
        foreach ($schedules as $schedule) {
            wp_clear_scheduled_hook('fly_backup_scheduled_backup_' . $schedule->ID);
        }
        
        flush_rewrite_rules();
    }
}
