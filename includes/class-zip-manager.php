<?php
/**
 * ZIP Manager Class
 *
 * @package Fly_Backup
 */

if (!defined('ABSPATH')) {
    exit;
}

class Fly_Backup_Zip_Manager {
    
    private $zip;
    private $zip_path;
    
    public function __construct($zip_path = null) {
        if ($zip_path) {
            $this->zip_path = $zip_path;
        }
    }
    
    public function create($zip_path) {
        $this->zip_path = $zip_path;
        $this->zip = new ZipArchive();
        
        $result = $this->zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        
        if ($result !== true) {
            throw new Exception(esc_html('Failed to create ZIP archive: ' . $zip_path));
        }
        
        return true;
    }
    
    public function open($zip_path) {
        $this->zip_path = $zip_path;
        $this->zip = new ZipArchive();
        
        $result = $this->zip->open($zip_path);
        
        if ($result !== true) {
            throw new Exception(esc_html('Failed to open ZIP archive: ' . $zip_path));
        }
        
        return true;
    }
    
    public function add_file($file_path, $local_name = null) {
        if (!file_exists($file_path)) {
            return false;
        }
        
        if (!$local_name) {
            $local_name = basename($file_path);
        }
        
        return $this->zip->addFile($file_path, $local_name);
    }
    
    public function add_from_string($local_name, $contents) {
        return $this->zip->addFromString($local_name, $contents);
    }
    
    public function add_directory($dir_path, $local_dir = '', $exclude_patterns = array()) {
        if (!is_dir($dir_path)) {
            return false;
        }
        
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir_path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($files as $file) {
            $file_path = $file->getRealPath();
            $relative_path = substr($file_path, strlen($dir_path) + 1);
            
            if ($this->should_exclude($file_path, $exclude_patterns)) {
                continue;
            }
            
            $local_path = $local_dir ? $local_dir . '/' . $relative_path : $relative_path;
            
            if ($file->isDir()) {
                $this->zip->addEmptyDir($local_path);
            } else {
                $this->zip->addFile($file_path, $local_path);
            }
        }
        
        return true;
    }
    
    public function add_directory_chunked($dir_path, $local_dir = '', $offset = 0, $limit = 100, $exclude_patterns = array()) {
        if (!is_dir($dir_path)) {
            return array('added' => 0, 'total' => 0, 'completed' => true);
        }
        
        $files = array();
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir_path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            $file_path = $file->getRealPath();
            
            if ($this->should_exclude($file_path, $exclude_patterns)) {
                continue;
            }
            
            $files[] = $file;
        }
        
        $total = count($files);
        $added = 0;
        
        for ($i = $offset; $i < min($offset + $limit, $total); $i++) {
            $file = $files[$i];
            $file_path = $file->getRealPath();
            $relative_path = substr($file_path, strlen($dir_path) + 1);
            $local_path = $local_dir ? $local_dir . '/' . $relative_path : $relative_path;
            
            if ($file->isDir()) {
                $this->zip->addEmptyDir($local_path);
            } else {
                $this->zip->addFile($file_path, $local_path);
            }
            
            $added++;
        }
        
        $completed = ($offset + $limit) >= $total;
        
        return array(
            'added' => $added,
            'total' => $total,
            'completed' => $completed,
            'next_offset' => $offset + $limit
        );
    }
    
    private function should_exclude($file_path, $exclude_patterns) {
        foreach ($exclude_patterns as $pattern) {
            if (strpos($file_path, $pattern) !== false) {
                return true;
            }
        }
        
        return fly_backup_should_exclude_file($file_path);
    }
    
    public function extract($destination) {
        if (!$this->zip) {
            return false;
        }
        
        return $this->zip->extractTo($destination);
    }
    
    public function extract_file($file_name, $destination) {
        if (!$this->zip) {
            return false;
        }
        
        return $this->zip->extractTo($destination, $file_name);
    }
    
    public function close() {
        if ($this->zip) {
            return $this->zip->close();
        }
        return false;
    }
    
    public function get_file_count() {
        if ($this->zip) {
            return $this->zip->numFiles;
        }
        return 0;
    }
    
    public function get_file_list() {
        if (!$this->zip) {
            return array();
        }
        
        $files = array();
        for ($i = 0; $i < $this->zip->numFiles; $i++) {
            $files[] = $this->zip->getNameIndex($i);
        }
        
        return $files;
    }
    
    public function verify() {
        if (!file_exists($this->zip_path)) {
            return false;
        }
        
        $zip = new ZipArchive();
        $result = $zip->open($this->zip_path, ZipArchive::CHECKCONS);
        
        if ($result === true) {
            $zip->close();
            return true;
        }
        
        return false;
    }
    
    public function get_size() {
        if (file_exists($this->zip_path)) {
            return filesize($this->zip_path);
        }
        return 0;
    }
}
