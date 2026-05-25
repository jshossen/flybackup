<?php
/**
 * Pro Manager Class
 *
 * @package Fly_Backup
 */

if (!defined('ABSPATH')) {
    exit;
}

class Fly_Backup_Pro_Manager {
    
    private $is_pro_active = false;
    
    public function __construct() {
        $this->check_pro_status();
        
        if ($this->is_pro_active) {
            $this->load_pro_features();
        }
        
        add_filter('fly_backup_storage_locations', array($this, 'add_cloud_storage_locations'));
        add_filter('fly_backup_backup_items', array($this, 'add_pro_backup_items'), 10, 3);
    }
    
    private function check_pro_status() {
        $this->is_pro_active = apply_filters('fly_backup_is_pro_active', false);
    }
    
    private function load_pro_features() {
        if (file_exists(FLY_BACKUP_PLUGIN_DIR . 'pro/migration/class-migration-tool.php')) {
            require_once FLY_BACKUP_PLUGIN_DIR . 'pro/migration/class-migration-tool.php';
        }
        
        if (file_exists(FLY_BACKUP_PLUGIN_DIR . 'pro/realtime/class-realtime-backup.php')) {
            require_once FLY_BACKUP_PLUGIN_DIR . 'pro/realtime/class-realtime-backup.php';
        }
        
        if (file_exists(FLY_BACKUP_PLUGIN_DIR . 'pro/licensing/class-license-manager.php')) {
            require_once FLY_BACKUP_PLUGIN_DIR . 'pro/licensing/class-license-manager.php';
        }
    }
    
    public function add_cloud_storage_locations($locations) {
        $locations['google_drive'] = __('Google Drive', 'fly-backup');
        $locations['dropbox'] = __('Dropbox', 'fly-backup');
        $locations['amazon_s3'] = __('Amazon S3', 'fly-backup');
        $locations['onedrive'] = __('OneDrive', 'fly-backup');
        
        return $locations;
    }
    
    public function add_pro_backup_items($items, $type, $selected_items) {
        if (!$this->is_pro_active) {
            return $items;
        }
        
        return apply_filters('fly_backup_pro_backup_items', $items, $type, $selected_items);
    }
    
    public function is_pro() {
        return $this->is_pro_active;
    }
}
