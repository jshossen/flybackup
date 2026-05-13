<?php
/**
 * AJAX Handler Class
 *
 * @package Auto_Backup
 */

if (!defined('ABSPATH')) {
    exit;
}

class Auto_Backup_Ajax_Handler {
    
    public function __construct() {
        add_action('wp_ajax_ab_start_backup', array($this, 'start_backup'));
        add_action('wp_ajax_ab_get_backup_progress', array($this, 'get_backup_progress'));
        add_action('wp_ajax_ab_restore_backup', array($this, 'restore_backup'));
        add_action('wp_ajax_ab_delete_backup', array($this, 'delete_backup'));
        add_action('wp_ajax_ab_get_health_status', array($this, 'get_health_status'));
        add_action('wp_ajax_ab_download_backup', array($this, 'download_backup'));
        add_action('wp_ajax_ab_clear_logs', array($this, 'clear_logs'));
    }
    
    public function start_backup() {
        check_ajax_referer('auto_backup_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'full';
        $items = isset($_POST['items']) ? array_map('sanitize_text_field', $_POST['items']) : array();
        
        $backup_engine = new Auto_Backup_Backup_Engine();
        $result = $backup_engine->create_backup($type, $items);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    public function get_backup_progress() {
        check_ajax_referer('auto_backup_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $backup_id = isset($_POST['backup_id']) ? intval($_POST['backup_id']) : 0;
        
        if (!$backup_id) {
            wp_send_json_error(array('message' => 'Invalid backup ID'));
        }
        
        $backup_engine = new Auto_Backup_Backup_Engine();
        $progress = $backup_engine->get_backup_progress($backup_id);
        
        wp_send_json_success($progress);
    }
    
    public function restore_backup() {
        check_ajax_referer('auto_backup_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $backup_id = isset($_POST['backup_id']) ? intval($_POST['backup_id']) : 0;
        $items = isset($_POST['items']) ? array_map('sanitize_text_field', $_POST['items']) : array();
        
        if (!$backup_id) {
            wp_send_json_error(array('message' => 'Invalid backup ID'));
        }
        
        $restore_engine = new Auto_Backup_Restore_Engine();
        $result = $restore_engine->restore_backup($backup_id, $items);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    public function delete_backup() {
        check_ajax_referer('auto_backup_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $backup_id = isset($_POST['backup_id']) ? intval($_POST['backup_id']) : 0;
        
        if (!$backup_id) {
            wp_send_json_error(array('message' => 'Invalid backup ID'));
        }
        
        $backup_engine = new Auto_Backup_Backup_Engine();
        $result = $backup_engine->delete_backup($backup_id);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    public function get_health_status() {
        check_ajax_referer('auto_backup_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $health_check = new Auto_Backup_Health_Check();
        $status = $health_check->run_all_checks();
        
        wp_send_json_success($status);
    }
    
    public function download_backup() {
        check_ajax_referer('auto_backup_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $backup_id = isset($_GET['backup_id']) ? intval($_GET['backup_id']) : 0;
        
        if (!$backup_id) {
            wp_die('Invalid backup ID');
        }
        
        $backup_engine = new Auto_Backup_Backup_Engine();
        $backup_engine->download_backup($backup_id);
    }
    
    public function clear_logs() {
        check_ajax_referer('auto_backup_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'ab_logs';
        $wpdb->query("TRUNCATE TABLE `{$table_name}`");
        
        wp_send_json_success(array('message' => 'All logs cleared successfully'));
    }
}
