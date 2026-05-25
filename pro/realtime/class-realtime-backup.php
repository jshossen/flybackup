<?php
/**
 * Real-time Backup Class (Skeleton)
 *
 * @package Fly_Backup
 */

if (!defined('ABSPATH')) {
    exit;
}

class Fly_Backup_Realtime_Backup {
    
    public function __construct() {
        add_action('save_post', array($this, 'on_post_save'));
        add_action('activated_plugin', array($this, 'on_plugin_activated'));
        add_action('woocommerce_new_order', array($this, 'on_new_order'));
    }
    
    public function on_post_save($post_id) {
        return false;
    }
    
    public function on_plugin_activated($plugin) {
        return false;
    }
    
    public function on_new_order($order_id) {
        return false;
    }
}
