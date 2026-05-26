<?php
/**
 * Uninstall Fly Backup
 *
 * @package Fly_Backup
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

if (!function_exists('wp_rmdir')) {
    function wp_rmdir($dir) {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Polyfill for WP < 6.3
        return rmdir($dir);
    }
}

global $wpdb;

$table_backups = $wpdb->prefix . 'fly_backup_backups';
$table_logs = $wpdb->prefix . 'fly_backup_logs';
$table_schedules = $wpdb->prefix . 'fly_backup_schedules';

$wpdb->query("DROP TABLE IF EXISTS {$table_backups}");
$wpdb->query("DROP TABLE IF EXISTS {$table_logs}");
$wpdb->query("DROP TABLE IF EXISTS {$table_schedules}");

delete_option('fly_backup_settings');
delete_option('fly_backup_version');
delete_option('fly_backup_retention_count');
delete_option('fly_backup_db_version');

$upload_dir = wp_upload_dir();
$backup_dir = $upload_dir['basedir'] . '/flybackup/';

if (is_dir($backup_dir)) {
    $files = glob($backup_dir . '*');
    foreach ($files as $file) {
        if (is_file($file)) {
            wp_delete_file($file);
        }
    }
    wp_rmdir($backup_dir);
}

wp_clear_scheduled_hook('fly_backup_scheduled_backup');
wp_clear_scheduled_hook('fly_backup_cleanup_old_backups');
