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
    }
    
    public function register_widget() {
        wp_add_dashboard_widget(
            'fly_backup_widget',
            __('Fly Backup Status', 'fly-backup'),
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
        <div class="fly-backup-dashboard-widget">
            <style>
                .fly-backup-dashboard-widget {
                    padding: 10px 0;
                }
                .fly-backup-stat {
                    margin-bottom: 15px;
                    padding-bottom: 15px;
                    border-bottom: 1px solid #f0f0f0;
                }
                .fly-backup-stat:last-child {
                    border-bottom: none;
                    margin-bottom: 0;
                }
                .fly-backup-stat-label {
                    font-weight: 600;
                    color: #23282d;
                    margin-bottom: 5px;
                }
                .fly-backup-stat-value {
                    color: #72aee6;
                    font-size: 14px;
                }
                .fly-backup-actions {
                    margin-top: 15px;
                    padding-top: 15px;
                    border-top: 1px solid #f0f0f0;
                }
            </style>
            
            <?php if ($newest_backup): ?>
                <div class="fly-backup-stat">
                    <div class="fly-backup-stat-label"><?php esc_html_e('Last Backup', 'fly-backup'); ?></div>
                    <div class="fly-backup-stat-value">
                        <?php echo esc_html(fly_backup_time_ago($newest_backup->created_at)); ?>
                        (<?php echo esc_html(fly_backup_format_bytes($newest_backup->backup_size)); ?>)
                    </div>
                </div>
            <?php else: ?>
                <div class="fly-backup-stat">
                    <div class="fly-backup-stat-label"><?php esc_html_e('Last Backup', 'fly-backup'); ?></div>
                    <div class="fly-backup-stat-value"><?php esc_html_e('No backups yet', 'fly-backup'); ?></div>
                </div>
            <?php endif; ?>
            
            <?php if ($next_scheduled): ?>
                <div class="fly-backup-stat">
                    <div class="fly-backup-stat-label"><?php esc_html_e('Next Scheduled Backup', 'fly-backup'); ?></div>
                    <div class="fly-backup-stat-value">
                        <?php echo esc_html($next_scheduled->schedule_name); ?> -
                        <?php echo esc_html(gmdate('M j, Y g:i A', strtotime($next_scheduled->next_run))); ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="fly-backup-stat">
                    <div class="fly-backup-stat-label"><?php esc_html_e('Next Scheduled Backup', 'fly-backup'); ?></div>
                    <div class="fly-backup-stat-value"><?php esc_html_e('No schedules configured', 'fly-backup'); ?></div>
                </div>
            <?php endif; ?>
            
            <div class="fly-backup-stat">
                <div class="fly-backup-stat-label"><?php esc_html_e('Total Backups', 'fly-backup'); ?></div>
                <div class="fly-backup-stat-value">
                    <?php echo esc_html($storage['backup_count']); ?> 
                    (<?php echo esc_html($storage['total_size_formatted']); ?>)
                </div>
            </div>
            
            <div class="fly-backup-actions">
                <a href="<?php echo esc_url(admin_url('admin.php?page=fly-backup')); ?>" class="button button-primary">
                    <?php esc_html_e('Manage Backups', 'fly-backup'); ?>
                </a>
            </div>
        </div>
        <?php
    }
}
