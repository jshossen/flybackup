<?php
/**
 * Dashboard Widget Class
 *
 * @package Auto_Backup
 */

if (!defined('ABSPATH')) {
    exit;
}

class Auto_Backup_Dashboard_Widget {
    
    public function __construct() {
        add_action('wp_dashboard_setup', array($this, 'register_widget'));
    }
    
    public function register_widget() {
        wp_add_dashboard_widget(
            'auto_backup_widget',
            __('Auto Backup Status', 'auto-backup'),
            array($this, 'render_widget')
        );
    }
    
    public function render_widget() {
        $database = new Auto_Backup_Database();
        $retention_manager = new Auto_Backup_Retention_Manager();
        $scheduler = new Auto_Backup_Scheduler();
        
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
        <div class="auto-backup-dashboard-widget">
            <style>
                .auto-backup-dashboard-widget {
                    padding: 10px 0;
                }
                .auto-backup-stat {
                    margin-bottom: 15px;
                    padding-bottom: 15px;
                    border-bottom: 1px solid #f0f0f0;
                }
                .auto-backup-stat:last-child {
                    border-bottom: none;
                    margin-bottom: 0;
                }
                .auto-backup-stat-label {
                    font-weight: 600;
                    color: #23282d;
                    margin-bottom: 5px;
                }
                .auto-backup-stat-value {
                    color: #72aee6;
                    font-size: 14px;
                }
                .auto-backup-actions {
                    margin-top: 15px;
                    padding-top: 15px;
                    border-top: 1px solid #f0f0f0;
                }
            </style>
            
            <?php if ($newest_backup): ?>
                <div class="auto-backup-stat">
                    <div class="auto-backup-stat-label"><?php _e('Last Backup', 'auto-backup'); ?></div>
                    <div class="auto-backup-stat-value">
                        <?php echo esc_html(auto_backup_time_ago($newest_backup->created_at)); ?>
                        (<?php echo esc_html(auto_backup_format_bytes($newest_backup->backup_size)); ?>)
                    </div>
                </div>
            <?php else: ?>
                <div class="auto-backup-stat">
                    <div class="auto-backup-stat-label"><?php _e('Last Backup', 'auto-backup'); ?></div>
                    <div class="auto-backup-stat-value"><?php _e('No backups yet', 'auto-backup'); ?></div>
                </div>
            <?php endif; ?>
            
            <?php if ($next_scheduled): ?>
                <div class="auto-backup-stat">
                    <div class="auto-backup-stat-label"><?php _e('Next Scheduled Backup', 'auto-backup'); ?></div>
                    <div class="auto-backup-stat-value">
                        <?php echo esc_html($next_scheduled->schedule_name); ?> - 
                        <?php echo esc_html(date('M j, Y g:i A', strtotime($next_scheduled->next_run))); ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="auto-backup-stat">
                    <div class="auto-backup-stat-label"><?php _e('Next Scheduled Backup', 'auto-backup'); ?></div>
                    <div class="auto-backup-stat-value"><?php _e('No schedules configured', 'auto-backup'); ?></div>
                </div>
            <?php endif; ?>
            
            <div class="auto-backup-stat">
                <div class="auto-backup-stat-label"><?php _e('Total Backups', 'auto-backup'); ?></div>
                <div class="auto-backup-stat-value">
                    <?php echo esc_html($storage['backup_count']); ?> 
                    (<?php echo esc_html($storage['total_size_formatted']); ?>)
                </div>
            </div>
            
            <div class="auto-backup-actions">
                <a href="<?php echo admin_url('admin.php?page=auto-backup'); ?>" class="button button-primary">
                    <?php _e('Manage Backups', 'auto-backup'); ?>
                </a>
            </div>
        </div>
        <?php
    }
}
