<?php
/**
 * Restore Engine Class
 *
 * @package Auto_Backup
 */

if (!defined('ABSPATH')) {
    exit;
}

class Auto_Backup_Restore_Engine {
    
    private $database;
    private $logger;
    private $zip_manager;
    
    public function __construct() {
        $this->database = new Auto_Backup_Database();
        $this->logger = new Auto_Backup_Logger();
    }
    
    public function restore_backup($backup_id, $items = array()) {
        try {
            $backup = $this->database->get_backup($backup_id);
            
            if (!$backup) {
                throw new Exception('Backup not found');
            }
            
            if (!file_exists($backup->storage_location)) {
                throw new Exception('Backup file not found');
            }
            
            $this->logger->info('Restore started', $backup_id);
            
            do_action('auto_backup_before_restore', $backup_id);
            
            $this->create_safety_backup();
            
            if (!$this->validate_backup($backup->storage_location)) {
                throw new Exception('Backup file is corrupted');
            }
            
            $this->zip_manager = new Auto_Backup_Zip_Manager();
            $this->zip_manager->open($backup->storage_location);
            
            $temp_dir = AUTO_BACKUP_BACKUP_DIR . 'temp_restore_' . time() . '/';
            wp_mkdir_p($temp_dir);
            
            $this->zip_manager->extract($temp_dir);
            $this->zip_manager->close();
            
            $included_items = json_decode($backup->included_items, true);
            
            if (empty($items)) {
                $items = $included_items;
            }
            
            if (is_array($items) && (in_array('database', $items) || $backup->backup_type === 'database')) {
                $this->logger->info('Restoring database', $backup_id);
                $this->restore_database($temp_dir . 'database.sql', $backup_id);
            }
            
            if ($backup->backup_type === 'full' || $backup->backup_type === 'partial') {
                if (empty($items) || in_array('uploads', $items)) {
                    $this->logger->info('Restoring uploads', $backup_id);
                    $this->restore_files($temp_dir . 'uploads', WP_CONTENT_DIR . '/uploads');
                }
                
                if (empty($items) || in_array('plugins', $items)) {
                    $this->logger->info('Restoring plugins', $backup_id);
                    $this->restore_files($temp_dir . 'plugins', WP_CONTENT_DIR . '/plugins');
                }
                
                if (empty($items) || in_array('themes', $items)) {
                    $this->logger->info('Restoring themes', $backup_id);
                    $this->restore_files($temp_dir . 'themes', WP_CONTENT_DIR . '/themes');
                }
                
                if (empty($items) || in_array('wp_config', $items)) {
                    $this->logger->info('Restoring wp-config', $backup_id);
                    if (file_exists($temp_dir . 'wp-config/wp-config.php')) {
                        copy($temp_dir . 'wp-config/wp-config.php', ABSPATH . 'wp-config.php');
                    }
                }
            }
            
            $this->cleanup_temp_files($temp_dir);
            
            $this->logger->success('Restore completed successfully', $backup_id);
            
            do_action('auto_backup_after_restore', $backup_id);
            
            return array(
                'success' => true,
                'message' => 'Restore completed successfully'
            );
            
        } catch (Exception $e) {
            $error_message = $e->getMessage();
            
            $this->logger->error('Restore failed: ' . $error_message, $backup_id);
            
            if (isset($temp_dir)) {
                $this->cleanup_temp_files($temp_dir);
            }
            
            return array(
                'success' => false,
                'message' => $error_message
            );
        }
    }
    
    private function validate_backup($backup_path) {
        if (!file_exists($backup_path)) {
            return false;
        }
        
        $zip = new ZipArchive();
        $result = $zip->open($backup_path, ZipArchive::CHECKCONS);
        
        if ($result === true) {
            $zip->close();
            return true;
        }
        
        return false;
    }
    
    private function restore_database($sql_file, $backup_id) {
        global $wpdb;
        
        if (!file_exists($sql_file)) {
            throw new Exception('Database backup file not found');
        }
        
        $sql_content = file_get_contents($sql_file);
        
        if (empty($sql_content)) {
            throw new Exception('Database backup file is empty');
        }
        
        $this->logger->info('Starting database restore', $backup_id);
        
        // Define plugin tables that should NOT be restored (to preserve current backups and logs)
        $plugin_tables = array(
            $wpdb->prefix . 'ab_backups',
            $wpdb->prefix . 'ab_logs',
            $wpdb->prefix . 'ab_schedules'
        );
        
        // Remove comments and split into individual statements
        $lines = explode("\n", $sql_content);
        $query = '';
        $queries_executed = 0;
        $queries_failed = 0;
        $queries_skipped = 0;
        $skip_current_table = false;
        $current_table = '';
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip empty lines and comments
            if (empty($line) || substr($line, 0, 2) === '--') {
                continue;
            }
            
            // Check if this is a DROP TABLE or CREATE TABLE statement for plugin tables
            if (preg_match('/DROP TABLE IF EXISTS `([^`]+)`/', $line, $matches)) {
                $table_name = $matches[1];
                if (in_array($table_name, $plugin_tables)) {
                    $skip_current_table = true;
                    $current_table = $table_name;
                    $this->logger->info("Skipping plugin table: {$table_name}", $backup_id);
                    continue;
                }
            }
            
            if (preg_match('/CREATE TABLE `([^`]+)`/', $line, $matches)) {
                $table_name = $matches[1];
                if (in_array($table_name, $plugin_tables)) {
                    $skip_current_table = true;
                    $current_table = $table_name;
                    continue;
                }
            }
            
            if (preg_match('/INSERT INTO `([^`]+)`/', $line, $matches)) {
                $table_name = $matches[1];
                if (in_array($table_name, $plugin_tables)) {
                    $skip_current_table = true;
                    $current_table = $table_name;
                }
            }
            
            // If we're skipping this table, don't add to query
            if ($skip_current_table) {
                // Check if query is complete (ends with semicolon)
                if (substr(trim($line), -1) === ';') {
                    $queries_skipped++;
                    $skip_current_table = false;
                    $current_table = '';
                }
                continue;
            }
            
            // Add line to current query
            $query .= $line . ' ';
            
            // Check if query is complete (ends with semicolon)
            if (substr(trim($line), -1) === ';') {
                // Remove trailing semicolon and whitespace
                $query = trim($query);
                $query = substr($query, 0, -1);
                
                if (!empty($query)) {
                    // Execute the query. SQL is from a trusted backup file generated by this plugin.
                    $result = $wpdb->query($query); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                    
                    if ($result === false) {
                        if (!empty($wpdb->last_error)) {
                            // Log the error with the query for debugging
                            $this->logger->warning(
                                'Query failed: ' . $wpdb->last_error . ' | Query: ' . substr($query, 0, 100) . '...', 
                                $backup_id
                            );
                            $queries_failed++;
                        }
                    } else {
                        $queries_executed++;
                    }
                }
                
                // Reset query for next statement
                $query = '';
            }
        }
        
        $this->logger->info(
            "Database restore completed. Executed: {$queries_executed}, Failed: {$queries_failed}, Skipped: {$queries_skipped}", 
            $backup_id
        );
        
        if ($queries_failed > 0) {
            $this->logger->warning(
                "Some queries failed during restore. Check logs for details.", 
                $backup_id
            );
        }
        
        return true;
    }
    
    private function restore_files($source_dir, $destination_dir) {
        if (!is_dir($source_dir)) {
            return false;
        }
        
        if (!file_exists($destination_dir)) {
            wp_mkdir_p($destination_dir);
        }
        
        $this->copy_directory($source_dir, $destination_dir);
        
        return true;
    }
    
    private function copy_directory($source, $destination) {
        if (!is_dir($source)) {
            return false;
        }
        
        if (!file_exists($destination)) {
            wp_mkdir_p($destination);
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $item) {
            $target_path = $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            
            if ($item->isDir()) {
                if (!file_exists($target_path)) {
                    wp_mkdir_p($target_path);
                }
            } else {
                copy($item, $target_path);
            }
        }
        
        return true;
    }
    
    private function create_safety_backup() {
        $safety_backup_name = 'safety_backup_' . gmdate('Y-m-d_H-i-s') . '.zip';
        $safety_backup_path = AUTO_BACKUP_BACKUP_DIR . $safety_backup_name;
        
        $zip = new ZipArchive();
        $zip->open($safety_backup_path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        
        global $wpdb;
        $tables = $wpdb->get_results('SHOW TABLES', ARRAY_N);
        $sql_content = '';
        
        foreach ($tables as $table) {
            $table_name = $table[0];
            $create_table = $wpdb->get_row("SHOW CREATE TABLE `{$table_name}`", ARRAY_N);
            $sql_content .= $create_table[1] . ";\n\n";
        }
        
        $zip->addFromString('safety_database.sql', $sql_content);
        $zip->close();
        
        $this->logger->info('Safety backup created: ' . $safety_backup_name);
        
        return $safety_backup_path;
    }
    
    private function cleanup_temp_files($temp_dir) {
        if (!is_dir($temp_dir)) {
            return;
        }
        
        $this->delete_directory($temp_dir);
    }
    
    private function delete_directory($dir) {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), array('.', '..'));
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            
            if (is_dir($path)) {
                $this->delete_directory($path);
            } else {
                wp_delete_file($path);
            }
        }
        
        return wp_rmdir($dir);
    }
    
    public function get_backup_contents($backup_id) {
        $backup = $this->database->get_backup($backup_id);
        
        if (!$backup || !file_exists($backup->storage_location)) {
            return array('error' => 'Backup not found');
        }
        
        $zip = new ZipArchive();
        $zip->open($backup->storage_location);
        
        $files = array();
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $files[] = array(
                'name' => $stat['name'],
                'size' => $stat['size']
            );
        }
        
        $zip->close();
        
        return array(
            'backup_id' => $backup_id,
            'backup_name' => $backup->backup_name,
            'files' => $files,
            'total_files' => count($files)
        );
    }
}
