<?php
/**
 * Backup Engine Class - Performance First
 *
 * @package Auto_Backup
 */

if (!defined('ABSPATH')) {
    exit;
}

class Auto_Backup_Backup_Engine {
    
    private $database;
    private $logger;
    private $zip_manager;
    private $chunk_size = 100;
    
    public function __construct() {
        $this->database = new Auto_Backup_Database();
        $this->logger = new Auto_Backup_Logger();
    }
    
    public function create_backup($type = 'full', $items = array(), $is_scheduled = false) {
        try {
            $backup_data = array(
                'backup_name' => auto_backup_generate_backup_filename($type),
                'backup_type' => $type,
                'status' => 'in_progress',
                'included_items' => $items
            );
            
            $backup_id = $this->database->create_backup($backup_data);
            
            $this->logger->info('Backup started', $backup_id, array('type' => $type));
            
            do_action('auto_backup_before_backup', $backup_id, $type, $items);
            
            $start_time = time();
            
            $backup_path = auto_backup_get_backup_dir() . $backup_data['backup_name'];
            $this->zip_manager = new Auto_Backup_Zip_Manager();
            $this->zip_manager->create($backup_path);
            
            // Backup database if explicitly requested or if type is 'database'
            $should_backup_db = false;
            if ($type === 'database') {
                $should_backup_db = true;
            } elseif (!empty($items) && in_array('database', $items)) {
                $should_backup_db = true;
            } elseif (empty($items) && $type === 'full') {
                // Only backup database for 'full' type if no items specified
                $should_backup_db = true;
            }
            
            if ($should_backup_db) {
                $this->logger->info('Backing up database', $backup_id);
                $this->backup_database($backup_id);
            }
            
            if ($type === 'full' || $type === 'partial') {
                $backup_items = $this->get_backup_items($type, $items);
                
                foreach ($backup_items as $item_key => $item_path) {
                    $this->logger->info("Backing up {$item_key}", $backup_id);
                    $this->backup_files($backup_id, $item_path, $item_key);
                }
            }
            
            $this->zip_manager->close();
            
            $duration = time() - $start_time;
            $backup_size = filesize($backup_path);
            
            $this->database->update_backup($backup_id, array(
                'status' => 'completed',
                'duration' => $duration,
                'backup_size' => $backup_size,
                'storage_location' => $backup_path
            ));
            
            $this->logger->success('Backup completed successfully', $backup_id, array(
                'duration' => $duration,
                'size' => $backup_size
            ));
            
            do_action('auto_backup_after_backup', $backup_id, $backup_path);
            
            // Upload to cloud if configured
            $this->upload_to_cloud($backup_id, $backup_path);
            
            $this->cleanup_old_backups();
            
            $this->send_notification($backup_id, 'success');
            
            return array(
                'success' => true,
                'backup_id' => $backup_id,
                'message' => 'Backup completed successfully'
            );
            
        } catch (Exception $e) {
            $error_message = $e->getMessage();
            
            if (isset($backup_id)) {
                $this->database->update_backup($backup_id, array(
                    'status' => 'failed',
                    'error_message' => $error_message
                ));
                
                $this->logger->error('Backup failed: ' . $error_message, $backup_id);
            }
            
            $this->send_notification($backup_id ?? null, 'failure', $error_message);
            
            return array(
                'success' => false,
                'message' => $error_message
            );
        }
    }
    
    private function backup_database($backup_id) {
        global $wpdb;
        
        $sql_file = 'database.sql';
        $sql_content = '';
        
        // Add header with important SQL settings
        $sql_content .= "-- WordPress Database Backup\n";
        $sql_content .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $sql_content .= "-- MySQL Version: " . $wpdb->db_version() . "\n\n";
        $sql_content .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
        $sql_content .= "SET FOREIGN_KEY_CHECKS = 0;\n";
        $sql_content .= "SET time_zone = \"+00:00\";\n\n";
        
        $tables = $wpdb->get_results('SHOW TABLES', ARRAY_N);
        
        foreach ($tables as $table) {
            $table_name = $table[0];
            
            // Get CREATE TABLE statement
            $create_table = $wpdb->get_row("SHOW CREATE TABLE `{$table_name}`", ARRAY_N);
            
            $sql_content .= "\n-- --------------------------------------------------------\n";
            $sql_content .= "-- Table structure for table `{$table_name}`\n";
            $sql_content .= "-- --------------------------------------------------------\n\n";
            $sql_content .= "DROP TABLE IF EXISTS `{$table_name}`;\n";
            $sql_content .= $create_table[1] . ";\n\n";
            
            // Get table data
            $rows = $wpdb->get_results("SELECT * FROM `{$table_name}`", ARRAY_A);
            
            if (!empty($rows)) {
                $sql_content .= "-- Dumping data for table `{$table_name}`\n\n";
                
                // Get column names for explicit INSERT
                $columns = array_keys($rows[0]);
                $column_list = '`' . implode('`, `', $columns) . '`';
                
                // Insert in batches for better performance
                $batch_size = 100;
                $batch_count = 0;
                $insert_values = array();
                
                foreach ($rows as $row) {
                    $values = array();
                    foreach ($row as $value) {
                        if (is_null($value)) {
                            $values[] = 'NULL';
                        } else {
                            $values[] = "'" . $wpdb->_real_escape($value) . "'";
                        }
                    }
                    
                    $insert_values[] = '(' . implode(', ', $values) . ')';
                    $batch_count++;
                    
                    // Write batch when size is reached or last row
                    if ($batch_count >= $batch_size || $row === end($rows)) {
                        $sql_content .= "INSERT INTO `{$table_name}` ({$column_list}) VALUES\n";
                        $sql_content .= implode(",\n", $insert_values) . ";\n";
                        $insert_values = array();
                        $batch_count = 0;
                    }
                }
                
                $sql_content .= "\n";
            }
        }
        
        // Re-enable foreign key checks
        $sql_content .= "\nSET FOREIGN_KEY_CHECKS = 1;\n";
        
        $this->zip_manager->add_from_string($sql_file, $sql_content);
        
        $this->logger->info('Database backup completed', $backup_id);
        
        return true;
    }
    
    private function backup_files($backup_id, $source_path, $local_dir) {
        if (!is_dir($source_path)) {
            if (file_exists($source_path)) {
                $this->zip_manager->add_file($source_path, $local_dir . '/' . basename($source_path));
            }
            return;
        }
        
        $exclude_patterns = auto_backup_get_excluded_paths();
        
        $result = $this->zip_manager->add_directory_chunked(
            $source_path,
            $local_dir,
            0,
            $this->chunk_size,
            $exclude_patterns
        );
        
        $offset = $result['next_offset'];
        
        while (!$result['completed']) {
            $result = $this->zip_manager->add_directory_chunked(
                $source_path,
                $local_dir,
                $offset,
                $this->chunk_size,
                $exclude_patterns
            );
            
            $offset = $result['next_offset'];
            
            $progress = ($offset / $result['total']) * 100;
            $this->logger->info("Progress: {$local_dir} - " . round($progress) . "%", $backup_id);
            
            if (function_exists('set_time_limit')) {
                @set_time_limit(30);
            }
        }
        
        return true;
    }
    
    private function get_backup_items($type, $items) {
        $backup_items = array();
        
        // If items array is provided, use it regardless of type
        if (!empty($items)) {
            if (in_array('uploads', $items)) {
                $backup_items['uploads'] = WP_CONTENT_DIR . '/uploads';
            }
            if (in_array('plugins', $items)) {
                $backup_items['plugins'] = WP_CONTENT_DIR . '/plugins';
            }
            if (in_array('themes', $items)) {
                $backup_items['themes'] = WP_CONTENT_DIR . '/themes';
            }
            if (in_array('wp-config', $items)) {
                $backup_items['wp-config'] = ABSPATH . 'wp-config.php';
            }
        } elseif ($type === 'full') {
            // Only use default full backup if items array is empty
            $backup_items = array(
                'uploads' => WP_CONTENT_DIR . '/uploads',
                'plugins' => WP_CONTENT_DIR . '/plugins',
                'themes' => WP_CONTENT_DIR . '/themes',
                'wp-config' => ABSPATH . 'wp-config.php'
            );
        }
        
        return apply_filters('auto_backup_backup_items', $backup_items, $type, $items);
    }
    
    public function get_backup_progress($backup_id) {
        $backup = $this->database->get_backup($backup_id);
        
        if (!$backup) {
            return array('error' => 'Backup not found');
        }
        
        $logs = $this->database->get_logs(array('backup_id' => $backup_id, 'limit' => 10));
        
        $progress = 0;
        if ($backup->status === 'completed') {
            $progress = 100;
        } elseif ($backup->status === 'in_progress') {
            $progress = 50;
        }
        
        return array(
            'backup_id' => $backup_id,
            'status' => $backup->status,
            'progress' => $progress,
            'logs' => $logs
        );
    }
    
    public function calculate_backup_size($items) {
        $total_size = 0;
        
        if (in_array('database', $items)) {
            $total_size += auto_backup_get_database_size();
        }
        
        if (in_array('uploads', $items)) {
            $total_size += auto_backup_get_directory_size(WP_CONTENT_DIR . '/uploads');
        }
        
        if (in_array('plugins', $items)) {
            $total_size += auto_backup_get_directory_size(WP_CONTENT_DIR . '/plugins');
        }
        
        if (in_array('themes', $items)) {
            $total_size += auto_backup_get_directory_size(WP_CONTENT_DIR . '/themes');
        }
        
        if (in_array('wp_config', $items)) {
            $wp_config = ABSPATH . 'wp-config.php';
            if (file_exists($wp_config)) {
                $total_size += filesize($wp_config);
            }
        }
        
        return $total_size;
    }
    
    private function cleanup_old_backups() {
        $retention_count = get_option('auto_backup_retention_count', 5);
        $backups = $this->database->get_backups(array(
            'limit' => 1000,
            'status' => 'completed'
        ));
        
        if (count($backups) > $retention_count) {
            $backups_to_delete = array_slice($backups, $retention_count);
            
            foreach ($backups_to_delete as $backup) {
                if (file_exists($backup->storage_location)) {
                    @unlink($backup->storage_location);
                }
                
                $this->database->delete_backup($backup->id);
                $this->logger->info('Old backup deleted: ' . $backup->backup_name);
            }
        }
    }
    
    private function send_notification($backup_id, $status, $error_message = '') {
        $settings = auto_backup_get_settings();
        
        if (!$settings['email_notifications']) {
            return;
        }
        
        if ($status === 'success') {
            $backup = $this->database->get_backup($backup_id);
            $subject = 'Backup Completed Successfully';
            $message = sprintf(
                "Your WordPress backup has been completed successfully.\n\nBackup Name: %s\nSize: %s\nDuration: %d seconds\nDate: %s",
                $backup->backup_name,
                auto_backup_format_bytes($backup->backup_size),
                $backup->duration,
                $backup->created_at
            );
        } else {
            $subject = 'Backup Failed';
            $message = sprintf(
                "Your WordPress backup has failed.\n\nError: %s\nDate: %s",
                $error_message,
                date('Y-m-d H:i:s')
            );
        }
        
        auto_backup_send_notification($subject, $message);
    }
    
    public function delete_backup($backup_id) {
        $backup = $this->database->get_backup($backup_id);
        
        if (!$backup) {
            return array('success' => false, 'message' => 'Backup not found');
        }
        
        if (file_exists($backup->storage_location)) {
            @unlink($backup->storage_location);
        }
        
        $this->database->delete_backup($backup_id);
        $this->logger->info('Backup deleted: ' . $backup->backup_name);
        
        return array('success' => true, 'message' => 'Backup deleted successfully');
    }
    
    public function download_backup($backup_id) {
        $backup = $this->database->get_backup($backup_id);
        
        if (!$backup || !file_exists($backup->storage_location)) {
            return false;
        }
        
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $backup->backup_name . '"');
        header('Content-Length: ' . filesize($backup->storage_location));
        
        readfile($backup->storage_location);
        exit;
    }
    
    private function upload_to_cloud($backup_id, $backup_path) {
        if (!class_exists('Auto_Backup_Cloud_Manager')) {
            return;
        }
        
        $cloud_manager = new Auto_Backup_Cloud_Manager();
        $providers = $cloud_manager->get_all_providers_status();
        
        // Find first connected provider
        $connected_provider = null;
        foreach ($providers as $provider => $status) {
            if ($status['connected']) {
                $connected_provider = $provider;
                break;
            }
        }
        
        if (!$connected_provider) {
            return;
        }
        
        $this->logger->info('Uploading backup to cloud: ' . $connected_provider, $backup_id);
        
        $result = $cloud_manager->upload_backup($connected_provider, $backup_path);
        
        if ($result['success']) {
            // Update backup record with cloud storage info
            $cloud_storage = array(
                'provider' => $connected_provider,
                'remote_path' => $result['remote_id'] ?? basename($backup_path),
                'uploaded_at' => current_time('mysql')
            );
            
            $this->database->update_backup($backup_id, array(
                'cloud_storage' => json_encode($cloud_storage)
            ));
            
            $this->logger->success('Backup uploaded to cloud: ' . $connected_provider, $backup_id);
            
            // Delete local file after successful cloud upload (cloud-first strategy)
            if (file_exists($backup_path)) {
                @unlink($backup_path);
                $this->logger->info('Local backup file deleted after cloud upload', $backup_id);
            }
        } else {
            $this->logger->warning('Cloud upload failed: ' . $result['message'], $backup_id);
        }
    }
}
