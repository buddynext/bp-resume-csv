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

/**
 * Check if BP Resume Manager is active and available
 * 
 * @return bool
 */
function bprm_csv_is_bprm_active() {
    return defined('BPRM_PLUGIN_VERSION') && class_exists('Bprm_Resume_Manager');
}

/**
 * Get plugin version
 * 
 * @return string
 */
function bprm_csv_get_version() {
    return BP_RESUME_CSV_VERSION;
}

/**
 * Get plugin upload directory
 * 
 * @return string
 */
function bprm_csv_get_upload_dir() {
    $upload_dir = wp_upload_dir();
    return $upload_dir['basedir'] . '/bp-resume-csv/';
}

/**
 * Get plugin upload URL
 * 
 * @return string
 */
function bprm_csv_get_upload_url() {
    $upload_dir = wp_upload_dir();
    return $upload_dir['baseurl'] . '/bp-resume-csv/';
}

/**
 * Log CSV activity if logging is enabled
 * 
 * @param int $user_id User ID
 * @param string $action Action performed
 * @param string $details Additional details
 */
function bprm_csv_log_activity($user_id, $action, $details = '') {
    if (class_exists('BP_Resume_CSV_Admin')) {
        BP_Resume_CSV_Admin::log_import_activity($user_id, $action, $details);
    }
}

/**
 * Get CSV field types
 * 
 * @return array
 */
function bprm_csv_get_field_types() {
    return array(
        'textbox' => __('Text Box', 'bp-resume-csv'),
        'textarea' => __('Text Area', 'bp-resume-csv'),
        'email' => __('Email', 'bp-resume-csv'),
        'phone_number' => __('Phone Number', 'bp-resume-csv'),
        'url' => __('URL', 'bp-resume-csv'),
        'calender_field' => __('Date', 'bp-resume-csv'),
        'year_dropdown' => __('Year', 'bp-resume-csv'),
        'place_autocomplete' => __('Location', 'bp-resume-csv'),
        'dropdown' => __('Dropdown', 'bp-resume-csv'),
        'radio_button' => __('Radio Button', 'bp-resume-csv'),
        'checkbox' => __('Checkbox', 'bp-resume-csv'),
        'selectize' => __('Multi-select', 'bp-resume-csv'),
        'text_dropdown' => __('Text + Dropdown', 'bp-resume-csv'),
        'image' => __('Image', 'bp-resume-csv'),
        'text_oembed' => __('Text/Embed', 'bp-resume-csv')
    );
}

/**
 * Sanitize CSV field value based on type
 * 
 * @param mixed $value Field value
 * @param string $type Field type
 * @return mixed Sanitized value
 */
function bprm_csv_sanitize_field_value($value, $type) {
    switch ($type) {
        case 'email':
            return sanitize_email($value);
        case 'url':
            return esc_url_raw($value);
        case 'textbox':
        case 'textarea':
            return sanitize_textarea_field($value);
        case 'calender_field':
            return sanitize_text_field($value);
        case 'year_dropdown':
            return absint($value);
        default:
            return sanitize_text_field($value);
    }
}

/**
 * Validate CSV field value based on type
 * 
 * @param mixed $value Field value
 * @param string $type Field type
 * @return bool|WP_Error True if valid, WP_Error if invalid
 */
function bprm_csv_validate_field_value($value, $type) {
    switch ($type) {
        case 'email':
            if (!empty($value) && !is_email($value)) {
                return new WP_Error('invalid_email', __('Invalid email address', 'bp-resume-csv'));
            }
            break;
        case 'url':
            if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                return new WP_Error('invalid_url', __('Invalid URL', 'bp-resume-csv'));
            }
            break;
        case 'year_dropdown':
            $year = intval($value);
            if (!empty($value) && ($year < 1900 || $year > date('Y') + 10)) {
                return new WP_Error('invalid_year', __('Invalid year', 'bp-resume-csv'));
            }
            break;
        case 'calender_field':
            if (!empty($value) && !strtotime($value)) {
                return new WP_Error('invalid_date', __('Invalid date format. Use YYYY-MM-DD', 'bp-resume-csv'));
            }
            break;
    }
    
    return true;
}

/**
 * Get sample data for field type
 * 
 * @param string $type Field type
 * @param array $options Field options (for dropdowns)
 * @return string Sample value
 */
function bprm_csv_get_sample_value($type, $options = array()) {
    $samples = array(
        'textbox' => 'Sample Text',
        'textarea' => 'Sample long text content here.',
        'email' => 'user@example.com',
        'phone_number' => '+1234567890',
        'url' => 'https://example.com',
        'calender_field' => date('Y-m-d'),
        'year_dropdown' => date('Y'),
        'place_autocomplete' => 'New York, NY, USA',
        'image' => 'image_url_or_id',
        'text_oembed' => 'https://www.youtube.com/watch?v=example'
    );
    
    if (isset($samples[$type])) {
        return $samples[$type];
    }
    
    // Handle option-based fields
    switch ($type) {
        case 'dropdown':
        case 'radio_button':
            return !empty($options) ? $options[0] : 'Option 1';
        case 'checkbox':
        case 'selectize':
            return !empty($options) ? $options[0] : 'Option 1';
        case 'text_dropdown':
            $option = !empty($options) ? $options[0] : '5';
            return json_encode(array('text' => 'Sample Text', 'dropdown_val' => $option));
        default:
            return 'Sample Value';
    }
}

/**
 * Check if current page is CSV import page
 * 
 * @return bool
 */
function bprm_csv_is_import_page() {
    return bp_is_user_profile() && 
           bp_is_current_component('resume') && 
           bp_is_current_action('csv-import');
}

/**
 * Get CSV template headers
 * 
 * @return array
 */
function bprm_csv_get_headers() {
    return array(
        'group_key',
        'group_name', 
        'group_instance',
        'field_key',
        'field_title',
        'field_type',
        'field_instance',
        'field_value',
        'field_options_available'
    );
}

/**
 * Create CSV download response
 * 
 * @param array $data CSV data
 * @param string $filename Filename
 */
function bprm_csv_create_download($data, $filename) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}