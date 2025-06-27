<?php
/**
 * Helper functions for BP Resume CSV Import/Export
 * 
 * File: includes/helpers.php
 * 
 * Only include functions that are reused across multiple files
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Check if user can access CSV functionality
 * 
 * @param int $user_id User ID (optional, defaults to current user)
 * @return bool
 */
function bprm_csv_user_can_access($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return false;
    }
    
    // Use existing plugin method if available
    if (class_exists('BP_Resume_CSV_Plugin')) {
        return BP_Resume_CSV_Plugin::user_has_csv_access($user_id);
    }
    
    return true; // Default to allow access
}

/**
 * Get max file size for CSV uploads
 * 
 * @return int File size in bytes
 */
function bprm_csv_get_max_file_size() {
    $settings = get_option('bp_resume_csv_options', array());
    $max_size = isset($settings['max_file_size']) ? $settings['max_file_size'] : 5;
    
    return $max_size * 1024 * 1024; // Convert MB to bytes
}

/**
 * Format file size for display
 * 
 * @param int $bytes File size in bytes
 * @return string Formatted file size
 */
function bprm_csv_format_file_size($bytes) {
    if ($bytes >= 1024 * 1024) {
        return round($bytes / (1024 * 1024), 1) . ' MB';
    } elseif ($bytes >= 1024) {
        return round($bytes / 1024, 1) . ' KB';
    }
    return $bytes . ' bytes';
}