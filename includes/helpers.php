<?php
/**
 * Helper Functions
 *
 * @package Fly_Backup
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('wp_is_writable')) {
    function wp_is_writable($path) {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable -- Polyfill for WP < 6.5
        return is_writable($path);
    }
}

if (!function_exists('wp_rmdir')) {
    function wp_rmdir($dir) {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Polyfill for WP < 6.3
        return rmdir($dir);
    }
}

function flybackup_format_bytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}

function flybackup_get_settings() {
    $defaults = array(
        'email_notifications' => false,
        'notification_email' => get_option('admin_email'),
        'backup_items' => array(
            'database' => true,
            'uploads' => true,
            'plugins' => true,
            'themes' => true,
            'wp_config' => true
        )
    );
    
    $settings = get_option('flybackup_settings', array());
    
    return wp_parse_args($settings, $defaults);
}

function flybackup_update_settings($settings) {
    return update_option('flybackup_settings', $settings);
}

function flybackup_get_backup_dir() {
    return FLYBACKUP_BACKUP_DIR;
}

function flybackup_generate_backup_filename($type = 'full') {
    $hash = substr(md5(uniqid(wp_rand(), true)), 0, 8);
    return 'backup_' . $type . '_' . gmdate('Y-m-d_H-i-s') . '_' . $hash . '.zip';
}

function flybackup_get_site_size() {
    $size = 0;
    
    $upload_dir = wp_upload_dir();
    $paths = array(
        $upload_dir['basedir'],
        WP_PLUGIN_DIR,
        get_theme_root()
    );
    
    foreach ($paths as $path) {
        if (is_dir($path)) {
            $size += flybackup_get_directory_size($path);
        }
    }
    
    return $size;
}

function flybackup_get_directory_size($path) {
    $size = 0;
    
    if (!is_dir($path)) {
        return 0;
    }
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $size += $file->getSize();
        }
    }
    
    return $size;
}

function flybackup_get_database_size() {
    global $wpdb;
    
    $size = 0;
    $tables = $wpdb->get_results('SHOW TABLE STATUS', ARRAY_A);
    
    foreach ($tables as $table) {
        $size += $table['Data_length'] + $table['Index_length'];
    }
    
    return $size;
}

function flybackup_get_available_disk_space() {
    $backup_dir = flybackup_get_backup_dir();
    
    if (!file_exists($backup_dir)) {
        $upload_dir = wp_upload_dir();
        $backup_dir = $upload_dir['basedir'];
    }
    
    return @disk_free_space($backup_dir);
}

function flybackup_is_writable() {
    $backup_dir = flybackup_get_backup_dir();
    
    if (!file_exists($backup_dir)) {
        $upload_dir = wp_upload_dir();
        return wp_is_writable($upload_dir['basedir']);
    }
    
    return wp_is_writable($backup_dir);
}

function flybackup_get_php_memory_limit() {
    $memory_limit = ini_get('memory_limit');
    
    if (preg_match('/^(\d+)(.)$/', $memory_limit, $matches)) {
        if ($matches[2] == 'M') {
            return $matches[1] * 1024 * 1024;
        } elseif ($matches[2] == 'K') {
            return $matches[1] * 1024;
        } elseif ($matches[2] == 'G') {
            return $matches[1] * 1024 * 1024 * 1024;
        }
    }
    
    return (int) $memory_limit;
}

function flybackup_get_max_execution_time() {
    return (int) ini_get('max_execution_time');
}

function flybackup_send_notification($subject, $message) {
    $settings = flybackup_get_settings();
    
    if (!$settings['email_notifications']) {
        return false;
    }
    
    $to = $settings['notification_email'];
    $headers = array('Content-Type: text/html; charset=UTF-8');
    
    return wp_mail($to, $subject, $message, $headers);
}

function flybackup_get_excluded_paths() {
    return apply_filters('flybackup_excluded_paths', array(
        'cache',
        'tmp',
        'temp',
        'logs',
        '.git',
        '.svn',
        'node_modules',
        'flybackups'
    ));
}

function flybackup_should_exclude_file($file_path) {
    $excluded_paths = flybackup_get_excluded_paths();
    
    foreach ($excluded_paths as $excluded) {
        if (strpos($file_path, '/' . $excluded . '/') !== false || 
            strpos($file_path, '/' . $excluded) === strlen($file_path) - strlen('/' . $excluded)) {
            return true;
        }
    }
    
    return false;
}

function flybackup_time_ago($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return $diff . ' seconds ago';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' minutes ago';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' hours ago';
    } elseif ($diff < 604800) {
        return floor($diff / 86400) . ' days ago';
    } else {
        return gmdate('M j, Y', $timestamp);
    }
}

function flybackup_format_next_run($datetime) {
    if (empty($datetime)) {
        return 'Not scheduled';
    }
    
    $timestamp = strtotime($datetime);
    $diff = $timestamp - time();
    
    // If in the past, show as overdue
    if ($diff < 0) {
        return 'Overdue';
    }
    
    // Format based on time until next run
    if ($diff < 60) {
        return 'In ' . $diff . ' seconds';
    } elseif ($diff < 3600) {
        return 'In ' . floor($diff / 60) . ' minutes';
    } elseif ($diff < 86400) {
        return 'In ' . floor($diff / 3600) . ' hours';
    } elseif ($diff < 172800) { // Less than 2 days
        return 'Tomorrow at ' . gmdate('g:i A', $timestamp);
    } elseif ($diff < 604800) { // Less than 7 days
        return gmdate('l \a\t g:i A', $timestamp); // e.g., "Monday at 2:00 PM"
    } else {
        return gmdate('M j, Y \a\t g:i A', $timestamp); // e.g., "May 15, 2026 at 2:00 PM"
    }
}

function flybackup_verify_nonce($nonce, $action = 'flybackup_nonce') {
    return wp_verify_nonce($nonce, $action);
}

function flybackup_current_user_can() {
    return current_user_can('manage_options');
}
