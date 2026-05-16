<?php
/**
 * Plugin Name: Auto Backup
 * Plugin URI: https://jshossen.com
 * Description: Automatic WordPress backups that just work. One-click automated backups without technical complexity.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://jshossen.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: auto-backup
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

define('AUTO_BACKUP_VERSION', '1.0.0');
define('AUTO_BACKUP_PLUGIN_FILE', __FILE__);
define('AUTO_BACKUP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AUTO_BACKUP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AUTO_BACKUP_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('AUTO_BACKUP_BACKUP_DIR', WP_CONTENT_DIR . '/auto-backups/');

require_once AUTO_BACKUP_PLUGIN_DIR . 'includes/class-auto-backup.php';

function auto_backup() {
    return Auto_Backup::instance();
}

auto_backup();

register_activation_hook(__FILE__, array('Auto_Backup', 'activate'));
register_deactivation_hook(__FILE__, array('Auto_Backup', 'deactivate'));
