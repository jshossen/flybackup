<?php
/**
 * Scheduler Class
 *
 * @package Auto_Backup
 */

if (!defined('ABSPATH')) {
    exit;
}

class Auto_Backup_Scheduler {
    
    private $database;
    private $logger;
    private $backup_engine;
    private static $hooks_registered = false;
    
    public function __construct() {
        $this->database = new Auto_Backup_Database();
        $this->logger = new Auto_Backup_Logger();
        
        if (!self::$hooks_registered) {
            add_action('auto_backup_scheduled_backup', array($this, 'execute_scheduled_backup'));
            add_action('auto_backup_cleanup_old_backups', array($this, 'cleanup_old_backups'));
            self::$hooks_registered = true;
        }
    }
    
    public function create_schedule($name, $frequency, $backup_type, $items = array()) {
        $next_run = $this->calculate_next_run($frequency);
        
        $schedule_data = array(
            'schedule_name' => $name,
            'frequency' => $frequency,
            'backup_type' => $backup_type,
            'included_items' => $items,
            'next_run' => $next_run,
            'status' => 'active'
        );
        
        $schedule_id = $this->database->create_schedule($schedule_data);
        
        $this->schedule_cron($schedule_id, $frequency, $next_run);
        
        $this->logger->info('Schedule created: ' . $name);
        
        return array(
            'success' => true,
            'schedule_id' => $schedule_id,
            'message' => 'Schedule created successfully'
        );
    }
    
    public function update_schedule($schedule_id, $data) {
        $schedule = $this->database->get_schedule($schedule_id);
        
        if (!$schedule) {
            return array('success' => false, 'message' => 'Schedule not found');
        }
        
        if (isset($data['frequency'])) {
            $data['next_run'] = $this->calculate_next_run($data['frequency'], $schedule->last_run);
            $this->reschedule_cron($schedule_id, $data['frequency'], $data['next_run']);
        }
        
        $this->database->update_schedule($schedule_id, $data);
        
        $this->logger->info('Schedule updated: ' . $schedule->schedule_name);
        
        return array('success' => true, 'message' => 'Schedule updated successfully');
    }
    
    public function delete_schedule($schedule_id) {
        $schedule = $this->database->get_schedule($schedule_id);
        
        if (!$schedule) {
            return array('success' => false, 'message' => 'Schedule not found');
        }
        
        $this->unschedule_cron($schedule_id);
        
        $this->database->delete_schedule($schedule_id);
        
        $this->logger->info('Schedule deleted: ' . $schedule->schedule_name);
        
        return array('success' => true, 'message' => 'Schedule deleted successfully');
    }
    
    public function pause_schedule($schedule_id) {
        $this->database->update_schedule($schedule_id, array('status' => 'paused'));
        $this->unschedule_cron($schedule_id);
        
        return array('success' => true, 'message' => 'Schedule paused');
    }
    
    public function resume_schedule($schedule_id) {
        $schedule = $this->database->get_schedule($schedule_id);
        
        if (!$schedule) {
            return array('success' => false, 'message' => 'Schedule not found');
        }
        
        $next_run = $this->calculate_next_run($schedule->frequency);
        
        $this->database->update_schedule($schedule_id, array(
            'status' => 'active',
            'next_run' => $next_run
        ));
        
        $this->schedule_cron($schedule_id, $schedule->frequency, $next_run);
        
        return array('success' => true, 'message' => 'Schedule resumed');
    }
    
    public function execute_scheduled_backup($schedule_id) {
        $schedule = $this->database->get_schedule($schedule_id);
        
        if (!$schedule || $schedule->status !== 'active') {
            return;
        }
        
        $lock_key = 'ab_sched_lock_' . $schedule_id;
        if (get_transient($lock_key)) {
            $this->logger->warning('Scheduled backup skipped: already running', $schedule_id);
            return;
        }
        set_transient($lock_key, 1, 5 * MINUTE_IN_SECONDS);
        
        $this->logger->info('Executing scheduled backup: ' . $schedule->schedule_name);
        
        if (!$this->backup_engine) {
            $this->backup_engine = new Auto_Backup_Backup_Engine();
        }
        
        $items = json_decode($schedule->included_items, true);
        
        $result = $this->backup_engine->create_backup($schedule->backup_type, $items, true);
        
        $next_run = $this->calculate_next_run($schedule->frequency);
        
        $this->database->update_schedule($schedule_id, array(
            'last_run' => current_time('mysql'),
            'next_run' => $next_run
        ));
        
        $this->schedule_cron($schedule_id, $schedule->frequency, $next_run);
        
        delete_transient($lock_key);
        
        if ($result['success']) {
            $this->logger->success('Scheduled backup completed: ' . $schedule->schedule_name);
        } else {
            $this->logger->error('Scheduled backup failed: ' . $schedule->schedule_name);
        }
    }
    
    public function calculate_next_run($frequency, $from_time = null) {
        $base_time = $from_time ? strtotime($from_time) : time();
        
        switch ($frequency) {
            case 'hourly':
                $next_run = $base_time + HOUR_IN_SECONDS;
                break;
            case 'daily':
                $next_run = $base_time + DAY_IN_SECONDS;
                break;
            case 'weekly':
                $next_run = $base_time + WEEK_IN_SECONDS;
                break;
            case 'monthly':
                $next_run = strtotime('+1 month', $base_time);
                break;
            default:
                $next_run = $base_time + DAY_IN_SECONDS;
        }
        
        return gmdate('Y-m-d H:i:s', $next_run);
    }
    
    private function schedule_cron($schedule_id, $frequency, $next_run) {
        $hook = 'auto_backup_scheduled_backup';
        $timestamp = strtotime($next_run);
        
        if (!wp_next_scheduled($hook, array($schedule_id))) {
            wp_schedule_single_event($timestamp, $hook, array($schedule_id));
        }
    }
    
    private function reschedule_cron($schedule_id, $frequency, $next_run) {
        $this->unschedule_cron($schedule_id);
        $this->schedule_cron($schedule_id, $frequency, $next_run);
    }
    
    private function unschedule_cron($schedule_id) {
        $hook = 'auto_backup_scheduled_backup';
        $timestamp = wp_next_scheduled($hook, array($schedule_id));
        
        if ($timestamp) {
            wp_unschedule_event($timestamp, $hook, array($schedule_id));
        }
    }
    
    public function get_schedules() {
        return $this->database->get_schedules();
    }
    
    public function get_active_schedules() {
        return $this->database->get_schedules('active');
    }
    
    public function cleanup_old_backups() {
        $retention_manager = new Auto_Backup_Retention_Manager();
        $retention_manager->cleanup();
    }
}
