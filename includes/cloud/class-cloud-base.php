<?php
/**
 * Cloud Storage Base Class
 *
 * @package Fly_Backup
 */

if (!defined('ABSPATH')) {
    exit;
}

abstract class Fly_Backup_Cloud_Base {
    
    protected $provider_name;
    protected $credentials;
    protected $is_connected = false;
    
    abstract public function connect($credentials);
    abstract public function upload($file_path, $remote_path);
    abstract public function download($remote_path, $local_path);
    abstract public function delete($remote_path);
    abstract public function list_files($remote_path = '/');
    abstract public function get_quota();
    
    public function is_connected() {
        return $this->is_connected;
    }
    
    public function get_provider_name() {
        return $this->provider_name;
    }
    
    protected function log($message, $type = 'info') {
        $logger = new Fly_Backup_Logger();
        $logger->log($message, $type, null, array('provider' => $this->provider_name));
    }
}
