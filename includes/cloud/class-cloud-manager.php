<?php
/**
 * Cloud Manager Class
 *
 * Manages all cloud storage providers and handles credential encryption
 *
 * @package Fly_Backup
 */

if (!defined('ABSPATH')) {
    exit;
}

class Fly_Backup_Cloud_Manager {
    
    private $database;
    private $providers = array();
    
    public function __construct() {
        $this->database = new Fly_Backup_Database();
        $this->load_providers();
    }
    
    private function load_providers() {
        $provider_map = array(
            'google_drive' => 'Fly_Backup_Google_Drive',
            'dropbox' => 'Fly_Backup_Dropbox',
            'amazon_s3' => 'Fly_Backup_Amazon_S3'
        );
        
        foreach ($provider_map as $provider => $class_name) {
            $class_file = FLY_BACKUP_PLUGIN_DIR . 'includes/cloud/class-' . str_replace('_', '-', $provider) . '.php';
            if (file_exists($class_file)) {
                require_once $class_file;
                if (class_exists($class_name)) {
                    $this->providers[$provider] = new $class_name();
                }
            }
        }
    }
    
    private function encrypt_credentials($credentials) {
        $key = wp_hash('fly_backup_cloud_key');
        $json = json_encode($credentials);
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('AES-256-CBC'));
        $encrypted = openssl_encrypt($json, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($encrypted . '::' . base64_encode($iv));
    }
    
    private function decrypt_credentials($encrypted_data) {
        $key = wp_hash('fly_backup_cloud_key');
        $parts = explode('::', base64_decode($encrypted_data), 2);
        if (count($parts) !== 2) {
            return array();
        }
        $encrypted = $parts[0];
        $iv = base64_decode($parts[1]);
        $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
        return json_decode($decrypted, true);
    }
    
    public function get_provider($provider_name) {
        if (isset($this->providers[$provider_name])) {
            $credentials = $this->database->get_cloud_credentials($provider_name);
            if ($credentials && $credentials->is_connected) {
                $this->providers[$provider_name]->connect($credentials->credentials);
            }
            return $this->providers[$provider_name];
        }
        return null;
    }
    
    public function get_all_providers_status() {
        $status = array();
        
        foreach ($this->providers as $name => $provider) {
            $credentials = $this->database->get_cloud_credentials($name);
            $status[$name] = array(
                'name' => $provider->get_provider_name(),
                'connected' => $credentials ? (bool) $credentials->is_connected : false,
                'has_credentials' => !empty($credentials)
            );
        }
        
        return $status;
    }
    
    public function connect_provider($provider_name, $credentials, $settings = array()) {
        if (!isset($this->providers[$provider_name])) {
            return array('success' => false, 'message' => 'Provider not found');
        }
        
        $provider = $this->providers[$provider_name];
        
        if ($provider->connect($credentials)) {
            $this->database->save_cloud_credentials($provider_name, $credentials, $settings, true);
            return array('success' => true, 'message' => 'Connected successfully');
        }
        
        return array('success' => false, 'message' => 'Connection failed');
    }
    
    public function disconnect_provider($provider_name) {
        $this->database->delete_cloud_credentials($provider_name);
        return array('success' => true, 'message' => 'Disconnected successfully');
    }
    
    public function upload_backup($provider_name, $file_path, $remote_path = '') {
        $provider = $this->get_provider($provider_name);
        
        if (!$provider) {
            return array('success' => false, 'message' => 'Provider not found or not connected');
        }
        
        if (!$provider->is_connected()) {
            return array('success' => false, 'message' => 'Provider not connected');
        }
        
        $remote_path = $remote_path ?: basename($file_path);
        
        if ($provider->upload($file_path, $remote_path)) {
            return array('success' => true, 'message' => 'Upload successful');
        }
        
        return array('success' => false, 'message' => 'Upload failed');
    }
    
    public function download_backup($provider_name, $remote_path, $local_path) {
        $provider = $this->get_provider($provider_name);
        
        if (!$provider || !$provider->is_connected()) {
            return array('success' => false, 'message' => 'Provider not connected');
        }
        
        if ($provider->download($remote_path, $local_path)) {
            return array('success' => true, 'message' => 'Download successful');
        }
        
        return array('success' => false, 'message' => 'Download failed');
    }
    
    public function download_backup_for_comparison($backup_id) {
        global $wpdb;
        
        $backup = $this->database->get_backup($backup_id);
        if (!$backup || empty($backup->cloud_storage)) {
            return null;
        }
        
        $cloud_data = json_decode($backup->cloud_storage, true);
        if (!$cloud_data || empty($cloud_data['provider']) || empty($cloud_data['remote_path'])) {
            return null;
        }
        
        $temp_dir = wp_upload_dir()['basedir'] . '/flybackup/temp/';
        if (!file_exists($temp_dir)) {
            wp_mkdir_p($temp_dir);
        }
        
        $temp_path = $temp_dir . basename($backup->backup_name);
        
        $result = $this->download_backup($cloud_data['provider'], $cloud_data['remote_path'], $temp_path);
        
        if ($result['success']) {
            return $temp_path;
        }
        
        return null;
    }
    
    public function get_available_providers() {
        $available = array();
        
        foreach ($this->providers as $name => $provider) {
            $available[$name] = $provider->get_provider_name();
        }
        
        return $available;
    }
}
