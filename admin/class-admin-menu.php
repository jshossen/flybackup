<?php
/**
 * Admin Menu Class
 *
 * @package Fly_Backup
 */

if (!defined('ABSPATH')) {
    exit;
}

class Fly_Backup_Admin_Menu {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'register_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
    }
    
    public function register_menu() {
        add_menu_page(
            __('Fly Backup', 'fly-backup'),
            __('Fly Backup', 'fly-backup'),
            'manage_options',
            'fly-backup',
            array($this, 'render_page'),
            'dashicons-backup',
            30
        );
        
        add_submenu_page(
            'fly-backup',
            __('Dashboard', 'fly-backup'),
            __('Dashboard', 'fly-backup'),
            'manage_options',
            'fly-backup',
            array($this, 'render_page')
        );
        
        add_submenu_page(
            'fly-backup',
            __('Backups', 'fly-backup'),
            __('Backups', 'fly-backup'),
            'manage_options',
            'fly-backup-backups',
            array($this, 'render_page')
        );
        
        add_submenu_page(
            'fly-backup',
            __('Restore', 'fly-backup'),
            __('Restore', 'fly-backup'),
            'manage_options',
            'fly-backup-restore',
            array($this, 'render_page')
        );
        
        add_submenu_page(
            'fly-backup',
            __('Backup Details', 'fly-backup'),
            null,
            'manage_options',
            'fly-backup-backup-details',
            array($this, 'render_page')
        );
        
        add_submenu_page(
            'fly-backup',
            __('Schedules', 'fly-backup'),
            __('Schedules', 'fly-backup'),
            'manage_options',
            'fly-backup-schedules',
            array($this, 'render_page')
        );
        
        add_submenu_page(
            'fly-backup',
            __('Settings', 'fly-backup'),
            __('Settings', 'fly-backup'),
            'manage_options',
            'fly-backup-settings',
            array($this, 'render_page')
        );
        
        add_submenu_page(
            'fly-backup',
            __('Compare', 'fly-backup'),
            __('Compare', 'fly-backup'),
            'manage_options',
            'fly-backup-compare',
            array($this, 'render_page')
        );
        
        add_submenu_page(
            'fly-backup',
            __('Cloud Storage', 'fly-backup'),
            __('Cloud Storage', 'fly-backup'),
            'manage_options',
            'fly-backup-cloud',
            array($this, 'render_page')
        );
        
        add_submenu_page(
            'fly-backup',
            __('Logs', 'fly-backup'),
            __('Logs', 'fly-backup'),
            'manage_options',
            'fly-backup-logs',
            array($this, 'render_page')
        );
    }
    
    public function render_page() {
        ?>
        <div class="wrap">
            <div id="fly-backup-app"></div>
        </div>
        <?php
    }
    
    public function enqueue_assets($hook) {
        if (strpos($hook, 'fly-backup') === false) {
            return;
        }
        
        wp_enqueue_style(
            'fly-backup-admin',
            FLY_BACKUP_PLUGIN_URL . 'assets/css/admin-style.css',
            array(),
            FLY_BACKUP_VERSION
        );
        
        wp_enqueue_script(
            'fly-backup-admin',
            FLY_BACKUP_PLUGIN_URL . 'assets/js/admin-script.js',
            array('wp-api-fetch', 'wp-i18n'),
            FLY_BACKUP_VERSION,
            true
        );
        
        wp_localize_script('fly-backup-admin', 'autoBackupData', array(
            'apiUrl' => rest_url('fly-backup/v1'),
            'nonce' => wp_create_nonce('fly_backup_nonce'),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'pluginUrl' => FLY_BACKUP_PLUGIN_URL,
            'currentPage' => isset($_GET['page']) ? sanitize_text_field($_GET['page']) : 'fly-backup',
            'strings' => array(
                'confirmDelete' => __('Are you sure you want to delete this backup?', 'fly-backup'),
                'confirmRestore' => __('Are you sure you want to restore this backup? This will overwrite your current site.', 'fly-backup'),
                'backupInProgress' => __('Backup in progress...', 'fly-backup'),
                'restoreInProgress' => __('Restore in progress...', 'fly-backup')
            )
        ));
    }
}
