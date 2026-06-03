<?php
/**
 * Retention Manager Class
 *
 * @package Fly_Backup
 */

if (!defined('ABSPATH')) {
    exit;
}

class Fly_Backup_Retention_Manager {
    
    private $database;
    private $logger;
    
    public function __construct() {
        $this->database = new Fly_Backup_Database();
        $this->logger = new Fly_Backup_Logger();
    }
    
    public function cleanup() {
        $retention_count = get_option('flybackup_retention_count', 5);
        $retention_count = apply_filters('flybackup_retention_policy', $retention_count);
        
        $backups = $this->database->get_backups(array(
            'limit' => 1000,
            'status' => 'completed',
            'orderby' => 'created_at',
            'order' => 'DESC'
        ));
        
        if (count($backups) <= $retention_count) {
            return;
        }
        
        $backups_to_delete = array_slice($backups, $retention_count);
        
        foreach ($backups_to_delete as $backup) {
            $this->delete_backup($backup);
        }
        
        $this->logger->info('Retention cleanup completed. Deleted ' . count($backups_to_delete) . ' old backups');
    }
    
    private function delete_backup($backup) {
        if (file_exists($backup->storage_location)) {
            wp_delete_file($backup->storage_location);

            if (!file_exists($backup->storage_location)) {
                $this->logger->info('Deleted backup file: ' . $backup->backup_name);
            } else {
                $this->logger->warning('Failed to delete backup file: ' . $backup->backup_name);
            }
        }

        $this->database->delete_backup($backup->id);
    }
    
    public function get_storage_usage() {
        $total_size = $this->database->get_total_backup_size();
        $backup_count = $this->database->get_backup_count('completed');
        $available_space = flybackup_get_available_disk_space();
        
        return array(
            'total_size' => $total_size,
            'total_size_formatted' => flybackup_format_bytes($total_size),
            'backup_count' => $backup_count,
            'available_space' => $available_space,
            'available_space_formatted' => flybackup_format_bytes($available_space)
        );
    }
    
    public function get_oldest_backup() {
        $backups = $this->database->get_backups(array(
            'limit' => 1,
            'orderby' => 'created_at',
            'order' => 'ASC',
            'status' => 'completed'
        ));
        
        return !empty($backups) ? $backups[0] : null;
    }
    
    public function get_newest_backup() {
        $backups = $this->database->get_backups(array(
            'limit' => 1,
            'orderby' => 'created_at',
            'order' => 'DESC',
            'status' => 'completed'
        ));
        
        return !empty($backups) ? $backups[0] : null;
    }
    
    public function delete_old_backups_by_age($days) {
        $cutoff_date = gmdate('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        global $wpdb;
        $table = $wpdb->prefix . 'fly_backup_backups';
        
        $old_backups = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE created_at < %s AND status = 'completed'",
            $cutoff_date
        ));
        
        foreach ($old_backups as $backup) {
            $this->delete_backup($backup);
        }
        
        $this->logger->info('Deleted ' . count($old_backups) . ' backups older than ' . $days . ' days');
        
        return count($old_backups);
    }
    
    public function delete_failed_backups() {
        $failed_backups = $this->database->get_backups(array(
            'limit' => 1000,
            'status' => 'failed'
        ));
        
        foreach ($failed_backups as $backup) {
            $this->delete_backup($backup);
        }
        
        $this->logger->info('Deleted ' . count($failed_backups) . ' failed backups');
        
        return count($failed_backups);
    }
}
