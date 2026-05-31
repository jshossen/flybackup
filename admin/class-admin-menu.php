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
            __('Fly Backup', 'flybackup'),
            __('Fly Backup', 'flybackup'),
            'manage_options',
            'flybackup',
            array($this, 'render_page'),
            'dashicons-backup',
            30
        );
        
        add_submenu_page(
            'flybackup',
            __('Dashboard', 'flybackup'),
            __('Dashboard', 'flybackup'),
            'manage_options',
            'flybackup',
            array($this, 'render_page')
        );
        
        add_submenu_page(
            'flybackup',
            __('Backups', 'flybackup'),
            __('Backups', 'flybackup'),
            'manage_options',
            'flybackup-backups',
            array($this, 'render_page')
        );
        
        add_submenu_page(
            'flybackup',
            __('Restore', 'flybackup'),
            __('Restore', 'flybackup'),
            'manage_options',
            'flybackup-restore',
            array($this, 'render_page')
        );
        
        add_submenu_page(
            'flybackup',
            __('Backup Details', 'flybackup'),
            null,
            'manage_options',
            'flybackup-backup-details',
            array($this, 'render_page')
        );
        
        add_submenu_page(
            'flybackup',
            __('Schedules', 'flybackup'),
            __('Schedules', 'flybackup'),
            'manage_options',
            'flybackup-schedules',
            array($this, 'render_page')
        );
        
        add_submenu_page(
            'flybackup',
            __('Settings', 'flybackup'),
            __('Settings', 'flybackup'),
            'manage_options',
            'flybackup-settings',
            array($this, 'render_page')
        );
        
        add_submenu_page(
            'flybackup',
            __('Compare', 'flybackup'),
            __('Compare', 'flybackup'),
            'manage_options',
            'flybackup-compare',
            array($this, 'render_page')
        );
        
        add_submenu_page(
            'flybackup',
            __('Cloud Storage', 'flybackup'),
            __('Cloud Storage', 'flybackup'),
            'manage_options',
            'flybackup-cloud',
            array($this, 'render_page')
        );
        
        add_submenu_page(
            'flybackup',
            __('Logs', 'flybackup'),
            __('Logs', 'flybackup'),
            'manage_options',
            'flybackup-logs',
            array($this, 'render_page')
        );
    }
    
    public function render_page() {
        ?>
        <div class="wrap">
            <div id="flybackup-app"></div>
        </div>
        <?php
    }
    
    public function enqueue_assets($hook) {
        if (strpos($hook, 'flybackup') === false) {
            return;
        }
        
        wp_enqueue_style(
            'flybackup-admin',
            FLYBACKUP_PLUGIN_URL . 'assets/css/admin-style.css',
            array(),
            FLYBACKUP_VERSION
        );
        
        wp_enqueue_script(
            'flybackup-admin',
            FLYBACKUP_PLUGIN_URL . 'assets/js/admin-script.js',
            array('wp-api-fetch', 'wp-i18n'),
            FLYBACKUP_VERSION,
            true
        );
        
        wp_localize_script('flybackup-admin', 'flybackupData', array(
            'apiUrl' => rest_url('flybackup/v1'),
            'nonce' => wp_create_nonce('flybackup_nonce'),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'pluginUrl' => FLYBACKUP_PLUGIN_URL,
            'currentPage' => isset($_GET['page']) ? sanitize_text_field($_GET['page']) : 'flybackup',
            'strings' => array(
                'confirmDelete' => __('Are you sure you want to delete this backup?', 'flybackup'),
                'confirmRestore' => __('Are you sure you want to restore this backup? This will overwrite your current site.', 'flybackup'),
                'backupInProgress' => __('Backup in progress...', 'flybackup'),
                'restoreInProgress' => __('Restore in progress...', 'flybackup')
            )
        ));
    }
}
