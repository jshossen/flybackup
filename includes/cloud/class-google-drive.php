<?php
/**
 * Google Drive Storage Class (Skeleton)
 *
 * @package Fly_Backup
 */

if (!defined('ABSPATH')) {
    exit;
}

class Fly_Backup_Google_Drive extends Fly_Backup_Cloud_Base {
    
    private $api_base = 'https://www.googleapis.com/drive/v3';
    private $upload_base = 'https://www.googleapis.com/upload/drive/v3';
    private $access_token;
    
    public function __construct() {
        $this->provider_name = 'Google Drive';
    }
    
    public function connect($credentials) {
        if (empty($credentials['access_token'])) {
            return false;
        }
        
        $this->credentials = $credentials;
        $this->access_token = $credentials['access_token'];
        
        // Verify token is valid
        $response = $this->api_request('/about?fields=user');
        $this->is_connected = !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
        
        return $this->is_connected;
    }
    
    private function api_request($endpoint, $method = 'GET', $body = null, $headers = array()) {
        $url = $this->api_base . $endpoint;
        
        $default_headers = array(
            'Authorization' => 'Bearer ' . $this->access_token,
            'Content-Type' => 'application/json'
        );
        
        $args = array(
            'method' => $method,
            'headers' => array_merge($default_headers, $headers),
            'timeout' => 60
        );
        
        if ($body) {
            $args['body'] = $body;
        }
        
        return wp_remote_request($url, $args);
    }
    
    public function upload($file_path, $remote_path) {
        if (!$this->is_connected) {
            return false;
        }
        
        $file_name = basename($file_path);
        $folder_id = $this->get_or_create_folder('Fly_Backup');
        
        $boundary = uniqid();
        $metadata = json_encode(array(
            'name' => $file_name,
            'parents' => array($folder_id)
        ));
        
        $body = "--{$boundary}\r\n";
        $body .= "Content-Type: application/json; charset=UTF-8\r\n\r\n";
        $body .= $metadata . "\r\n";
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: application/octet-stream\r\n\r\n";
        $body .= file_get_contents($file_path) . "\r\n";
        $body .= "--{$boundary}--";
        
        $response = wp_remote_post($this->upload_base . '/files?uploadType=multipart', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->access_token,
                'Content-Type' => 'multipart/related; boundary=' . $boundary
            ),
            'body' => $body,
            'timeout' => 120
        ));
        
        if (is_wp_error($response)) {
            $this->log('Upload failed: ' . $response->get_error_message(), 'error');
            return false;
        }
        
        $code = wp_remote_retrieve_response_code($response);
        if ($code === 200 || $code === 201) {
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
        
        $file_id = $remote_path;
        $url = $this->api_base . '/files/' . $file_id . '?alt=media';
        
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->access_token
            ),
            'timeout' => 120,
            'stream' => true,
            'filename' => $local_path
        ));
        
        if (is_wp_error($response)) {
            $this->log('Download failed: ' . $response->get_error_message(), 'error');
            return false;
        }
        
        return wp_remote_retrieve_response_code($response) === 200;
    }
    
    public function delete($remote_path) {
        if (!$this->is_connected) {
            return false;
        }
        
        $file_id = $remote_path;
        $response = $this->api_request('/files/' . $file_id, 'DELETE');
        
        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 204;
    }
    
    public function list_files($remote_path = '/') {
        if (!$this->is_connected) {
            return array();
        }
        
        $folder_id = $remote_path === '/' ? 'root' : $remote_path;
        $query = "'" . $folder_id . "' in parents and trashed=false";
        
        $response = $this->api_request('/files?q=' . urlencode($query));
        
        if (is_wp_error($response)) {
            return array();
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        $files = array();
        
        if (!empty($data['files'])) {
            foreach ($data['files'] as $file) {
                $files[] = array(
                    'id' => $file['id'],
                    'name' => $file['name'],
                    'size' => $file['size'] ?? 0,
                    'mimeType' => $file['mimeType']
                );
            }
        }
        
        return $files;
    }
    
    public function get_quota() {
        if (!$this->is_connected) {
            return array('used' => 0, 'total' => 0);
        }
        
        $response = $this->api_request('/about?fields=storageQuota');
        
        if (is_wp_error($response)) {
            return array('used' => 0, 'total' => 0);
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        return array(
            'used' => $data['storageQuota']['usage'] ?? 0,
            'total' => $data['storageQuota']['limit'] ?? 0
        );
    }
    
    private function get_or_create_folder($folder_name) {
        // Search for existing folder
        $query = "mimeType='application/vnd.google-apps.folder' and name='" . $folder_name . "' and trashed=false";
        $response = $this->api_request('/files?q=' . urlencode($query));
        
        if (!is_wp_error($response)) {
            $data = json_decode(wp_remote_retrieve_body($response), true);
            if (!empty($data['files'])) {
                return $data['files'][0]['id'];
            }
        }
        
        // Create folder
        $body = json_encode(array(
            'name' => $folder_name,
            'mimeType' => 'application/vnd.google-apps.folder'
        ));
        
        $response = $this->api_request('/files', 'POST', $body);
        
        if (!is_wp_error($response)) {
            $data = json_decode(wp_remote_retrieve_body($response), true);
            return $data['id'] ?? null;
        }
        
        return null;
    }
    
    public function refresh_token($client_id, $client_secret, $refresh_token) {
        $response = wp_remote_post('https://oauth2.googleapis.com/token', array(
            'body' => array(
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'refresh_token' => $refresh_token,
                'grant_type' => 'refresh_token'
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
