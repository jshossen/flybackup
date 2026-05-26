<?php
/**
 * Plugin Name: FlyBackup - Auto backup manager
 * Plugin URI: https://github.com/jshossen/flybackup
 * Description: Fast, reliable WordPress backups that just work. One-click automated backups without technical complexity.
 * Version: 1.0.0
 * Author: jshossen
 * Author URI: https://jshossen.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: flybackup
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

define('FLY_BACKUP_VERSION', '1.0.0');
define('FLY_BACKUP_PLUGIN_FILE', __FILE__);
define('FLY_BACKUP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FLY_BACKUP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('FLY_BACKUP_PLUGIN_BASENAME', plugin_basename(__FILE__));

$fly_backup_upload_dir = wp_upload_dir();
define('FLY_BACKUP_BACKUP_DIR', $fly_backup_upload_dir['basedir'] . '/flybackup/');

require_once FLY_BACKUP_PLUGIN_DIR . 'includes/class-flybackup.php';

function fly_backup_main_function() {
    return Fly_Backup::instance();
}

fly_backup_main_function();

register_activation_hook(__FILE__, array('Fly_Backup', 'activate'));
register_deactivation_hook(__FILE__, array('Fly_Backup', 'deactivate'));
