<?php
/**
 * Restore Engine Class
 *
 * @package Fly_Backup
 */

if (!defined('ABSPATH')) {
    exit;
}

class Fly_Backup_Restore_Engine {

    private $database;
    private $logger;
    private $zip_manager;
    private $snapshot_dir = '';
    private $snapshot_db_file = '';
    private $restored_steps = array();
    private $temp_dir = '';

    public function __construct() {
        $this->database = new Fly_Backup_Database();
        $this->logger   = new Fly_Backup_Logger();
    }

    /**
     * Restore a backup with full snapshot, validation, streaming, and rollback.
     *
     * @param int   $backup_id         Backup ID.
     * @param array $items             Items to restore.
     * @param bool  $confirm_wp_config Whether the user confirmed wp-config restore.
     * @return array
     */
    public function restore_backup($backup_id, $items = array(), $confirm_wp_config = false) {
        $this->snapshot_dir    = '';
        $this->snapshot_db_file = '';
        $this->restored_steps  = array();
        $this->temp_dir        = '';

        try {
            $backup = $this->database->get_backup($backup_id);

            if (!$backup) {
                throw new Exception(esc_html__('Backup not found', 'flybackup'));
            }

            if (!file_exists($backup->storage_location)) {
                throw new Exception(esc_html__('Backup file not found', 'flybackup'));
            }

            $this->logger->info('Restore started', $backup_id);
            do_action('flybackup_before_restore', $backup_id);

            // 1. Validate backup archive integrity.
            if (!$this->validate_backup($backup->storage_location)) {
                throw new Exception(esc_html__('Backup file is corrupted', 'flybackup'));
            }

            // 2. Extract ZIP to temp directory.
            $this->zip_manager = new Fly_Backup_Zip_Manager();
            $this->zip_manager->open($backup->storage_location);
            $this->temp_dir = FLYBACKUP_BACKUP_DIR . 'temp_restore_' . gmdate('Y-m-d_H-i-s') . '_' . wp_rand(1000, 9999) . '/';
            wp_mkdir_p($this->temp_dir);
            $this->zip_manager->extract($this->temp_dir);
            $this->zip_manager->close();

            $included_items = json_decode($backup->included_items, true);
            if (empty($items)) {
                $items = $included_items;
            }

            // 3. Validate backup contents before any destructive action.
            $validation = $this->validate_backup_contents($this->temp_dir, $items, $backup->backup_type, $confirm_wp_config);
            if (is_wp_error($validation)) {
                throw new Exception($validation->get_error_message());
            }

            // 4. Create component-level snapshot.
            $snapshot_result = $this->create_snapshot($items, $backup->backup_type);
            if (is_wp_error($snapshot_result)) {
                throw new Exception($snapshot_result->get_error_message());
            }

            // 5. Restore database.
            if ($this->should_restore_database($items, $backup->backup_type)) {
                $this->logger->info('Restoring database', $backup_id);
                $this->restored_steps[] = 'database';
                $this->restore_database_streaming($this->temp_dir . 'database.sql', $backup_id);
            }

            // 6. Restore files.
            if ($backup->backup_type === 'full' || $backup->backup_type === 'partial') {
                $upload_dir = wp_upload_dir();
                $this->restore_file_component('uploads', $items, $this->temp_dir . 'uploads', $upload_dir['basedir'], $backup_id);
                $this->restore_file_component('plugins', $items, $this->temp_dir . 'plugins', WP_PLUGIN_DIR, $backup_id);
                $this->restore_file_component('themes', $items, $this->temp_dir . 'themes', get_theme_root(), $backup_id);
                $this->restore_file_component('wp_config', $items, $this->temp_dir . 'wp-config/wp-config.php', ABSPATH . 'wp-config.php', $backup_id, true);
            }

            // 7. Cleanup.
            $this->cleanup_snapshot();
            $this->cleanup_temp_files($this->temp_dir);

            $this->logger->success('Restore completed successfully', $backup_id);
            do_action('flybackup_after_restore', $backup_id);

            return array(
                'success' => true,
                'message' => esc_html__('Restore completed successfully', 'flybackup'),
            );

        } catch (Exception $e) {
            $error_message = $e->getMessage();
            $this->logger->error('Restore failed: ' . $error_message, $backup_id);

            // Rollback any steps that were completed.
            $this->rollback_restore($backup_id);

            // Cleanup temp files.
            if (!empty($this->temp_dir)) {
                $this->cleanup_temp_files($this->temp_dir);
            }

            return array(
                'success' => false,
                'message' => $error_message,
            );
        }
    }

    /**
     * Check if database should be restored.
     */
    private function should_restore_database($items, $backup_type) {
        return is_array($items) && (in_array('database', $items, true) || 'database' === $backup_type);
    }

    /**
     * Restore a single file component with snapshot tracking.
     */
    private function restore_file_component($component, $items, $source, $destination, $backup_id, $is_file = false) {
        if (!is_array($items) || (!empty($items) && !in_array($component, $items, true))) {
            return;
        }

        if ('wp_config' === $component && (!file_exists($source) || !is_file($source))) {
            $this->logger->warning('wp-config.php not found in backup, skipping', $backup_id);
            return;
        }

        if (!$is_file && !is_dir($source)) {
            $this->logger->warning(ucfirst($component) . ' directory not found in backup, skipping', $backup_id);
            return;
        }

        $this->logger->info('Restoring ' . $component, $backup_id);
        $this->restored_steps[] = $component;

        if ($is_file) {
            if (!file_exists($source)) {
                throw new Exception(esc_html__('Backup wp-config.php file not found', 'flybackup'));
            }
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_copy -- Internal restore operation.
            $copied = copy($source, $destination);
            if (!$copied) {
                throw new Exception(esc_html__('Failed to restore wp-config.php', 'flybackup'));
            }
        } else {
            $result = $this->restore_files($source, $destination);
            if (!$result) {
                throw new Exception(
                    sprintf(
                        /* translators: %s: Component name */
                        esc_html__('Failed to restore %s', 'flybackup'),
                        esc_html($component)
                    )
                );
            }
        }
    }

    /**
     * Validate ZIP archive integrity.
     */
    private function validate_backup($backup_path) {
        if (!file_exists($backup_path)) {
            return false;
        }

        $zip = new ZipArchive();
        $result = $zip->open($backup_path, ZipArchive::CHECKCONS);

        if (true === $result) {
            $zip->close();
            return true;
        }

        return false;
    }

    /**
     * Validate that expected files exist inside the extracted backup.
     */
    private function validate_backup_contents($temp_dir, $items, $backup_type, $confirm_wp_config) {
        if ($this->should_restore_database($items, $backup_type)) {
            $db_file = $temp_dir . 'database.sql';
            if (!file_exists($db_file)) {
                return new WP_Error('missing_db', esc_html__('Database backup file not found in archive', 'flybackup'));
            }
            if (!is_readable($db_file)) {
                return new WP_Error('unreadable_db', esc_html__('Database backup file is not readable', 'flybackup'));
            }
            if (0 === filesize($db_file)) {
                return new WP_Error('empty_db', esc_html__('Database backup file is empty', 'flybackup'));
            }
        }

        if ('full' !== $backup_type && 'partial' !== $backup_type) {
            return true;
        }

        $expected_dirs = array(
            'uploads'   => $temp_dir . 'uploads',
            'plugins'   => $temp_dir . 'plugins',
            'themes'    => $temp_dir . 'themes',
            'wp_config' => $temp_dir . 'wp-config/wp-config.php',
        );

        foreach ($expected_dirs as $component => $path) {
            if (!is_array($items) || (!empty($items) && !in_array($component, $items, true))) {
                continue;
            }

            if ('wp_config' === $component) {
                if (!file_exists($path)) {
                    return new WP_Error('missing_wp_config', esc_html__('wp-config.php not found in backup archive', 'flybackup'));
                }

                // Protect wp-config.php: compare DB credentials.
                if (!$this->wp_config_credentials_match($path)) {
                    if (!$confirm_wp_config) {
                        return new WP_Error(
                            'wp_config_mismatch',
                            esc_html__('The backed-up wp-config.php contains different database credentials. Restoring it could break your site. Please confirm if you want to proceed.', 'flybackup')
                        );
                    }
                }
            } else {
                if (!is_dir($path)) {
                    return new WP_Error(
                        'missing_component',
                        sprintf(
                            /* translators: %s: Component name */
                            esc_html__('%s directory not found in backup archive', 'flybackup'),
                            esc_html(ucfirst($component))
                        )
                    );
                }
            }
        }

        return true;
    }

    /**
     * Compare DB credentials between current wp-config.php and a backed-up wp-config.php.
     */
    private function wp_config_credentials_match($backup_wp_config_path) {
        $current_constants = $this->extract_wp_config_constants(ABSPATH . 'wp-config.php');
        $backup_constants  = $this->extract_wp_config_constants($backup_wp_config_path);

        $keys = array('DB_NAME', 'DB_USER', 'DB_PASSWORD', 'DB_HOST');

        foreach ($keys as $key) {
            $current = isset($current_constants[$key]) ? $current_constants[$key] : '';
            $backup  = isset($backup_constants[$key]) ? $backup_constants[$key] : '';
            if ($current !== $backup) {
                return false;
            }
        }

        return true;
    }

    /**
     * Extract DB-related constants from a wp-config.php file.
     */
    private function extract_wp_config_constants($file_path) {
        $constants = array();
        if (!file_exists($file_path) || !is_readable($file_path)) {
            return $constants;
        }

        $content = file_get_contents($file_path); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Internal parsing of local file.
        if (false === $content) {
            return $constants;
        }

        $keys = array('DB_NAME', 'DB_USER', 'DB_PASSWORD', 'DB_HOST');
        foreach ($keys as $key) {
            if (preg_match("/define\(\s*['\"]" . preg_quote($key, '/') . "['\"]\s*,\s*['\"](.*?)['\"]\s*\)/", $content, $matches)) {
                $constants[$key] = $matches[1];
            }
        }

        return $constants;
    }

    /**
     * Create a component-level snapshot of DB and/or files before restoring.
     */
    private function create_snapshot($items, $backup_type) {
        $this->snapshot_dir = FLYBACKUP_BACKUP_DIR . 'restore_snapshot_' . gmdate('Y-m-d_H-i-s') . '_' . wp_rand(1000, 9999) . '/';
        wp_mkdir_p($this->snapshot_dir);

        // Snapshot database.
        if ($this->should_restore_database($items, $backup_type)) {
            $this->snapshot_db_file = $this->snapshot_dir . 'snapshot_database.sql';
            $result = $this->snapshot_database($this->snapshot_db_file);
            if (is_wp_error($result)) {
                return $result;
            }
        }

        // Snapshot files.
        if ('full' === $backup_type || 'partial' === $backup_type) {
            $components = array('uploads', 'plugins', 'themes', 'wp_config');
            foreach ($components as $component) {
                if (!is_array($items) || (!empty($items) && !in_array($component, $items, true))) {
                    continue;
                }

                switch ($component) {
                    case 'uploads':
                        $upload_dir = wp_upload_dir();
                        $this->snapshot_files($upload_dir['basedir'], $this->snapshot_dir . 'uploads');
                        break;
                    case 'plugins':
                        $this->snapshot_files(WP_PLUGIN_DIR, $this->snapshot_dir . 'plugins');
                        break;
                    case 'themes':
                        $this->snapshot_files(get_theme_root(), $this->snapshot_dir . 'themes');
                        break;
                    case 'wp_config':
                        if (file_exists(ABSPATH . 'wp-config.php')) {
                            wp_mkdir_p($this->snapshot_dir . 'wp-config');
                            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_copy -- Internal snapshot operation.
                            copy(ABSPATH . 'wp-config.php', $this->snapshot_dir . 'wp-config/wp-config.php');
                        }
                        break;
                }
            }
        }

        $this->logger->info('Snapshot created: ' . $this->snapshot_dir);
        return true;
    }

    /**
     * Snapshot the full database (schema + data) to a SQL file.
     */
    private function snapshot_database($output_file) {
        global $wpdb;

        $handle = fopen($output_file, 'w'); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Internal snapshot file write.
        if (false === $handle) {
            return new WP_Error('snapshot_failed', esc_html__('Failed to create database snapshot file', 'flybackup'));
        }

        $header  = "-- WordPress Database Snapshot\n";
        $header .= "-- Generated: " . gmdate('Y-m-d H:i:s') . "\n";
        $header .= "-- MySQL Version: " . $wpdb->db_version() . "\n\n";
        $header .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
        $header .= "SET FOREIGN_KEY_CHECKS = 0;\n";
        $header .= "SET time_zone = \"+00:00\";\n\n";
        fwrite($handle, $header); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Internal snapshot file write.

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $tables = $wpdb->get_results('SHOW TABLES', ARRAY_N);

        foreach ($tables as $table) {
            $table_name = $table[0];

            // Schema.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
            $create_table = $wpdb->get_row("SHOW CREATE TABLE `{$table_name}`", ARRAY_N);
            if ($create_table) {
                fwrite($handle, "\n-- --------------------------------------------------------\n"); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Internal snapshot file write.
                fwrite($handle, "-- Table structure for table `{$table_name}`\n"); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Internal snapshot file write.
                fwrite($handle, "-- --------------------------------------------------------\n\n"); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Internal snapshot file write.
                fwrite($handle, "DROP TABLE IF EXISTS `{$table_name}`;\n"); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Internal snapshot file write.
                fwrite($handle, $create_table[1] . ";\n\n"); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Internal snapshot file write.
            }

            // Data.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
            $rows = $wpdb->get_results("SELECT * FROM `{$table_name}`", ARRAY_A);
            if (!empty($rows)) {
                fwrite($handle, "-- Dumping data for table `{$table_name}`\n\n"); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Internal snapshot file write.
                $columns = array_keys($rows[0]);
                $column_list = '`' . implode('`, `', $columns) . '`';

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

                    if ($batch_count >= $batch_size) {
                        fwrite($handle, "INSERT INTO `{$table_name}` ({$column_list}) VALUES " . implode(', ', $insert_values) . ";\n"); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Internal snapshot file write.
                        $batch_count = 0;
                        $insert_values = array();
                    }
                }

                if ($batch_count > 0) {
                    fwrite($handle, "INSERT INTO `{$table_name}` ({$column_list}) VALUES " . implode(', ', $insert_values) . ";\n"); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Internal snapshot file write.
                }
                fwrite($handle, "\n"); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Internal snapshot file write.
            }
        }

        fclose($handle); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Internal snapshot file write.
        return true;
    }

    /**
     * Snapshot a directory tree.
     */
    private function snapshot_files($source, $destination) {
        if (!is_dir($source)) {
            return;
        }

        wp_mkdir_p($destination);

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $target_path = $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName();

            if ($item->isDir()) {
                wp_mkdir_p($target_path);
            } else {
                // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_copy -- Internal snapshot operation.
                copy($item, $target_path);
            }
        }
    }

    /**
     * Rollback any completed restore steps using the snapshot.
     */
    private function rollback_restore($backup_id) {
        if (empty($this->restored_steps) || empty($this->snapshot_dir)) {
            return;
        }

        $this->logger->warning('Rolling back restore steps: ' . implode(', ', $this->restored_steps), $backup_id);

        // Rollback files first (in reverse order).
        $file_steps = array_reverse($this->restored_steps);
        foreach ($file_steps as $step) {
            switch ($step) {
                case 'database':
                    $this->rollback_database();
                    break;
                case 'uploads':
                    $upload_dir = wp_upload_dir();
                    $this->rollback_files($this->snapshot_dir . 'uploads', $upload_dir['basedir']);
                    break;
                case 'plugins':
                    $this->rollback_files($this->snapshot_dir . 'plugins', WP_PLUGIN_DIR);
                    break;
                case 'themes':
                    $this->rollback_files($this->snapshot_dir . 'themes', get_theme_root());
                    break;
                case 'wp_config':
                    if (file_exists($this->snapshot_dir . 'wp-config/wp-config.php')) {
                        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_copy -- Internal rollback operation.
                        copy($this->snapshot_dir . 'wp-config/wp-config.php', ABSPATH . 'wp-config.php');
                    }
                    break;
            }
        }

        $this->logger->info('Rollback completed', $backup_id);
        $this->cleanup_snapshot();
    }

    /**
     * Rollback database by re-importing the snapshot SQL file.
     */
    private function rollback_database() {
        if (empty($this->snapshot_db_file) || !file_exists($this->snapshot_db_file)) {
            return;
        }

        global $wpdb;

        $file = new SplFileObject($this->snapshot_db_file);
        $file->setFlags(SplFileObject::READ_AHEAD | SplFileObject::SKIP_EMPTY);

        $query = '';
        while (!$file->eof()) {
            $line = trim($file->fgets());

            if (empty($line) || substr($line, 0, 2) === '--') {
                continue;
            }

            $query .= $line . ' ';

            if (substr($line, -1) === ';') {
                $query = trim($query);
                $query = substr($query, 0, -1);
                if (!empty($query)) {
                    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                    $wpdb->query($query);
                }
                $query = '';
            }
        }

        $file = null;
    }

    /**
     * Rollback files by restoring snapshot copies.
     */
    private function rollback_files($snapshot_source, $destination) {
        if (!is_dir($snapshot_source)) {
            return;
        }

        $this->delete_directory($destination);
        wp_mkdir_p($destination);

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($snapshot_source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $target_path = $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName();

            if ($item->isDir()) {
                wp_mkdir_p($target_path);
            } else {
                // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_copy -- Internal rollback operation.
                copy($item, $target_path);
            }
        }
    }

    /**
     * Restore database using memory-efficient line-by-line streaming.
     */
    private function restore_database_streaming($sql_file, $backup_id) {
        global $wpdb;

        if (!file_exists($sql_file)) {
            throw new Exception(esc_html__('Database backup file not found', 'flybackup'));
        }

        if (0 === filesize($sql_file)) {
            throw new Exception(esc_html__('Database backup file is empty', 'flybackup'));
        }

        $this->logger->info('Starting database restore (streaming)', $backup_id);

        // Define plugin tables that should NOT be restored.
        $plugin_tables = array(
            $wpdb->prefix . 'fly_backup_backups',
            $wpdb->prefix . 'fly_backup_logs',
            $wpdb->prefix . 'fly_backup_schedules',
        );

        $file = new SplFileObject($sql_file);
        $file->setFlags(SplFileObject::READ_AHEAD | SplFileObject::SKIP_EMPTY);

        $query           = '';
        $queries_executed = 0;
        $queries_failed   = 0;
        $queries_skipped  = 0;
        $skip_current_table = false;

        while (!$file->eof()) {
            $line = trim($file->fgets());

            if (empty($line) || substr($line, 0, 2) === '--') {
                continue;
            }

            // Detect plugin table statements to skip.
            if (preg_match('/DROP TABLE IF EXISTS `([^`]+)`/', $line, $matches)) {
                $table_name = $matches[1];
                if (in_array($table_name, $plugin_tables, true)) {
                    $skip_current_table = true;
                    $this->logger->info("Skipping plugin table: {$table_name}", $backup_id);
                    continue;
                }
            }

            if (preg_match('/CREATE TABLE `([^`]+)`/', $line, $matches)) {
                $table_name = $matches[1];
                if (in_array($table_name, $plugin_tables, true)) {
                    $skip_current_table = true;
                    continue;
                }
            }

            if (preg_match('/INSERT INTO `([^`]+)`/', $line, $matches)) {
                $table_name = $matches[1];
                if (in_array($table_name, $plugin_tables, true)) {
                    $skip_current_table = true;
                }
            }

            if ($skip_current_table) {
                if (substr($line, -1) === ';') {
                    $queries_skipped++;
                    $skip_current_table = false;
                }
                continue;
            }

            $query .= $line . ' ';

            if (substr($line, -1) === ';') {
                $query = trim($query);
                $query = substr($query, 0, -1);

                if (!empty($query)) {
                    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                    $result = $wpdb->query($query);

                    if (false === $result) {
                        if (!empty($wpdb->last_error)) {
                            $this->logger->warning(
                                'Query failed: ' . $wpdb->last_error . ' | Query: ' . substr($query, 0, 100) . '...',
                                $backup_id
                            );
                            $queries_failed++;

                            // Halt on structural statement failures (DROP/CREATE) to prevent partial schema.
                            if (preg_match('/^(DROP|CREATE)\s+TABLE/i', $query)) {
                                throw new Exception(
                                    sprintf(
                                        /* translators: %s: SQL error message */
                                        esc_html__('Critical database restore failure: %s', 'flybackup'),
                                        esc_html($wpdb->last_error)
                                    )
                                );
                            }
                        }
                    } else {
                        $queries_executed++;
                    }
                }

                $query = '';
            }
        }

        $file = null;

        $this->logger->info(
            "Database restore completed. Executed: {$queries_executed}, Failed: {$queries_failed}, Skipped: {$queries_skipped}",
            $backup_id
        );

        if ($queries_failed > 0) {
            $this->logger->warning('Some queries failed during restore. Check logs for details.', $backup_id);
        }

        return true;
    }

    /**
     * Restore files from source to destination.
     */
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

    /**
     * Copy directory recursively.
     */
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
                // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_copy -- Internal restore operation.
                copy($item, $target_path);
            }
        }

        return true;
    }

    /**
     * Cleanup snapshot files.
     */
    private function cleanup_snapshot() {
        if (!empty($this->snapshot_dir) && is_dir($this->snapshot_dir)) {
            $this->delete_directory($this->snapshot_dir);
            $this->snapshot_dir = '';
            $this->snapshot_db_file = '';
        }
    }

    /**
     * Cleanup temp files.
     */
    private function cleanup_temp_files($temp_dir) {
        if (!empty($temp_dir) && is_dir($temp_dir)) {
            $this->delete_directory($temp_dir);
        }
    }

    /**
     * Delete a directory recursively.
     */
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

    /**
     * Get the contents of a backup archive.
     */
    public function get_backup_contents($backup_id) {
        $backup = $this->database->get_backup($backup_id);

        if (!$backup || !file_exists($backup->storage_location)) {
            return array('error' => esc_html__('Backup not found', 'flybackup'));
        }

        $zip = new ZipArchive();
        $zip->open($backup->storage_location);

        $files = array();
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $files[] = array(
                'name' => $stat['name'],
                'size' => $stat['size'],
            );
        }

        $zip->close();

        return array(
            'backup_id'   => $backup_id,
            'backup_name' => $backup->backup_name,
            'files'       => $files,
            'total_files' => count($files),
        );
    }
}
