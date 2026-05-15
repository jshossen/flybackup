<?php
/**
 * REST API Class
 *
 * @package Auto_Backup
 */

if (!defined('ABSPATH')) {
    exit;
}

class Auto_Backup_Rest_API {
    
    private $namespace = 'auto-backup/v1';
    private $database;
    private $backup_engine;
    private $restore_engine;
    private $scheduler;
    private $health_check;
    private $logger;
    private $comparison;
    
    public function __construct() {
        $this->database = new Auto_Backup_Database();
        $this->backup_engine = new Auto_Backup_Backup_Engine();
        $this->restore_engine = new Auto_Backup_Restore_Engine();
        $this->scheduler = new Auto_Backup_Scheduler();
        $this->health_check = new Auto_Backup_Health_Check();
        $this->logger = new Auto_Backup_Logger();
        $this->comparison = new Auto_Backup_Comparison();
        
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    public function register_routes() {
        register_rest_route($this->namespace, '/backups', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_backups'),
                'permission_callback' => array($this, 'check_permission')
            ),
            array(
                'methods' => 'POST',
                'callback' => array($this, 'create_backup'),
                'permission_callback' => array($this, 'check_permission')
            )
        ));
        
        register_rest_route($this->namespace, '/backups/(?P<id>\d+)', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_backup'),
                'permission_callback' => array($this, 'check_permission')
            ),
            array(
                'methods' => 'DELETE',
                'callback' => array($this, 'delete_backup'),
                'permission_callback' => array($this, 'check_permission')
            )
        ));
        
        register_rest_route($this->namespace, '/backups/(?P<id>\d+)/restore', array(
            'methods' => 'POST',
            'callback' => array($this, 'restore_backup'),
            'permission_callback' => array($this, 'check_permission')
        ));
        
        register_rest_route($this->namespace, '/backups/(?P<id>\d+)/download', array(
            'methods' => 'GET',
            'callback' => array($this, 'download_backup'),
            'permission_callback' => array($this, 'check_permission')
        ));
        
        register_rest_route($this->namespace, '/schedules', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_schedules'),
                'permission_callback' => array($this, 'check_permission')
            ),
            array(
                'methods' => 'POST',
                'callback' => array($this, 'create_schedule'),
                'permission_callback' => array($this, 'check_permission')
            )
        ));
        
        register_rest_route($this->namespace, '/schedules/(?P<id>\d+)', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_schedule'),
                'permission_callback' => array($this, 'check_permission')
            ),
            array(
                'methods' => 'PUT',
                'callback' => array($this, 'update_schedule'),
                'permission_callback' => array($this, 'check_permission')
            ),
            array(
                'methods' => 'DELETE',
                'callback' => array($this, 'delete_schedule'),
                'permission_callback' => array($this, 'check_permission')
            )
        ));
        
        register_rest_route($this->namespace, '/health', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_health'),
            'permission_callback' => array($this, 'check_permission')
        ));
        
        register_rest_route($this->namespace, '/logs', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_logs'),
            'permission_callback' => array($this, 'check_permission')
        ));
        
        register_rest_route($this->namespace, '/settings', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_settings'),
                'permission_callback' => array($this, 'check_permission')
            ),
            array(
                'methods' => 'POST',
                'callback' => array($this, 'update_settings'),
                'permission_callback' => array($this, 'check_permission')
            )
        ));
        
        register_rest_route($this->namespace, '/stats', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_stats'),
            'permission_callback' => array($this, 'check_permission')
        ));
        
        // Backup comparison endpoints
        register_rest_route($this->namespace, '/backups/(?P<id>\d+)/details', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_backup_details'),
            'permission_callback' => array($this, 'check_permission')
        ));
        
        register_rest_route($this->namespace, '/backups/(?P<id>\d+)/compare/current', array(
            'methods' => 'GET',
            'callback' => array($this, 'compare_current_vs_backup'),
            'permission_callback' => array($this, 'check_permission')
        ));
        
        register_rest_route($this->namespace, '/backups/compare', array(
            'methods' => 'POST',
            'callback' => array($this, 'compare_backup_vs_backup'),
            'permission_callback' => array($this, 'check_permission')
        ));
        
        register_rest_route($this->namespace, '/backups/(?P<id>\d+)/tables/(?P<table>[a-zA-Z0-9_]+)/diff', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_table_diff'),
            'permission_callback' => array($this, 'check_permission')
        ));
    }
    
    public function check_permission() {
        return current_user_can('manage_options');
    }
    
    public function get_backups($request) {
        $params = $request->get_params();
        
        $args = array(
            'limit' => isset($params['limit']) ? intval($params['limit']) : 20,
            'offset' => isset($params['offset']) ? intval($params['offset']) : 0,
            'status' => isset($params['status']) ? sanitize_text_field($params['status']) : null,
            'type' => isset($params['type']) ? sanitize_text_field($params['type']) : null
        );
        
        $backups = $this->database->get_backups($args);
        $total = $this->database->get_backup_count();
        
        foreach ($backups as &$backup) {
            $backup->included_items = json_decode($backup->included_items, true);
            $backup->size_formatted = auto_backup_format_bytes($backup->backup_size);
            $backup->time_ago = auto_backup_time_ago($backup->created_at);
        }
        
        return new WP_REST_Response(array(
            'backups' => $backups,
            'total' => $total
        ), 200);
    }
    
    public function get_backup($request) {
        $id = $request['id'];
        $backup = $this->database->get_backup($id);
        
        if (!$backup) {
            return new WP_Error('not_found', 'Backup not found', array('status' => 404));
        }
        
        $backup->included_items = json_decode($backup->included_items, true);
        $backup->size_formatted = auto_backup_format_bytes($backup->backup_size);
        $backup->time_ago = auto_backup_time_ago($backup->created_at);
        
        return new WP_REST_Response($backup, 200);
    }
    
    public function create_backup($request) {
        $params = $request->get_json_params();
        
        $type = isset($params['type']) ? sanitize_text_field($params['type']) : 'full';
        $items = isset($params['items']) ? array_map('sanitize_text_field', $params['items']) : array();
        
        $result = $this->backup_engine->create_backup($type, $items);
        
        if ($result['success']) {
            return new WP_REST_Response($result, 201);
        } else {
            return new WP_Error('backup_failed', $result['message'], array('status' => 500));
        }
    }
    
    public function delete_backup($request) {
        $id = $request['id'];
        $result = $this->backup_engine->delete_backup($id);
        
        if ($result['success']) {
            return new WP_REST_Response($result, 200);
        } else {
            return new WP_Error('delete_failed', $result['message'], array('status' => 500));
        }
    }
    
    public function restore_backup($request) {
        $id = $request['id'];
        $params = $request->get_json_params();
        $items = isset($params['items']) ? array_map('sanitize_text_field', $params['items']) : array();
        
        $result = $this->restore_engine->restore_backup($id, $items);
        
        if ($result['success']) {
            return new WP_REST_Response($result, 200);
        } else {
            return new WP_Error('restore_failed', $result['message'], array('status' => 500));
        }
    }
    
    public function download_backup($request) {
        $id = $request['id'];
        $this->backup_engine->download_backup($id);
    }
    
    public function get_schedules($request) {
        $schedules = $this->scheduler->get_schedules();
        
        foreach ($schedules as &$schedule) {
            $schedule->included_items = json_decode($schedule->included_items, true);
            $schedule->next_run_formatted = auto_backup_format_next_run($schedule->next_run);
        }
        
        return new WP_REST_Response($schedules, 200);
    }
    
    public function get_schedule($request) {
        $id = $request['id'];
        $schedule = $this->database->get_schedule($id);
        
        if (!$schedule) {
            return new WP_Error('not_found', 'Schedule not found', array('status' => 404));
        }
        
        $schedule->included_items = json_decode($schedule->included_items, true);
        
        return new WP_REST_Response($schedule, 200);
    }
    
    public function create_schedule($request) {
        $params = $request->get_json_params();
        
        $name = isset($params['schedule_name']) ? sanitize_text_field($params['schedule_name']) : 'New Schedule';
        $frequency = isset($params['frequency']) ? sanitize_text_field($params['frequency']) : 'daily';
        $type = isset($params['backup_type']) ? sanitize_text_field($params['backup_type']) : 'full';
        $items = isset($params['items']) ? array_map('sanitize_text_field', $params['items']) : array();
        
        $result = $this->scheduler->create_schedule($name, $frequency, $type, $items);
        
        if ($result['success']) {
            return new WP_REST_Response($result, 201);
        } else {
            return new WP_Error('schedule_failed', $result['message'], array('status' => 500));
        }
    }
    
    public function update_schedule($request) {
        $id = $request['id'];
        $params = $request->get_json_params();
        
        $result = $this->scheduler->update_schedule($id, $params);
        
        if ($result['success']) {
            return new WP_REST_Response($result, 200);
        } else {
            return new WP_Error('update_failed', $result['message'], array('status' => 500));
        }
    }
    
    public function delete_schedule($request) {
        $id = $request['id'];
        $result = $this->scheduler->delete_schedule($id);
        
        if ($result['success']) {
            return new WP_REST_Response($result, 200);
        } else {
            return new WP_Error('delete_failed', $result['message'], array('status' => 500));
        }
    }
    
    public function get_health($request) {
        $health = $this->health_check->run_all_checks();
        $recommendations = $this->health_check->get_recommendations();
        $system_info = $this->health_check->get_system_info();
        
        return new WP_REST_Response(array(
            'health' => $health,
            'recommendations' => $recommendations,
            'system_info' => $system_info
        ), 200);
    }
    
    public function get_logs($request) {
        $params = $request->get_params();
        
        $args = array(
            'limit' => isset($params['limit']) ? intval($params['limit']) : 100,
            'offset' => isset($params['offset']) ? intval($params['offset']) : 0,
            'backup_id' => isset($params['backup_id']) ? intval($params['backup_id']) : null,
            'log_type' => isset($params['log_type']) ? sanitize_text_field($params['log_type']) : null
        );
        
        $logs = $this->logger->get_logs($args);
        
        return new WP_REST_Response($logs, 200);
    }
    
    public function get_settings($request) {
        $settings = auto_backup_get_settings();
        $retention_count = get_option('auto_backup_retention_count', 5);
        
        return new WP_REST_Response(array(
            'settings' => $settings,
            'retention_count' => $retention_count
        ), 200);
    }
    
    public function update_settings($request) {
        $params = $request->get_json_params();
        
        if (isset($params['settings'])) {
            auto_backup_update_settings($params['settings']);
        }
        
        if (isset($params['retention_count'])) {
            update_option('auto_backup_retention_count', intval($params['retention_count']));
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Settings updated successfully'
        ), 200);
    }
    
    public function get_stats($request) {
        $retention_manager = new Auto_Backup_Retention_Manager();
        $storage = $retention_manager->get_storage_usage();
        $newest_backup = $retention_manager->get_newest_backup();
        $schedules = $this->scheduler->get_active_schedules();
        
        $next_scheduled = null;
        if (!empty($schedules)) {
            usort($schedules, function($a, $b) {
                return strtotime($a->next_run) - strtotime($b->next_run);
            });
            $next_scheduled = $schedules[0];
        }
        
        return new WP_REST_Response(array(
            'total_backups' => $storage['backup_count'],
            'total_size' => $storage['total_size_formatted'],
            'available_space' => $storage['available_space_formatted'],
            'last_backup' => $newest_backup ? array(
                'name' => $newest_backup->backup_name,
                'date' => $newest_backup->created_at,
                'time_ago' => auto_backup_time_ago($newest_backup->created_at),
                'size' => auto_backup_format_bytes($newest_backup->backup_size)
            ) : null,
            'next_scheduled' => $next_scheduled ? array(
                'name' => $next_scheduled->schedule_name,
                'date' => $next_scheduled->next_run,
                'time_until' => auto_backup_format_next_run($next_scheduled->next_run)
            ) : null
        ), 200);
    }
    
    // Comparison methods
    public function get_backup_details($request) {
        $id = $request['id'];
        $details = $this->comparison->get_backup_details($id);
        
        if (isset($details['error'])) {
            return new WP_Error('details_failed', $details['error'], array('status' => 422));
        }
        
        return new WP_REST_Response($details, 200);
    }
    
    public function compare_current_vs_backup($request) {
        $id = $request['id'];
        $result = $this->comparison->compare_current_vs_backup($id);
        
        if (isset($result['error'])) {
            return new WP_Error('compare_failed', $result['error'], array('status' => 422));
        }
        
        return new WP_REST_Response($result, 200);
    }
    
    public function compare_backup_vs_backup($request) {
        $params = $request->get_json_params();
        
        $source_id = isset($params['source_id']) ? intval($params['source_id']) : 0;
        $target_id = isset($params['target_id']) ? intval($params['target_id']) : 0;
        
        if (!$source_id || !$target_id) {
            return new WP_Error('invalid_params', 'Source and target backup IDs are required', array('status' => 400));
        }
        
        $result = $this->comparison->compare_backup_vs_backup($source_id, $target_id);
        
        if (isset($result['error'])) {
            return new WP_Error('compare_failed', $result['error'], array('status' => 422));
        }
        
        return new WP_REST_Response($result, 200);
    }
    
    public function get_table_diff($request) {
        $id = $request['id'];
        $table = $request['table'];
        
        $source_backup_id = isset($_GET['source_backup_id']) ? intval($_GET['source_backup_id']) : 0;
        $target_backup_id = isset($_GET['target_backup_id']) ? intval($_GET['target_backup_id']) : $id;
        
        $result = $this->comparison->get_table_diff($table, $source_backup_id, $target_backup_id);
        
        return new WP_REST_Response($result, 200);
    }
}
