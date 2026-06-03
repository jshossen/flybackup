<?php
/**
 * Backup Comparison Engine
 *
 * Provides functionality to compare backups with current site or other backups
 *
 * @package Fly_Backup
 */

if (!defined('ABSPATH')) {
    exit;
}

class Fly_Backup_Comparison {
    
    private $logger;
    
    public function __construct() {
        $this->logger = new Fly_Backup_Logger();
    }
    
    /**
     * Ensure backup file is available locally, downloading from cloud if needed
     *
     * @param object $backup Backup record
     * @return string|false Local file path or false
     */
    private function ensure_backup_local($backup) {
        if (file_exists($backup->storage_location)) {
            return $backup->storage_location;
        }
        
        // Try to download from cloud
        if (!empty($backup->cloud_storage) && class_exists('Fly_Backup_Cloud_Manager')) {
            $cloud_storage = json_decode($backup->cloud_storage, true);
            if (!empty($cloud_storage['provider']) && !empty($cloud_storage['remote_path'])) {
                $this->logger->info('Downloading backup from cloud for comparison: ' . $cloud_storage['provider']);
                
                $cloud_manager = new Fly_Backup_Cloud_Manager();
                $result = $cloud_manager->download_backup(
                    $cloud_storage['provider'],
                    $cloud_storage['remote_path'],
                    $backup->storage_location
                );
                
                if ($result['success'] && file_exists($backup->storage_location)) {
                    $this->logger->info('Backup downloaded from cloud successfully');
                    return $backup->storage_location;
                }
                
                $this->logger->warning('Failed to download backup from cloud: ' . $result['message']);
            }
        }
        
        return false;
    }
    
    /**
     * Get detailed information about a backup's contents
     *
     * @param int $backup_id Backup ID
     * @return array Backup details
     */
    public function get_backup_details($backup_id) {
        global $wpdb;
        
        $backup = $this->get_backup_record($backup_id);
        if (!$backup) {
            return array('error' => 'Backup not found');
        }
        
        $zip_path = $this->ensure_backup_local($backup);
        $file_exists = $zip_path !== false;
        
        $details = array(
            'backup_id' => $backup_id,
            'backup_name' => $backup->backup_name,
            'backup_type' => $backup->backup_type,
            'created_at' => $backup->created_at,
            'file_size' => $this->format_file_size($backup->backup_size),
            'file_path' => $backup->storage_location,
            'file_exists' => $file_exists,
            'cloud_storage' => $backup->cloud_storage ? json_decode($backup->cloud_storage, true) : null,
            'database' => array(),
            'files' => array()
        );
        
        if (!$file_exists) {
            $details['warning'] = 'Backup ZIP file not found on disk or cloud. Only database record information is available.';
            return $details;
        }
        
        // Extract and parse database.sql if present
        $sql_content = $this->extract_sql_from_zip($zip_path);
        if ($sql_content) {
            $details['database'] = $this->parse_sql_structure($sql_content);
        }
        
        // Get file list from ZIP
        $details['files'] = $this->get_zip_file_list($zip_path);
        
        return $details;
    }
    
    /**
     * Compare current database with backup
     *
     * @param int $backup_id Backup ID to compare with
     * @return array Comparison results
     */
    public function compare_current_vs_backup($backup_id) {
        global $wpdb;
        
        $backup = $this->get_backup_record($backup_id);
        if (!$backup) {
            return array('error' => 'Backup not found');
        }
        
        $zip_path = $this->ensure_backup_local($backup);
        if (!$zip_path) {
            return array(
                'error' => 'Backup file not found locally or in cloud',
                'mode' => 'current_vs_backup',
                'backup_id' => $backup_id,
                'summary' => array(
                    'tables_added' => 0,
                    'tables_removed' => 0,
                    'tables_changed' => 0,
                    'tables_unchanged' => 0,
                    'files_added' => 0,
                    'files_removed' => 0,
                    'files_changed' => 0,
                    'files_unchanged' => 0
                ),
                'database' => array(),
                'files' => array()
            );
        }
        
        $result = array(
            'mode' => 'current_vs_backup',
            'backup_id' => $backup_id,
            'summary' => array(
                'tables_added' => 0,
                'tables_removed' => 0,
                'tables_changed' => 0,
                'tables_unchanged' => 0,
                'files_added' => 0,
                'files_removed' => 0,
                'files_changed' => 0,
                'files_unchanged' => 0
            ),
            'database' => array(),
            'files' => array()
        );
        
        // Get current database tables (excluding plugin tables)
        $current_tables = $this->get_current_database_tables();
        
        // Get backup database tables
        $sql_content = $this->extract_sql_from_zip($zip_path);
        $backup_tables = $sql_content ? $this->parse_sql_structure($sql_content, true) : array();
        
        // Compare tables
        $current_table_names = array_keys($current_tables);
        $backup_table_names = array_keys($backup_tables);
        
        $all_tables = array_unique(array_merge($current_table_names, $backup_table_names));
        
        foreach ($all_tables as $table_name) {
            $in_current = in_array($table_name, $current_table_names);
            $in_backup = in_array($table_name, $backup_table_names);
            
            if ($in_current && !$in_backup) {
                $result['database'][$table_name] = array(
                    'status' => 'removed',
                    'rows_current' => $current_tables[$table_name]['rows'],
                    'rows_backup' => 0,
                    'size_current' => $current_tables[$table_name]['size'],
                    'size_backup' => '0 B'
                );
                $result['summary']['tables_removed']++;
            } elseif (!$in_current && $in_backup) {
                $result['database'][$table_name] = array(
                    'status' => 'added',
                    'rows_current' => 0,
                    'rows_backup' => $backup_tables[$table_name]['rows'],
                    'size_current' => '0 B',
                    'size_backup' => $backup_tables[$table_name]['size']
                );
                $result['summary']['tables_added']++;
            } else {
                // Compare row counts
                $current_rows = $current_tables[$table_name]['rows'];
                $backup_rows = $backup_tables[$table_name]['rows'];
                
                if ($current_rows != $backup_rows) {
                    $result['database'][$table_name] = array(
                        'status' => 'changed',
                        'rows_current' => $current_rows,
                        'rows_backup' => $backup_rows,
                        'rows_diff' => $backup_rows - $current_rows,
                        'size_current' => $current_tables[$table_name]['size'],
                        'size_backup' => $backup_tables[$table_name]['size']
                    );
                    $result['summary']['tables_changed']++;
                } else {
                    $result['database'][$table_name] = array(
                        'status' => 'unchanged',
                        'rows_current' => $current_rows,
                        'rows_backup' => $backup_rows,
                        'size_current' => $current_tables[$table_name]['size'],
                        'size_backup' => $backup_tables[$table_name]['size']
                    );
                    $result['summary']['tables_unchanged']++;
                }
            }
        }
        
        // Compare files
        $result['files'] = $this->compare_files_with_current($zip_path);
        
        // Update summary from file comparison
        foreach ($result['files'] as $file) {
            if ($file['status'] === 'added') $result['summary']['files_added']++;
            elseif ($file['status'] === 'removed') $result['summary']['files_removed']++;
            elseif ($file['status'] === 'changed') $result['summary']['files_changed']++;
            else $result['summary']['files_unchanged']++;
        }
        
        return $result;
    }
    
    /**
     * Compare two backups with each other
     *
     * @param int $source_id First backup ID
     * @param int $target_id Second backup ID
     * @return array Comparison results
     */
    public function compare_backup_vs_backup($source_id, $target_id) {
        $source = $this->get_backup_record($source_id);
        $target = $this->get_backup_record($target_id);
        
        if (!$source) return array('error' => 'Source backup not found');
        if (!$target) return array('error' => 'Target backup not found');
        
        $source_zip = $this->ensure_backup_local($source);
        $target_zip = $this->ensure_backup_local($target);
        
        if (!$source_zip) return array('error' => 'Source backup file not found locally or in cloud');
        if (!$target_zip) return array('error' => 'Target backup file not found locally or in cloud');
        
        $result = array(
            'mode' => 'backup_vs_backup',
            'source_id' => $source_id,
            'target_id' => $target_id,
            'summary' => array(
                'tables_added' => 0,
                'tables_removed' => 0,
                'tables_changed' => 0,
                'tables_unchanged' => 0,
                'files_added' => 0,
                'files_removed' => 0,
                'files_changed' => 0,
                'files_unchanged' => 0
            ),
            'database' => array(),
            'files' => array()
        );
        
        // Get database structures from both backups
        $source_sql = $this->extract_sql_from_zip($source_zip);
        $target_sql = $this->extract_sql_from_zip($target_zip);
        
        $source_tables = $source_sql ? $this->parse_sql_structure($source_sql, true) : array();
        $target_tables = $target_sql ? $this->parse_sql_structure($target_sql, true) : array();
        
        // Compare tables
        $all_tables = array_unique(array_merge(array_keys($source_tables), array_keys($target_tables)));
        
        foreach ($all_tables as $table_name) {
            $in_source = isset($source_tables[$table_name]);
            $in_target = isset($target_tables[$table_name]);
            
            if ($in_source && !$in_target) {
                $result['database'][$table_name] = array(
                    'status' => 'removed',
                    'rows_source' => $source_tables[$table_name]['rows'],
                    'rows_target' => 0,
                    'size_source' => $source_tables[$table_name]['size'],
                    'size_target' => '0 B'
                );
                $result['summary']['tables_removed']++;
            } elseif (!$in_source && $in_target) {
                $result['database'][$table_name] = array(
                    'status' => 'added',
                    'rows_source' => 0,
                    'rows_target' => $target_tables[$table_name]['rows'],
                    'size_source' => '0 B',
                    'size_target' => $target_tables[$table_name]['size']
                );
                $result['summary']['tables_added']++;
            } else {
                $source_rows = $source_tables[$table_name]['rows'];
                $target_rows = $target_tables[$table_name]['rows'];
                
                if ($source_rows != $target_rows) {
                    $result['database'][$table_name] = array(
                        'status' => 'changed',
                        'rows_source' => $source_rows,
                        'rows_target' => $target_rows,
                        'rows_diff' => $target_rows - $source_rows,
                        'size_source' => $source_tables[$table_name]['size'],
                        'size_target' => $target_tables[$table_name]['size']
                    );
                    $result['summary']['tables_changed']++;
                } else {
                    $result['database'][$table_name] = array(
                        'status' => 'unchanged',
                        'rows_source' => $source_rows,
                        'rows_target' => $target_rows,
                        'size_source' => $source_tables[$table_name]['size'],
                        'size_target' => $target_tables[$table_name]['size']
                    );
                    $result['summary']['tables_unchanged']++;
                }
            }
        }
        
        // Compare files
        $result['files'] = $this->compare_backup_files($source_zip, $target_zip);
        
        // Update summary
        foreach ($result['files'] as $file) {
            if ($file['status'] === 'added') $result['summary']['files_added']++;
            elseif ($file['status'] === 'removed') $result['summary']['files_removed']++;
            elseif ($file['status'] === 'changed') $result['summary']['files_changed']++;
            else $result['summary']['files_unchanged']++;
        }
        
        return $result;
    }
    
    /**
     * Get detailed row-level diff for a specific table
     *
     * @param string $table_name Table name
     * @param int $source_backup_id Source backup ID (0 for current)
     * @param int $target_backup_id Target backup ID
     * @return array Row differences
     */
    public function get_table_diff($table_name, $source_backup_id, $target_backup_id) {
        global $wpdb;
        
        $result = array(
            'table_name' => $table_name,
            'added' => array(),
            'removed' => array(),
            'modified' => array(),
            'total_added' => 0,
            'total_removed' => 0,
            'total_modified' => 0
        );
        
        // Get source rows
        $source_rows = array();
        if ($source_backup_id === 0) {
            // Current database
            $source_rows = $wpdb->get_results("SELECT * FROM `{$table_name}`", ARRAY_A);
        } else {
            // From backup SQL
            $backup = $this->get_backup_record($source_backup_id);
            if ($backup) {
                $sql = $this->extract_sql_from_zip($backup->storage_location);
                if ($sql) {
                    $source_rows = $this->extract_table_rows($sql, $table_name);
                }
            }
        }
        
        // Get target rows
        $target_rows = array();
        if ($target_backup_id === 0) {
            $target_rows = $wpdb->get_results("SELECT * FROM `{$table_name}`", ARRAY_A);
        } else {
            $backup = $this->get_backup_record($target_backup_id);
            if ($backup) {
                $sql = $this->extract_sql_from_zip($backup->storage_location);
                if ($sql) {
                    $target_rows = $this->extract_table_rows($sql, $table_name);
                }
            }
        }
        
        // Index rows by primary key (assume first column is PK)
        $source_index = $this->index_rows_by_pk($source_rows);
        $target_index = $this->index_rows_by_pk($target_rows);
        
        // Find added rows (in target, not in source)
        $added_pks = array_diff_key($target_index, $source_index);
        foreach ($added_pks as $pk => $row) {
            $result['added'][] = $row;
        }
        $result['total_added'] = count($added_pks);
        
        // Find removed rows (in source, not in target)
        $removed_pks = array_diff_key($source_index, $target_index);
        foreach ($removed_pks as $pk => $row) {
            $result['removed'][] = $row;
        }
        $result['total_removed'] = count($removed_pks);
        
        // Find modified rows (same PK, different data)
        $common_pks = array_intersect_key($source_index, $target_index);
        foreach ($common_pks as $pk => $source_row) {
            $target_row = $target_index[$pk];
            if ($source_row != $target_row) {
                $result['modified'][] = array(
                    'primary_key' => $pk,
                    'before' => $source_row,
                    'after' => $target_row
                );
            }
        }
        $result['total_modified'] = count($result['modified']);
        
        // Limit detailed results to prevent memory issues
        $limit = 50;
        $result['added'] = array_slice($result['added'], 0, $limit);
        $result['removed'] = array_slice($result['removed'], 0, $limit);
        $result['modified'] = array_slice($result['modified'], 0, $limit);
        $result['has_more'] = ($result['total_added'] > $limit || $result['total_removed'] > $limit || $result['total_modified'] > $limit);
        
        return $result;
    }
    
    /**
     * Helper: Get backup record from database
     */
    private function get_backup_record($backup_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}fly_backup_backups WHERE id = %d",
            $backup_id
        ));
    }
    
    /**
     * Helper: Extract SQL content from ZIP file
     */
    private function extract_sql_from_zip($zip_path) {
        if (!file_exists($zip_path)) {
            return false;
        }
        
        $zip = new ZipArchive();
        if ($zip->open($zip_path) !== true) {
            return false;
        }
        
        $sql_content = false;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $file_name = $zip->getNameIndex($i);
            if ($file_name === 'database.sql') {
                $sql_content = $zip->getFromIndex($i);
                break;
            }
        }
        
        $zip->close();
        return $sql_content;
    }
    
    /**
     * Helper: Parse SQL file to get table structures and row counts
     */
    private function parse_sql_structure($sql_content, $for_comparison = false) {
        $table_stats = array();
        
        // Seed with CREATE TABLE list so empty tables still appear.
        preg_match_all('/CREATE TABLE `([^`]+)`/', $sql_content, $create_matches);
        foreach ($create_matches[1] as $table_name) {
            $table_stats[$table_name] = array(
                'rows' => 0,
                'size_bytes' => 0,
            );
        }

        // Parse SQL into statements safely (handles semicolons inside quoted values).
        $statements = $this->split_sql_statements($sql_content);
        foreach ($statements as $statement) {
            if (!preg_match('/INSERT\s+INTO\s+`([^`]+)`/i', $statement, $table_match, PREG_OFFSET_CAPTURE)) {
                continue;
            }

            $table_name = $table_match[1][0];
            $insert_offset = $table_match[0][1];
            $insert_statement = substr($statement, $insert_offset);

            if ('' === trim($insert_statement)) {
                continue;
            }

            if (!isset($table_stats[$table_name])) {
                $table_stats[$table_name] = array(
                    'rows' => 0,
                    'size_bytes' => 0,
                );
            }

            $table_stats[$table_name]['size_bytes'] += strlen($insert_statement);

            $values_pos = stripos($insert_statement, 'VALUES');
            if (false === $values_pos) {
                continue;
            }

            $values_part = substr($insert_statement, $values_pos + 6);
            $table_stats[$table_name]['rows'] += $this->count_insert_rows($values_part);
        }

        $tables = array();
        $tables_list = array();
        foreach ($table_stats as $table_name => $stats) {
            $table_data = array(
                'rows' => (int) $stats['rows'],
                'size' => $this->format_file_size((int) $stats['size_bytes'])
            );

            $tables[$table_name] = $table_data;
            $tables_list[] = array(
                'name' => $table_name,
                'rows' => $table_data['rows'],
                'size' => $table_data['size']
            );
        }

        if ($for_comparison) {
            return $tables;
        }

        return array('tables' => $tables_list);
    }

    /**
     * Split SQL dump into statements while respecting quoted strings.
     */
    private function split_sql_statements($sql_content) {
        $statements = array();
        $current = '';
        $length = strlen($sql_content);
        $in_string = false;
        $string_quote = '';
        $escaped = false;

        for ($i = 0; $i < $length; $i++) {
            $char = $sql_content[$i];
            $current .= $char;

            if ($in_string) {
                if ($escaped) {
                    $escaped = false;
                    continue;
                }

                if ('\\' === $char) {
                    $escaped = true;
                    continue;
                }

                if ($char === $string_quote) {
                    // Handle doubled quote escape (e.g. '').
                    if ("'" === $string_quote && $i + 1 < $length && $sql_content[$i + 1] === "'") {
                        $current .= $sql_content[$i + 1];
                        $i++;
                        continue;
                    }

                    $in_string = false;
                    $string_quote = '';
                }

                continue;
            }

            if ("'" === $char || '"' === $char) {
                $in_string = true;
                $string_quote = $char;
                continue;
            }

            if (';' === $char) {
                $statement = trim($current);
                if ('' !== $statement) {
                    $statements[] = $statement;
                }
                $current = '';
            }
        }

        $remaining = trim($current);
        if ('' !== $remaining) {
            $statements[] = $remaining;
        }

        return $statements;
    }

    /**
     * Count row tuples in an INSERT ... VALUES payload.
     */
    private function count_insert_rows($values_part) {
        $rows = 0;
        $depth = 0;
        $length = strlen($values_part);
        $in_string = false;
        $string_quote = '';
        $escaped = false;

        for ($i = 0; $i < $length; $i++) {
            $char = $values_part[$i];

            if ($in_string) {
                if ($escaped) {
                    $escaped = false;
                    continue;
                }

                if ('\\' === $char) {
                    $escaped = true;
                    continue;
                }

                if ($char === $string_quote) {
                    if ("'" === $string_quote && $i + 1 < $length && $values_part[$i + 1] === "'") {
                        $i++;
                        continue;
                    }

                    $in_string = false;
                    $string_quote = '';
                }

                continue;
            }

            if ("'" === $char || '"' === $char) {
                $in_string = true;
                $string_quote = $char;
                continue;
            }

            if ('(' === $char) {
                if (0 === $depth) {
                    $rows++;
                }
                $depth++;
                continue;
            }

            if (')' === $char && $depth > 0) {
                $depth--;
            }
        }

        return $rows;
    }
    
    /**
     * Helper: Get current database tables (excluding plugin tables)
     */
    private function get_current_database_tables() {
        global $wpdb;
        
        $plugin_tables = array(
            $wpdb->prefix . 'fly_backup_backups',
            $wpdb->prefix . 'fly_backup_logs',
            $wpdb->prefix . 'fly_backup_schedules'
        );
        
        $tables = array();
        $all_tables = $wpdb->get_results('SHOW TABLES', ARRAY_N);
        
        foreach ($all_tables as $table) {
            $table_name = $table[0];
            
            // Skip plugin tables
            if (in_array($table_name, $plugin_tables)) {
                continue;
            }
            
            // Only include WordPress tables (with prefix)
            if (strpos($table_name, $wpdb->prefix) === 0) {
                $row_count = $wpdb->get_var("SELECT COUNT(*) FROM `{$table_name}`");
                $size = $wpdb->get_var("SELECT ROUND(SUM(LENGTH(JSON_ARRAY(*)))/1024, 2) FROM `{$table_name}`") ?: 0;
                
                $tables[$table_name] = array(
                    'rows' => (int) $row_count,
                    'size' => $this->format_file_size($size * 1024)
                );
            }
        }
        
        return $tables;
    }
    
    /**
     * Helper: Get file list from ZIP with sizes
     */
    private function get_zip_file_list($zip_path) {
        $files = array(
            'uploads' => array('count' => 0, 'size' => 0, 'files' => array()),
            'plugins' => array('count' => 0, 'size' => 0, 'files' => array()),
            'themes' => array('count' => 0, 'size' => 0, 'files' => array()),
            'wp-config' => array('count' => 0, 'size' => 0, 'files' => array()),
            'other' => array('count' => 0, 'size' => 0, 'files' => array())
        );
        
        $zip = new ZipArchive();
        if ($zip->open($zip_path) !== true) {
            return $files;
        }
        
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $file_name = $zip->getNameIndex($i);
            $file_size = $zip->statIndex($i)['size'];
            
            if ($file_name === 'database.sql') continue;
            
            $category = 'other';
            if (strpos($file_name, 'uploads/') === 0) $category = 'uploads';
            elseif (strpos($file_name, 'plugins/') === 0) $category = 'plugins';
            elseif (strpos($file_name, 'themes/') === 0) $category = 'themes';
            elseif (strpos($file_name, 'wp-config') !== false) $category = 'wp-config';
            
            $files[$category]['count']++;
            $files[$category]['size'] += $file_size;
            $files[$category]['files'][] = array(
                'name' => $file_name,
                'size' => $this->format_file_size($file_size)
            );
        }
        
        $zip->close();
        
        // Format sizes
        foreach ($files as $category => $data) {
            $files[$category]['size_formatted'] = $this->format_file_size($data['size']);
        }
        
        return $files;
    }
    
    /**
     * Helper: Compare backup files with current filesystem
     */
    private function compare_files_with_current($zip_path) {
        $result = array();
        
        $zip = new ZipArchive();
        if ($zip->open($zip_path) !== true) {
            return $result;
        }
        
        $zip_files = array();
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            $stat = $zip->statIndex($i);
            if ($name !== 'database.sql') {
                $zip_files[$name] = $stat['size'];
            }
        }
        $zip->close();
        
        // Map ZIP paths to filesystem paths
        $upload_dir = wp_upload_dir();
        $path_map = array(
            'uploads/' => trailingslashit($upload_dir['basedir']),
            'plugins/' => trailingslashit(WP_PLUGIN_DIR),
            'themes/' => trailingslashit(get_theme_root()),
            'wp-config.php' => ABSPATH . 'wp-config.php'
        );
        
        foreach ($zip_files as $zip_file => $zip_size) {
            $fs_path = '';
            foreach ($path_map as $prefix => $fs_prefix) {
                if (strpos($zip_file, $prefix) === 0) {
                    $fs_path = $fs_prefix . substr($zip_file, strlen($prefix));
                    break;
                }
            }
            
            if ($fs_path && file_exists($fs_path)) {
                $fs_size = filesize($fs_path);
                if ($fs_size != $zip_size) {
                    $result[] = array(
                        'path' => $zip_file,
                        'status' => 'changed',
                        'size_backup' => $this->format_file_size($zip_size),
                        'size_current' => $this->format_file_size($fs_size)
                    );
                } else {
                    $result[] = array(
                        'path' => $zip_file,
                        'status' => 'unchanged',
                        'size_backup' => $this->format_file_size($zip_size),
                        'size_current' => $this->format_file_size($fs_size)
                    );
                }
            } else {
                $result[] = array(
                    'path' => $zip_file,
                    'status' => 'added',
                    'size_backup' => $this->format_file_size($zip_size),
                    'size_current' => '0 B'
                );
            }
        }
        
        // Check for files in current but not in backup (removed)
        $this->check_removed_files($result, $path_map);
        
        return $result;
    }
    
    /**
     * Helper: Compare files between two backups
     */
    private function compare_backup_files($source_zip, $target_zip) {
        $result = array();
        
        $source_files = $this->get_zip_files_hash($source_zip);
        $target_files = $this->get_zip_files_hash($target_zip);
        
        $all_files = array_unique(array_merge(array_keys($source_files), array_keys($target_files)));
        
        foreach ($all_files as $file) {
            $in_source = isset($source_files[$file]);
            $in_target = isset($target_files[$file]);
            
            if ($in_source && !$in_target) {
                $result[] = array(
                    'path' => $file,
                    'status' => 'removed',
                    'size_source' => $this->format_file_size($source_files[$file]),
                    'size_target' => '0 B'
                );
            } elseif (!$in_source && $in_target) {
                $result[] = array(
                    'path' => $file,
                    'status' => 'added',
                    'size_source' => '0 B',
                    'size_target' => $this->format_file_size($target_files[$file])
                );
            } elseif ($source_files[$file] != $target_files[$file]) {
                $result[] = array(
                    'path' => $file,
                    'status' => 'changed',
                    'size_source' => $this->format_file_size($source_files[$file]),
                    'size_target' => $this->format_file_size($target_files[$file])
                );
            } else {
                $result[] = array(
                    'path' => $file,
                    'status' => 'unchanged',
                    'size_source' => $this->format_file_size($source_files[$file]),
                    'size_target' => $this->format_file_size($target_files[$file])
                );
            }
        }
        
        return $result;
    }
    
    /**
     * Helper: Get files from ZIP as array
     */
    private function get_zip_files_hash($zip_path) {
        $files = array();
        
        $zip = new ZipArchive();
        if ($zip->open($zip_path) !== true) {
            return $files;
        }
        
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            $stat = $zip->statIndex($i);
            if ($name !== 'database.sql') {
                $files[$name] = $stat['size'];
            }
        }
        
        $zip->close();
        return $files;
    }
    
    /**
     * Helper: Check for files removed (in current but not in backup)
     */
    private function check_removed_files(&$result, $path_map) {
        // This is a simplified check - in production, you'd scan the entire filesystem
        // For now, we'll skip this to keep it fast
    }
    
    /**
     * Helper: Extract rows for a specific table from SQL content
     */
    private function extract_table_rows($sql_content, $table_name) {
        $rows = array();
        
        // Match INSERT statements for this table
        $pattern = '/INSERT INTO `' . preg_quote($table_name, '/') . '`\s+VALUES\s*\((.+?)\);/s';
        preg_match_all($pattern, $sql_content, $matches);
        
        // This is a simplified extraction - real implementation would need proper SQL parsing
        // For now, return empty to avoid errors
        
        return $rows;
    }
    
    /**
     * Helper: Index rows by primary key (first column)
     */
    private function index_rows_by_pk($rows) {
        $indexed = array();
        foreach ($rows as $row) {
            $keys = array_keys($row);
            if (!empty($keys)) {
                $pk = is_array($row[$keys[0]]) ? serialize($row[$keys[0]]) : $row[$keys[0]];
                $indexed[$pk] = $row;
            }
        }
        return $indexed;
    }
    
    /**
     * Helper: Format file size
     */
    private function format_file_size($bytes) {
        if ($bytes < 1024) return $bytes . ' B';
        elseif ($bytes < 1048576) return round($bytes / 1024, 2) . ' KB';
        elseif ($bytes < 1073741824) return round($bytes / 1048576, 2) . ' MB';
        else return round($bytes / 1073741824, 2) . ' GB';
    }
}
