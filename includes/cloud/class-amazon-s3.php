<?php
/**
 * Amazon S3 Storage Class (Skeleton)
 *
 * @package Fly_Backup
 */

if (!defined('ABSPATH')) {
    exit;
}

class Fly_Backup_Amazon_S3 extends Fly_Backup_Cloud_Base {
    
    private $access_key;
    private $secret_key;
    private $region;
    private $bucket;
    private $endpoint;
    
    public function __construct() {
        $this->provider_name = 'Amazon S3';
    }
    
    public function connect($credentials) {
        if (empty($credentials['access_key']) || empty($credentials['secret_key']) || empty($credentials['region']) || empty($credentials['bucket'])) {
            return false;
        }
        
        $this->credentials = $credentials;
        $this->access_key = $credentials['access_key'];
        $this->secret_key = $credentials['secret_key'];
        $this->region = $credentials['region'];
        $this->bucket = $credentials['bucket'];
        $this->endpoint = $credentials['endpoint'] ?? 's3.' . $this->region . '.amazonaws.com';
        
        // Test connection by listing bucket
        $response = $this->s3_request('HEAD', '/');
        $this->is_connected = !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
        
        return $this->is_connected;
    }
    
    private function s3_request($method, $path, $body = null, $headers = array()) {
        $host = $this->bucket . '.' . $this->endpoint;
        $url = 'https://' . $host . $path;
        
        $date = gmdate('Ymd\THis\Z');
        $date_short = substr($date, 0, 8);
        
        $headers['Host'] = $host;
        $headers['x-amz-date'] = $date;
        $headers['x-amz-content-sha256'] = hash('sha256', $body ?? '');
        
        $canonical_request = $this->create_canonical_request($method, $path, $headers, $body);
        $string_to_sign = $this->create_string_to_sign($date, $date_short, $canonical_request);
        $signature = $this->calculate_signature($date_short, $string_to_sign);
        
        $headers['Authorization'] = 'AWS4-HMAC-SHA256 Credential=' . $this->access_key . '/' . $date_short . '/' . $this->region . '/s3/aws4_request, SignedHeaders=' . $this->get_signed_headers($headers) . ', Signature=' . $signature;
        
        $args = array(
            'method' => $method,
            'headers' => $headers,
            'timeout' => 120
        );
        
        if ($body !== null) {
            $args['body'] = $body;
        }
        
        return wp_remote_request($url, $args);
    }
    
    private function create_canonical_request($method, $path, $headers, $body) {
        $canonical_headers = '';
        $signed_headers = '';
        
        $sorted_headers = $headers;
        ksort($sorted_headers);
        
        foreach ($sorted_headers as $key => $value) {
            $canonical_headers .= strtolower($key) . ':' . trim($value) . "\n";
            $signed_headers .= strtolower($key) . ';';
        }
        
        $signed_headers = rtrim($signed_headers, ';');
        
        return implode("\n", array(
            $method,
            $path,
            '',
            $canonical_headers,
            $signed_headers,
            hash('sha256', $body ?? '')
        ));
    }
    
    private function create_string_to_sign($date, $date_short, $canonical_request) {
        return implode("\n", array(
            'AWS4-HMAC-SHA256',
            $date,
            $date_short . '/' . $this->region . '/s3/aws4_request',
            hash('sha256', $canonical_request)
        ));
    }
    
    private function calculate_signature($date_short, $string_to_sign) {
        $k_date = hash_hmac('sha256', $date_short, 'AWS4' . $this->secret_key, true);
        $k_region = hash_hmac('sha256', $this->region, $k_date, true);
        $k_service = hash_hmac('sha256', 's3', $k_region, true);
        $k_signing = hash_hmac('sha256', 'aws4_request', $k_service, true);
        
        return hash_hmac('sha256', $string_to_sign, $k_signing);
    }
    
    private function get_signed_headers($headers) {
        $signed = array();
        foreach ($headers as $key => $value) {
            $signed[] = strtolower($key);
        }
        sort($signed);
        return implode(';', $signed);
    }
    
    public function upload($file_path, $remote_path) {
        if (!$this->is_connected) {
            return false;
        }
        
        $file_name = basename($file_path);
        $key = 'fly-backup/' . $file_name;
        
        $file_content = file_get_contents($file_path);
        
        $headers = array(
            'Content-Type' => 'application/octet-stream',
            'Content-Length' => strlen($file_content)
        );
        
        $response = $this->s3_request('PUT', '/' . $key, $file_content, $headers);
        
        if (is_wp_error($response)) {
            $this->log('Upload failed: ' . $response->get_error_message(), 'error');
            return false;
        }
        
        $code = wp_remote_retrieve_response_code($response);
        if ($code === 200) {
            $this->log('Upload successful: ' . $key, 'success');
            return $key;
        }
        
        $this->log('Upload failed with code ' . $code, 'error');
        return false;
    }
    
    public function download($remote_path, $local_path) {
        if (!$this->is_connected) {
            return false;
        }
        
        $response = $this->s3_request('GET', '/' . $remote_path);
        
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
        
        $response = $this->s3_request('DELETE', '/' . $remote_path);
        
        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 204;
    }
    
    public function list_files($remote_path = '/') {
        if (!$this->is_connected) {
            return array();
        }
        
        $prefix = 'fly-backup/';
        $response = $this->s3_request('GET', '/?prefix=' . urlencode($prefix) . '&max-keys=100');
        
        if (is_wp_error($response)) {
            return array();
        }
        
        $body = wp_remote_retrieve_body($response);
        $xml = simplexml_load_string($body);
        
        $files = array();
        if ($xml && $xml->Contents) {
            foreach ($xml->Contents as $content) {
                $files[] = array(
                    'id' => (string) $content->Key,
                    'name' => basename((string) $content->Key),
                    'size' => (int) $content->Size,
                    'path' => (string) $content->Key
                );
            }
        }
        
        return $files;
    }
    
    public function get_quota() {
        // S3 doesn't provide a simple quota API
        return array('used' => 0, 'total' => 0);
    }
}
