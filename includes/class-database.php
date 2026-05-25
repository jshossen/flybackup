<?php
/**
 * Database Manager
 *
 * @package Fly_Backup
 */

if (!defined('ABSPATH')) {
    exit;
}

class Fly_Backup_Database {
    
    const DB_VERSION = '1.1.0';
    
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $table_backups = $wpdb->prefix . 'fly_backup_backups';
        $table_logs = $wpdb->prefix . 'fly_backup_logs';
        $table_schedules = $wpdb->prefix . 'fly_backup_schedules';
        
        $sql_backups = "CREATE TABLE IF NOT EXISTS {$table_backups} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            backup_name varchar(255) NOT NULL,
            backup_type enum('full','partial','database') NOT NULL DEFAULT 'full',
            backup_size bigint(20) DEFAULT 0,
            created_at datetime NOT NULL,
            storage_location varchar(500) DEFAULT NULL,
            status enum('pending','in_progress','completed','failed') NOT NULL DEFAULT 'pending',
            duration int(11) DEFAULT 0,
            included_items text DEFAULT NULL,
            cloud_storage text DEFAULT NULL,
            error_message text DEFAULT NULL,
            PRIMARY KEY (id),
            KEY status (status),
            KEY created_at (created_at)
        ) {$charset_collate};";
        
        $sql_logs = "CREATE TABLE IF NOT EXISTS {$table_logs} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            backup_id bigint(20) DEFAULT NULL,
            log_type enum('info','warning','error','success') NOT NULL DEFAULT 'info',
            message text NOT NULL,
            created_at datetime NOT NULL,
            metadata text DEFAULT NULL,
            PRIMARY KEY (id),
            KEY backup_id (backup_id),
            KEY log_type (log_type),
            KEY created_at (created_at)
        ) {$charset_collate};";
        
        $table_cloud = $wpdb->prefix . 'fly_backup_cloud_credentials';
        
        $sql_cloud = "CREATE TABLE IF NOT EXISTS {$table_cloud} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            provider varchar(50) NOT NULL,
            credentials text NOT NULL,
            settings text DEFAULT NULL,
            is_connected tinyint(1) DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY provider (provider)
        ) {$charset_collate};";
        
        $sql_schedules = "CREATE TABLE IF NOT EXISTS {$table_schedules} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            schedule_name varchar(255) NOT NULL,
            frequency enum('hourly','daily','weekly','monthly') NOT NULL DEFAULT 'daily',
            backup_type enum('full','partial','database') NOT NULL DEFAULT 'full',
            included_items text DEFAULT NULL,
            next_run datetime DEFAULT NULL,
            last_run datetime DEFAULT NULL,
            status enum('active','paused') NOT NULL DEFAULT 'active',
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY status (status),
            KEY next_run (next_run)
        ) {$charset_collate};";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_backups);
        dbDelta($sql_logs);
        dbDelta($sql_cloud);
        dbDelta($sql_schedules);
        
        update_option('fly_backup_db_version', self::DB_VERSION);
    }
    
    public function get_backups($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'fly_backup_backups';
        
        $defaults = array(
            'limit' => 20,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC',
            'status' => null,
            'type' => null
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where_conditions = array();
        $where_values = array();

        if ($args['status']) {
            $where_conditions[] = 'status = %s';
            $where_values[] = $args['status'];
        }

        if ($args['type']) {
            $where_conditions[] = 'backup_type = %s';
            $where_values[] = $args['type'];
        }

        $allowed_orderby = array('created_at', 'id', 'backup_name', 'status', 'backup_type');
        $allowed_order = array('ASC', 'DESC');
        $orderby = in_array($args['orderby'], $allowed_orderby, true) ? $args['orderby'] : 'created_at';
        $order = in_array(strtoupper($args['order']), $allowed_order, true) ? strtoupper($args['order']) : 'DESC';

        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
            return $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$table} {$where_clause} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d",
                    array_merge($where_values, array($args['limit'], $args['offset']))
                )
            );
        }

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d",
                $args['limit'],
                $args['offset']
            )
        );
    }
    
    public function get_backup($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'fly_backup_backups';
        
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));
    }
    
    public function create_backup($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'fly_backup_backups';
        
        $defaults = array(
            'backup_name' => 'backup_' . gmdate('Y-m-d_H-i-s'),
            'backup_type' => 'full',
            'backup_size' => 0,
            'created_at' => current_time('mysql'),
            'status' => 'pending',
            'included_items' => null
        );
        
        $data = wp_parse_args($data, $defaults);
        
        if (is_array($data['included_items'])) {
            $data['included_items'] = json_encode($data['included_items']);
        }
        
        $wpdb->insert($table, $data);
        
        return $wpdb->insert_id;
    }
    
    public function update_backup($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'fly_backup_backups';
        
        if (isset($data['included_items']) && is_array($data['included_items'])) {
            $data['included_items'] = json_encode($data['included_items']);
        }
        
        return $wpdb->update($table, $data, array('id' => $id));
    }
    
    public function delete_backup($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'fly_backup_backups';
        
        return $wpdb->delete($table, array('id' => $id));
    }
    
    public function get_logs($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'fly_backup_logs';
        
        $defaults = array(
            'limit' => 100,
            'offset' => 0,
            'backup_id' => null,
            'log_type' => null
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where_conditions = array();
        $where_values = array();

        if ($args['backup_id']) {
            $where_conditions[] = 'backup_id = %d';
            $where_values[] = $args['backup_id'];
        }

        if ($args['log_type']) {
            $where_conditions[] = 'log_type = %s';
            $where_values[] = $args['log_type'];
        }

        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
            return $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$table} {$where_clause} ORDER BY created_at DESC LIMIT %d OFFSET %d",
                    array_merge($where_values, array($args['limit'], $args['offset']))
                )
            );
        }

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $args['limit'],
                $args['offset']
            )
        );
    }
    
    public function add_log($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'fly_backup_logs';
        
        $defaults = array(
            'backup_id' => null,
            'log_type' => 'info',
            'message' => '',
            'created_at' => current_time('mysql'),
            'metadata' => null
        );
        
        $data = wp_parse_args($data, $defaults);
        
        if (is_array($data['metadata'])) {
            $data['metadata'] = json_encode($data['metadata']);
        }
        
        return $wpdb->insert($table, $data);
    }
    
    public function get_schedules($status = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'fly_backup_schedules';
        
        if ($status) {
            return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE status = %s ORDER BY created_at DESC", $status));
        }
        
        return $wpdb->get_results("SELECT * FROM {$table} ORDER BY created_at DESC");
    }
    
    public function get_schedule($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'fly_backup_schedules';
        
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));
    }
    
    public function create_schedule($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'fly_backup_schedules';
        
        $defaults = array(
            'schedule_name' => 'Schedule ' . gmdate('Y-m-d H:i:s'),
            'frequency' => 'daily',
            'backup_type' => 'full',
            'status' => 'active',
            'created_at' => current_time('mysql')
        );
        
        $data = wp_parse_args($data, $defaults);
        
        if (is_array($data['included_items'])) {
            $data['included_items'] = json_encode($data['included_items']);
        }
        
        $wpdb->insert($table, $data);
        
        return $wpdb->insert_id;
    }
    
    public function update_schedule($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'fly_backup_schedules';
        
        if (isset($data['included_items']) && is_array($data['included_items'])) {
            $data['included_items'] = json_encode($data['included_items']);
        }
        
        return $wpdb->update($table, $data, array('id' => $id));
    }
    
    public function delete_schedule($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'fly_backup_schedules';
        
        return $wpdb->delete($table, array('id' => $id));
    }
    
    public function get_cloud_credentials($provider = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'fly_backup_cloud_credentials';
        
        if ($provider) {
            $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE provider = %s", $provider));
            if ($row) {
                $row->credentials = json_decode($row->credentials, true);
                $row->settings = $row->settings ? json_decode($row->settings, true) : array();
            }
            return $row;
        }
        
        $results = $wpdb->get_results("SELECT * FROM {$table}");
        foreach ($results as $row) {
            $row->credentials = json_decode($row->credentials, true);
            $row->settings = $row->settings ? json_decode($row->settings, true) : array();
        }
        return $results;
    }
    
    public function save_cloud_credentials($provider, $credentials, $settings = array(), $is_connected = false) {
        global $wpdb;
        $table = $wpdb->prefix . 'fly_backup_cloud_credentials';
        
        $data = array(
            'provider' => $provider,
            'credentials' => json_encode($credentials),
            'settings' => json_encode($settings),
            'is_connected' => $is_connected ? 1 : 0,
            'updated_at' => current_time('mysql')
        );
        
        $existing = $this->get_cloud_credentials($provider);
        
        if ($existing) {
            return $wpdb->update($table, $data, array('provider' => $provider));
        }
        
        $data['created_at'] = current_time('mysql');
        $wpdb->insert($table, $data);
        return $wpdb->insert_id;
    }
    
    public function delete_cloud_credentials($provider) {
        global $wpdb;
        $table = $wpdb->prefix . 'fly_backup_cloud_credentials';
        
        return $wpdb->delete($table, array('provider' => $provider));
    }
    
    public function get_total_backup_size() {
        global $wpdb;
        $table = $wpdb->prefix . 'fly_backup_backups';
        
        $result = $wpdb->get_var("SELECT SUM(backup_size) FROM {$table} WHERE status = 'completed'");
        
        return $result ? (int) $result : 0;
    }
    
    public function get_backup_count($status = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'fly_backup_backups';
        
        if ($status) {
            return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE status = %s", $status));
        }
        
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    }
}
