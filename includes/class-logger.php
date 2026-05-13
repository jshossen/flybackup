<?php
/**
 * Logger Class
 *
 * @package Auto_Backup
 */

if (!defined('ABSPATH')) {
    exit;
}

class Auto_Backup_Logger {
    
    private $database;
    
    public function __construct() {
        $this->database = new Auto_Backup_Database();
    }
    
    public function log($message, $type = 'info', $backup_id = null, $metadata = null) {
        $data = array(
            'backup_id' => $backup_id,
            'log_type' => $type,
            'message' => $message,
            'metadata' => $metadata
        );
        
        return $this->database->add_log($data);
    }
    
    public function info($message, $backup_id = null, $metadata = null) {
        return $this->log($message, 'info', $backup_id, $metadata);
    }
    
    public function success($message, $backup_id = null, $metadata = null) {
        return $this->log($message, 'success', $backup_id, $metadata);
    }
    
    public function warning($message, $backup_id = null, $metadata = null) {
        return $this->log($message, 'warning', $backup_id, $metadata);
    }
    
    public function error($message, $backup_id = null, $metadata = null) {
        return $this->log($message, 'error', $backup_id, $metadata);
    }
    
    public function get_logs($args = array()) {
        return $this->database->get_logs($args);
    }
    
    public function get_backup_logs($backup_id) {
        return $this->database->get_logs(array('backup_id' => $backup_id));
    }
    
    public function cleanup_old_logs($keep_count = 1000) {
        global $wpdb;
        $table = $wpdb->prefix . 'ab_logs';
        
        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        
        if ($total > $keep_count) {
            $delete_count = $total - $keep_count;
            $wpdb->query("DELETE FROM {$table} ORDER BY created_at ASC LIMIT {$delete_count}");
        }
    }
    
    public function export_logs($format = 'json') {
        $logs = $this->get_logs(array('limit' => 10000));
        
        if ($format === 'json') {
            return json_encode($logs, JSON_PRETTY_PRINT);
        } elseif ($format === 'csv') {
            $csv = "ID,Backup ID,Type,Message,Created At\n";
            foreach ($logs as $log) {
                $csv .= sprintf(
                    "%d,%s,%s,\"%s\",%s\n",
                    $log->id,
                    $log->backup_id ?: 'N/A',
                    $log->log_type,
                    str_replace('"', '""', $log->message),
                    $log->created_at
                );
            }
            return $csv;
        }
        
        return '';
    }
}
