<?php
/**
 * Health Check Class
 *
 * @package Auto_Backup
 */

if (!defined('ABSPATH')) {
    exit;
}

class Auto_Backup_Health_Check {
    
    private $checks = array();
    
    public function __construct() {
        $this->register_checks();
    }
    
    private function register_checks() {
        $this->checks = array(
            'writable' => array($this, 'check_writable'),
            'disk_space' => array($this, 'check_disk_space'),
            'memory_limit' => array($this, 'check_memory_limit'),
            'cron_status' => array($this, 'check_cron_status'),
            'zip_support' => array($this, 'check_zip_support')
        );
        
        $this->checks = apply_filters('auto_backup_health_checks', $this->checks);
    }
    
    public function run_all_checks() {
        $results = array();
        
        foreach ($this->checks as $check_name => $callback) {
            $results[$check_name] = call_user_func($callback);
        }
        
        $score = $this->calculate_health_score($results);
        
        return array(
            'checks' => $results,
            'score' => $score,
            'status' => $this->get_status_from_score($score)
        );
    }
    
    public function check_writable() {
        $backup_dir = auto_backup_get_backup_dir();
        
        if (!file_exists($backup_dir)) {
            wp_mkdir_p($backup_dir);
        }
        
        $is_writable = is_writable($backup_dir);
        
        return array(
            'status' => $is_writable ? 'ok' : 'critical',
            'message' => $is_writable ? 'Backup directory is writable' : 'Backup directory is not writable',
            'value' => $is_writable
        );
    }
    
    public function check_disk_space() {
        $available_space = auto_backup_get_available_disk_space();
        $required_space = 1024 * 1024 * 1024;
        
        $status = 'ok';
        $message = 'Sufficient disk space available';
        
        if ($available_space === false) {
            $status = 'warning';
            $message = 'Unable to determine disk space';
        } elseif ($available_space < $required_space) {
            $status = 'critical';
            $message = 'Low disk space: ' . auto_backup_format_bytes($available_space) . ' available';
        } else {
            $message = auto_backup_format_bytes($available_space) . ' available';
        }
        
        return array(
            'status' => $status,
            'message' => $message,
            'value' => $available_space
        );
    }
    
    public function check_memory_limit() {
        $memory_limit = auto_backup_get_php_memory_limit();
        $recommended_limit = 256 * 1024 * 1024;
        
        $status = 'ok';
        $message = 'Memory limit is sufficient';
        
        if ($memory_limit < $recommended_limit) {
            $status = 'warning';
            $message = 'Memory limit is low: ' . auto_backup_format_bytes($memory_limit);
        } else {
            $message = 'Memory limit: ' . auto_backup_format_bytes($memory_limit);
        }
        
        return array(
            'status' => $status,
            'message' => $message,
            'value' => $memory_limit
        );
    }
    
    public function check_cron_status() {
        $cron_disabled = defined('DISABLE_WP_CRON') && DISABLE_WP_CRON;
        
        $status = 'ok';
        $message = 'WP-Cron is enabled';
        
        if ($cron_disabled) {
            $status = 'warning';
            $message = 'WP-Cron is disabled. Scheduled backups may not work.';
        }
        
        $schedules = wp_get_schedules();
        
        if (empty($schedules)) {
            $status = 'warning';
            $message = 'No cron schedules available';
        }
        
        return array(
            'status' => $status,
            'message' => $message,
            'value' => !$cron_disabled
        );
    }
    
    public function check_zip_support() {
        $has_zip = class_exists('ZipArchive');
        
        return array(
            'status' => $has_zip ? 'ok' : 'critical',
            'message' => $has_zip ? 'ZIP support is available' : 'ZIP extension is not installed',
            'value' => $has_zip
        );
    }
    
    private function calculate_health_score($results) {
        $total_checks = count($results);
        $passed_checks = 0;
        $weight_map = array(
            'ok' => 1,
            'warning' => 0.5,
            'critical' => 0
        );
        
        foreach ($results as $result) {
            $status = $result['status'];
            $passed_checks += isset($weight_map[$status]) ? $weight_map[$status] : 0;
        }
        
        $score = ($passed_checks / $total_checks) * 100;
        
        return round($score);
    }
    
    private function get_status_from_score($score) {
        if ($score >= 80) {
            return 'healthy';
        } elseif ($score >= 50) {
            return 'warning';
        } else {
            return 'critical';
        }
    }
    
    public function get_recommendations() {
        $checks = $this->run_all_checks();
        $recommendations = array();
        
        foreach ($checks['checks'] as $check_name => $result) {
            if ($result['status'] !== 'ok') {
                $recommendations[] = array(
                    'check' => $check_name,
                    'message' => $result['message'],
                    'severity' => $result['status']
                );
            }
        }
        
        return $recommendations;
    }
    
    public function get_system_info() {
        global $wpdb;
        
        return array(
            'php_version' => PHP_VERSION,
            'wordpress_version' => get_bloginfo('version'),
            'mysql_version' => $wpdb->db_version(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'zip_support' => class_exists('ZipArchive'),
            'curl_support' => function_exists('curl_version')
        );
    }
}
