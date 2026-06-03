<?php
/**
 * Dropbox Storage Class (Skeleton)
 *
 * @package Fly_Backup
 */

if (!defined('ABSPATH')) {
    exit;
}

class Fly_Backup_Dropbox extends Fly_Backup_Cloud_Base {
    
    private $api_base = 'https://api.dropboxapi.com/2';
    private $content_base = 'https://content.dropboxapi.com/2';
    private $access_token;
    
    public function __construct() {
        $this->provider_name = 'Dropbox';
    }
    
    public function connect($credentials) {
        if (empty($credentials['access_token'])) {
            return false;
        }
        
        $this->credentials = $credentials;
        $this->access_token = $credentials['access_token'];
        
        // Verify token is valid
        $response = $this->api_request('/users/get_current_account');
        $this->is_connected = !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
        
        return $this->is_connected;
    }
    
    private function api_request($endpoint, $method = 'POST', $body = null) {
        $url = $this->api_base . $endpoint;
        
        $args = array(
            'method' => $method,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->access_token,
                'Content-Type' => 'application/json'
            ),
            'timeout' => 60
        );
        
        if ($body) {
            $args['body'] = json_encode($body);
        }
        
        return wp_remote_request($url, $args);
    }
    
    public function upload($file_path, $remote_path) {
        if (!$this->is_connected) {
            return false;
        }
        
        $file_name = basename($file_path);
        $dropbox_path = '/Fly_Backup/' . $file_name;
        
        $response = wp_remote_post($this->content_base . '/files/upload', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->access_token,
                'Content-Type' => 'application/octet-stream',
                'Dropbox-API-Arg' => json_encode(array(
                    'path' => $dropbox_path,
                    'mode' => 'add',
                    'autorename' => true,
                    'mute' => false
                ))
            ),
            'body' => file_get_contents($file_path),
            'timeout' => 120
        ));
        
        if (is_wp_error($response)) {
            $this->log('Upload failed: ' . $response->get_error_message(), 'error');
            return false;
        }
        
        $code = wp_remote_retrieve_response_code($response);
        if ($code === 200) {
            $data = json_decode(wp_remote_retrieve_body($response), true);
            $this->log('Upload successful: ' . $data['id'], 'success');
            return $data['id'];
        }
        
        $this->log('Upload failed with code ' . $code, 'error');
        return false;
    }
    
    public function download($remote_path, $local_path) {
        if (!$this->is_connected) {
            return false;
        }
        
        $response = wp_remote_post($this->content_base . '/files/download', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->access_token,
                'Dropbox-API-Arg' => json_encode(array(
                    'path' => $remote_path
                ))
            ),
            'timeout' => 120
        ));
        
        if (is_wp_error($response)) {
            $this->log('Download failed: ' . $response->get_error_message(), 'error');
            return false;
        }
        
        $code = wp_remote_retrieve_response_code($response);
        if ($code === 200) {
            $body = wp_remote_retrieve_body($response);
            return file_put_contents($local_path, $body) !== false;
        }
        
        return false;
    }
    
    public function delete($remote_path) {
        if (!$this->is_connected) {
            return false;
        }
        
        $response = $this->api_request('/files/delete_v2', 'POST', array(
            'path' => $remote_path
        ));
        
        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }
    
    public function list_files($remote_path = '/') {
        if (!$this->is_connected) {
            return array();
        }
        
        $path = $remote_path === '/' ? '/Fly_Backup' : $remote_path;
        
        $response = $this->api_request('/files/list_folder', 'POST', array(
            'path' => $path,
            'recursive' => false,
            'include_deleted' => false
        ));
        
        if (is_wp_error($response)) {
            return array();
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        $files = array();
        
        if (!empty($data['entries'])) {
            foreach ($data['entries'] as $entry) {
                if ($entry['.tag'] === 'file') {
                    $files[] = array(
                        'id' => $entry['id'],
                        'name' => $entry['name'],
                        'size' => $entry['size'] ?? 0,
                        'path' => $entry['path_display']
                    );
                }
            }
        }
        
        return $files;
    }
    
    public function get_quota() {
        if (!$this->is_connected) {
            return array('used' => 0, 'total' => 0);
        }
        
        $response = $this->api_request('/users/get_space_usage');
        
        if (is_wp_error($response)) {
            return array('used' => 0, 'total' => 0);
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        return array(
            'used' => $data['used'] ?? 0,
            'total' => $data['allocation']['allocated'] ?? 0
        );
    }
    
    public function refresh_token($app_key, $app_secret, $refresh_token) {
        $response = wp_remote_post('https://api.dropboxapi.com/oauth2/token', array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($app_key . ':' . $app_secret),
                'Content-Type' => 'application/x-www-form-urlencoded'
            ),
            'body' => array(
                'grant_type' => 'refresh_token',
                'refresh_token' => $refresh_token
            )
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!empty($data['access_token'])) {
            $this->access_token = $data['access_token'];
            return $data;
        }
        
        return false;
    }
}
