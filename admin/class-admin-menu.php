<?php
/**
 * Admin Menu Class
 *
 * @package Auto_Backup
 */

if (!defined('ABSPATH')) {
    exit;
}

class Auto_Backup_Admin_Menu {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'register_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
    }
    
    public function register_menu() {
        add_menu_page(
            __('Auto Backup', 'auto-backup'),
            __('Auto Backup', 'auto-backup'),
            'manage_options',
            'auto-backup',
            array($this, 'render_page'),
            'dashicons-backup',
            30
        );
        
        add_submenu_page(
            'auto-backup',
            __('Dashboard', 'auto-backup'),
            __('Dashboard', 'auto-backup'),
            'manage_options',
            'auto-backup',
            array($this, 'render_page')
        );
        
        add_submenu_page(
            'auto-backup',
            __('Backups', 'auto-backup'),
            __('Backups', 'auto-backup'),
            'manage_options',
            'auto-backup-backups',
            array($this, 'render_page')
        );
        
        add_submenu_page(
            'auto-backup',
            __('Restore', 'auto-backup'),
            __('Restore', 'auto-backup'),
            'manage_options',
            'auto-backup-restore',
            array($this, 'render_page')
        );
        
        add_submenu_page(
            'auto-backup',
            __('Backup Details', 'auto-backup'),
            null,
            'manage_options',
            'auto-backup-backup-details',
            array($this, 'render_page')
        );
        
        add_submenu_page(
            'auto-backup',
            __('Schedules', 'auto-backup'),
            __('Schedules', 'auto-backup'),
            'manage_options',
            'auto-backup-schedules',
            array($this, 'render_page')
        );
        
        add_submenu_page(
            'auto-backup',
            __('Settings', 'auto-backup'),
            __('Settings', 'auto-backup'),
            'manage_options',
            'auto-backup-settings',
            array($this, 'render_page')
        );
        
        add_submenu_page(
            'auto-backup',
            __('Compare', 'auto-backup'),
            __('Compare', 'auto-backup'),
            'manage_options',
            'auto-backup-compare',
            array($this, 'render_page')
        );
        
        add_submenu_page(
            'auto-backup',
            __('Cloud Storage', 'auto-backup'),
            __('Cloud Storage', 'auto-backup'),
            'manage_options',
            'auto-backup-cloud',
            array($this, 'render_page')
        );
        
        add_submenu_page(
            'auto-backup',
            __('Logs', 'auto-backup'),
            __('Logs', 'auto-backup'),
            'manage_options',
            'auto-backup-logs',
            array($this, 'render_page')
        );
    }
    
    public function render_page() {
        ?>
        <div class="wrap">
            <div id="auto-backup-app"></div>
        </div>
        <?php
    }
    
    public function enqueue_assets($hook) {
        if (strpos($hook, 'auto-backup') === false) {
            return;
        }
        
        wp_enqueue_style(
            'auto-backup-admin',
            AUTO_BACKUP_PLUGIN_URL . 'assets/css/admin-style.css',
            array(),
            AUTO_BACKUP_VERSION
        );
        
        wp_enqueue_script(
            'auto-backup-admin',
            AUTO_BACKUP_PLUGIN_URL . 'assets/js/admin-script.js',
            array('wp-api-fetch', 'wp-i18n'),
            AUTO_BACKUP_VERSION,
            true
        );
        
        wp_localize_script('auto-backup-admin', 'autoBackupData', array(
            'apiUrl' => rest_url('auto-backup/v1'),
            'nonce' => wp_create_nonce('auto_backup_nonce'),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'pluginUrl' => AUTO_BACKUP_PLUGIN_URL,
            'currentPage' => isset($_GET['page']) ? sanitize_text_field($_GET['page']) : 'auto-backup',
            'strings' => array(
                'confirmDelete' => __('Are you sure you want to delete this backup?', 'auto-backup'),
                'confirmRestore' => __('Are you sure you want to restore this backup? This will overwrite your current site.', 'auto-backup'),
                'backupInProgress' => __('Backup in progress...', 'auto-backup'),
                'restoreInProgress' => __('Restore in progress...', 'auto-backup')
            )
        ));
    }
}
