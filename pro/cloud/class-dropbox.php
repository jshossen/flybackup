<?php
/**
 * Dropbox Storage Class (Skeleton)
 *
 * @package Auto_Backup
 */

if (!defined('ABSPATH')) {
    exit;
}

class Auto_Backup_Dropbox extends Auto_Backup_Cloud_Base {
    
    public function __construct() {
        $this->provider_name = 'Dropbox';
    }
    
    public function connect($credentials) {
        return false;
    }
    
    public function upload($file_path, $remote_path) {
        return false;
    }
    
    public function download($remote_path, $local_path) {
        return false;
    }
    
    public function delete($remote_path) {
        return false;
    }
    
    public function list_files($remote_path = '/') {
        return array();
    }
    
    public function get_quota() {
        return array('used' => 0, 'total' => 0);
    }
}
