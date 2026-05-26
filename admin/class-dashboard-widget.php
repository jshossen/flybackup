<?php
/**
 * Dashboard Widget Class
 *
 * @package Fly_Backup
 */

if (!defined('ABSPATH')) {
    exit;
}

class Fly_Backup_Dashboard_Widget {
    
    public function __construct() {
        add_action('wp_dashboard_setup', array($this, 'register_widget'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
    }
    
    public function enqueue_styles($hook) {
        if ($hook !== 'index.php') {
            return;
        }
        
        wp_enqueue_style(
            'flybackup-dashboard-widget',
            FLY_BACKUP_PLUGIN_URL . 'assets/css/dashboard-widget.css',
            array(),
            FLY_BACKUP_VERSION
        );
    }
    
    public function register_widget() {
        wp_add_dashboard_widget(
            'fly_backup_widget',
            __('Fly Backup Status', 'flybackup'),
            array($this, 'render_widget')
        );
    }
    
    public function render_widget() {
        $database = new Fly_Backup_Database();
        $retention_manager = new Fly_Backup_Retention_Manager();
        $scheduler = new Fly_Backup_Scheduler();
        
        $newest_backup = $retention_manager->get_newest_backup();
        $schedules = $scheduler->get_active_schedules();
        $storage = $retention_manager->get_storage_usage();
        
        $next_scheduled = null;
        if (!empty($schedules)) {
            usort($schedules, function($a, $b) {
                return strtotime($a->next_run) - strtotime($b->next_run);
            });
            $next_scheduled = $schedules[0];
        }
        
        ?>
        <div class="flybackup-dashboard-widget">
            <?php if ($newest_backup): ?>
                <div class="flybackup-stat">
                    <div class="flybackup-stat-label"><?php esc_html_e('Last Backup', 'flybackup'); ?></div>
                    <div class="flybackup-stat-value">
                        <?php echo esc_html(fly_backup_time_ago($newest_backup->created_at)); ?>
                        (<?php echo esc_html(fly_backup_format_bytes($newest_backup->backup_size)); ?>)
                    </div>
                </div>
            <?php else: ?>
                <div class="flybackup-stat">
                    <div class="flybackup-stat-label"><?php esc_html_e('Last Backup', 'flybackup'); ?></div>
                    <div class="flybackup-stat-value"><?php esc_html_e('No backups yet', 'flybackup'); ?></div>
                </div>
            <?php endif; ?>
            
            <?php if ($next_scheduled): ?>
                <div class="flybackup-stat">
                    <div class="flybackup-stat-label"><?php esc_html_e('Next Scheduled Backup', 'flybackup'); ?></div>
                    <div class="flybackup-stat-value">
                        <?php echo esc_html($next_scheduled->schedule_name); ?> -
                        <?php echo esc_html(gmdate('M j, Y g:i A', strtotime($next_scheduled->next_run))); ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="flybackup-stat">
                    <div class="flybackup-stat-label"><?php esc_html_e('Next Scheduled Backup', 'flybackup'); ?></div>
                    <div class="flybackup-stat-value"><?php esc_html_e('No schedules configured', 'flybackup'); ?></div>
                </div>
            <?php endif; ?>
            
            <div class="flybackup-stat">
                <div class="flybackup-stat-label"><?php esc_html_e('Total Backups', 'flybackup'); ?></div>
                <div class="flybackup-stat-value">
                    <?php echo esc_html($storage['backup_count']); ?> 
                    (<?php echo esc_html($storage['total_size_formatted']); ?>)
                </div>
            </div>
            
            <div class="flybackup-actions">
                <a href="<?php echo esc_url(admin_url('admin.php?page=flybackup')); ?>" class="button button-primary">
                    <?php esc_html_e('Manage Backups', 'flybackup'); ?>
                </a>
            </div>
        </div>
        <?php
    }
}
