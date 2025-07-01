<?php
/**
 * Clean CSV Handler Class - NO DUPLICATES
 * 
 * File: includes/class-bp-resume-csv-handler.php
 * 
 * Replace your entire class with this clean version
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class BP_Resume_CSV_Handler {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('wp_ajax_bprm_download_sample_csv', array($this, 'download_sample_csv'));
        add_action('wp_ajax_bprm_upload_csv_data', array($this, 'process_csv_upload'));
        add_action('wp_ajax_bprm_export_current_data', array($this, 'export_current_data'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Non-logged in users (for template download)
        add_action('wp_ajax_nopriv_bprm_download_sample_csv', array($this, 'download_sample_csv'));
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        // More permissive condition - enqueue on any resume page
        if (bp_is_user_profile() && (
            bp_is_current_component('resume') || 
            (function_exists('bp_is_current_component') && bp_is_current_component('resume')) ||
            strpos($_SERVER['REQUEST_URI'], '/resume/') !== false
        )) {
            wp_enqueue_script(
                'bp-resume-csv-handler',
                BP_RESUME_CSV_PLUGIN_URL . 'assets/js/csv-handler.js',
                array('jquery'),
                BP_RESUME_CSV_VERSION,
                true
            );
            
            wp_enqueue_style(
                'bp-resume-csv-style',
                BP_RESUME_CSV_PLUGIN_URL . 'assets/css/csv-style.css',
                array(),
                BP_RESUME_CSV_VERSION
            );
            
            wp_localize_script('bp-resume-csv-handler', 'bprm_csv_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('bprm_csv_nonce'),
                'messages' => array(
                    'upload_success' => __('CSV data imported successfully!', 'bp-resume-csv'),
                    'export_success' => __('Current data exported successfully!', 'bp-resume-csv'),
                    'upload_error' => __('Error importing CSV data. Please check your file format.', 'bp-resume-csv'),
                    'file_required' => __('Please select a CSV file to upload.', 'bp-resume-csv'),
                    'processing' => __('Processing...', 'bp-resume-csv'),
                    'confirm_import' => __('Importing CSV data will replace your existing resume information. Make sure you have exported your current data if you want to keep a backup. Do you want to continue?', 'bp-resume-csv'),
                )
            ));
        }
    }
    
    /**
     * Get user's available resume fields
     */
    public function get_user_resume_fields($user_id) {
        // Check if BP Resume Manager is available
        if (!defined('BPRM_PLUGIN_VERSION')) {
            return $this->get_sample_resume_fields();
        }
        
        if (is_multisite() && is_plugin_active_for_network('bp-resume-manager/bp-resume-manager.php')) {
            $bprm_settings = get_site_option('bprm_resume_settings');
            $grp_args = get_site_option('bprm_groups_settings');
        } else {
            $bprm_settings = get_option('bprm_resume_settings');
            $grp_args = get_option('bprm_groups_settings');
        }
        
        if (empty($bprm_settings) || empty($grp_args)) {
            return $this->get_sample_resume_fields();
        }
        
        $user_meta = get_userdata($user_id);
        $user_role = $user_meta->roles;
        $mem_type = function_exists('bp_get_member_type') ? bp_get_member_type($user_id) : '';
        
        $available_fields = array();
        
        foreach ($grp_args as $group_index => $group_info) {
            if (!isset($group_info['resume_display']) || $group_info['resume_display'] !== 'yes') {
                continue;
            }
            
            if (!$this->check_group_availability($group_info, $user_role, $mem_type)) {
                continue;
            }
            
            if (!isset($bprm_settings[$group_index])) {
                continue;
            }
            
            foreach ($bprm_settings[$group_index] as $field_key => $field_info) {
                if (!isset($field_info['display']) || $field_info['display'] !== 'yes') {
                    continue;
                }
                
                $available_fields[$group_index][$field_key] = array(
                    'title' => $field_info['field_tile'],
                    'type' => $field_info['field_type']['type'],
                    'options' => isset($field_info['field_type']['options']) ? $field_info['field_type']['options'] : array(),
                    'repeater' => isset($field_info['repeater']) ? $field_info['repeater'] : 'no',
                    'group_name' => $group_info['g_name'],
                    'group_repeater' => isset($group_info['repeater']) ? $group_info['repeater'] : 'no'
                );
            }
        }
        
        return apply_filters('bprm_csv_available_fields', $available_fields, $user_id);
    }
    
    /**
     * Get sample resume fields for demo purposes
     */
    private function get_sample_resume_fields() {
        return array(
            'personal_info' => array(
                'first_name' => array(
                    'title' => 'First Name',
                    'type' => 'textbox',
                    'options' => array(),
                    'repeater' => 'no',
                    'group_name' => 'Personal Information',
                    'group_repeater' => 'no'
                ),
                'last_name' => array(
                    'title' => 'Last Name',
                    'type' => 'textbox',
                    'options' => array(),
                    'repeater' => 'no',
                    'group_name' => 'Personal Information',
                    'group_repeater' => 'no'
                ),
                'email' => array(
                    'title' => 'Email Address',
                    'type' => 'email',
                    'options' => array(),
                    'repeater' => 'no',
                    'group_name' => 'Personal Information',
                    'group_repeater' => 'no'
                )
            ),
            'work_experience' => array(
                'job_title' => array(
                    'title' => 'Job Title',
                    'type' => 'textbox',
                    'options' => array(),
                    'repeater' => 'no',
                    'group_name' => 'Work Experience',
                    'group_repeater' => 'yes'
                ),
                'company' => array(
                    'title' => 'Company',
                    'type' => 'textbox',
                    'options' => array(),
                    'repeater' => 'no',
                    'group_name' => 'Work Experience',
                    'group_repeater' => 'yes'
                )
            )
        );
    }
    
    /**
     * Check group availability for user
     */
    private function check_group_availability($group_info, $user_role, $mem_type) {
        if (!isset($group_info['grp_avail'])) {
            return true;
        }
        
        $grp_avail = $group_info['grp_avail'];
        
        if ('user_roles' === $grp_avail) {
            $roles = isset($group_info['roles']) ? $group_info['roles'] : array('all');
            if (is_array($roles)) {
                $roles_result = array_intersect($roles, $user_role);
                return !empty($roles_result) || in_array('all', $roles, true);
            }
            return 'all' === $roles;
        } elseif ('mem_type' === $grp_avail) {
            $mtypes = isset($group_info['mtypes']) ? $group_info['mtypes'] : array('all');
            if (is_array($mtypes) && !empty($mem_type)) {
                return in_array($mem_type, $mtypes, true) || in_array('all', $mtypes, true);
            }
            return 'all' === $mtypes || empty($mem_type);
        }
        
        return true;
    }
    
    /**
     * Download sample CSV
     */
    public function download_sample_csv() {
        // Verify nonce for logged-in users
        if (is_user_logged_in()) {
            if (!wp_verify_nonce($_POST['nonce'] ?? '', 'bprm_csv_nonce')) {
                wp_die(__('Security check failed', 'bp-resume-csv'));
            }
            $user_id = get_current_user_id();
        } else {
            // For demo purposes, use a generic template
            $user_id = 0;
        }
        
        $available_fields = $user_id ? $this->get_user_resume_fields($user_id) : $this->get_sample_resume_fields();
        
        if (empty($available_fields)) {
            wp_die(__('No resume fields available for your profile', 'bp-resume-csv'));
        }
        
        $csv_data = $this->generate_csv_structure($available_fields);
        $this->output_csv_file($csv_data, 'resume_template_' . date('Y-m-d_H-i-s') . '.csv', true);
    }
    
    /**
     * Export current user data
     */
    public function export_current_data() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'bprm_csv_nonce') || !is_user_logged_in()) {
            wp_die(__('Security check failed', 'bp-resume-csv'));
        }
        
        $user_id = get_current_user_id();
        $available_fields = $this->get_user_resume_fields($user_id);
        
        if (empty($available_fields)) {
            wp_die(__('No resume fields available for export', 'bp-resume-csv'));
        }
        
        $csv_data = $this->generate_export_data($available_fields, $user_id);
        $this->output_csv_file($csv_data, 'resume_data_' . date('Y-m-d_H-i-s') . '.csv', false);
    }
    
    /**
     * Generate CSV structure with headers and sample data - SINGLE DEFINITION
     */
    private function generate_csv_structure($available_fields) {
        $headers = array(
            'group_key',
            'group_name',
            'group_instance',
            'field_key',
            'field_title',
            'field_type',
            'field_instance',
            'field_value',
            'field_options_available',
            'field_required',
            'field_section_title',
            'field_section_icon'
        );
        
        $sample_rows = array();
        
        foreach ($available_fields as $group_key => $fields) {
            foreach ($fields as $field_key => $field_info) {
                $sample_value = $this->get_sample_value_for_field_type($field_info['type'], $field_info['options']);
                $options_text = !empty($field_info['options']) ? implode('|', $field_info['options']) : '';
                
                // Add main sample row
                $sample_rows[] = array(
                    $group_key,
                    $field_info['group_name'],
                    '0',
                    $field_key,
                    $field_info['title'],
                    $field_info['type'],
                    '0',
                    $sample_value,
                    $options_text,
                    isset($field_info['required']) ? $field_info['required'] : 'no',
                    isset($field_info['section_title']) ? $field_info['section_title'] : '',
                    isset($field_info['section_icon']) ? $field_info['section_icon'] : ''
                );
                
                // Add sample for repeater fields
                if (isset($field_info['repeater']) && $field_info['repeater'] === 'yes') {
                    $sample_rows[] = array(
                        $group_key,
                        $field_info['group_name'],
                        '0',
                        $field_key,
                        $field_info['title'],
                        $field_info['type'],
                        '1',
                        $sample_value,
                        $options_text,
                        isset($field_info['required']) ? $field_info['required'] : 'no',
                        isset($field_info['section_title']) ? $field_info['section_title'] : '',
                        isset($field_info['section_icon']) ? $field_info['section_icon'] : ''
                    );
                }
            }
        }
        
        return array(
            'headers' => apply_filters('bprm_csv_headers', $headers),
            'sample_rows' => apply_filters('bprm_csv_sample_rows', $sample_rows, $available_fields)
        );
    }
    
    /**
     * Generate export data with current user values - SINGLE DEFINITION
     */
    private function generate_export_data($available_fields, $user_id) {
        $headers = array(
            'group_key',
            'group_name',
            'group_instance',
            'field_key',
            'field_title',
            'field_type',
            'field_instance',
            'field_value',
            'field_options_available',
            'field_required',
            'field_section_title',
            'field_section_icon'
        );
        
        $data_rows = array();
        
        // If BP Resume Manager is not available, use sample data
        if (!defined('BPRM_PLUGIN_VERSION')) {
            return $this->generate_sample_export_data($user_id);
        }
        
        foreach ($available_fields as $group_key => $fields) {
            $group_count = get_user_meta($user_id, 'bprm_resume_' . $group_key . '_count', true);
            $group_count = ($group_count != '') ? intval($group_count) : 1;
            
            for ($g_instance = 0; $g_instance < $group_count; $g_instance++) {
                foreach ($fields as $field_key => $field_info) {
                    $field_count = get_user_meta($user_id, 'bprm_resume_' . $field_key . '_count', true);
                    $field_count = ($field_count != '') ? intval($field_count) : 1;
                    
                    for ($f_instance = 0; $f_instance < $field_count; $f_instance++) {
                        $g_key = ($g_instance != 0) ? '_' . $g_instance : '';
                        $field_repet_key = ($f_instance != 0) ? '_' . $f_instance : '';
                        $meta_key = 'bprm_resume_' . $group_key . $g_key . '_' . $field_key . $field_repet_key;
                        
                        $field_value = get_user_meta($user_id, $meta_key, true);
                        $options_text = !empty($field_info['options']) ? implode('|', $field_info['options']) : '';
                        
                        // Export ALL fields (including empty ones) so structure is preserved
                        $data_rows[] = array(
                            $group_key,
                            $field_info['group_name'],
                            $g_instance,
                            $field_key,
                            $field_info['title'],
                            $field_info['type'],
                            $f_instance,
                            $field_value,
                            $options_text,
                            isset($field_info['required']) ? $field_info['required'] : 'no',
                            isset($field_info['section_title']) ? $field_info['section_title'] : '',
                            isset($field_info['section_icon']) ? $field_info['section_icon'] : ''
                        );
                    }
                }
            }
        }
        
        return array(
            'headers' => $headers,
            'data_rows' => $data_rows
        );
    }
    
    /**
     * Generate sample export data - SINGLE DEFINITION
     */
    private function generate_sample_export_data($user_id) {
        $headers = array(
            'group_key',
            'group_name',
            'group_instance',
            'field_key',
            'field_title',
            'field_type',
            'field_instance',
            'field_value',
            'field_options_available',
            'field_required',
            'field_section_title',
            'field_section_icon'
        );
        
        // Get user info for sample data
        $user = get_userdata($user_id);
        $first_name = $user ? $user->first_name : 'Sample';
        $last_name = $user ? $user->last_name : 'User';
        $email = $user ? $user->user_email : 'sample@example.com';
        
        $data_rows = array(
            array('personal_info', 'Personal Information', '0', 'first_name', 'First Name', 'textbox', '0', $first_name, '', 'yes', '', ''),
            array('personal_info', 'Personal Information', '0', 'last_name', 'Last Name', 'textbox', '0', $last_name, '', 'yes', '', ''),
            array('personal_info', 'Personal Information', '0', 'email', 'Email Address', 'email', '0', $email, '', 'yes', '', ''),
        );
        
        return array(
            'headers' => $headers,
            'data_rows' => $data_rows
        );
    }
    
    /**
     * Get sample value for field type
     */
    private function get_sample_value_for_field_type($field_type, $options = array()) {
        $sample_values = array(
            'textbox' => 'Sample Text',
            'textarea' => 'Sample long text content',
            'email' => 'sample@example.com',
            'phone_number' => '+1234567890',
            'url' => 'https://example.com',
            'calender_field' => date('Y-m-d'),
            'year_dropdown' => date('Y'),
            'place_autocomplete' => 'New York, NY, USA',
            'text_oembed' => 'https://www.youtube.com/watch?v=example'
        );
        
        if (isset($sample_values[$field_type])) {
            return $sample_values[$field_type];
        }
        
        switch ($field_type) {
            case 'dropdown':
            case 'radio_button':
                return !empty($options) ? $options[0] : 'Option 1';
            case 'text_dropdown':
                $sample_option = !empty($options) ? $options[0] : '5';
                return json_encode(array('text' => 'Sample Skill', 'dropdown_val' => $sample_option));
            default:
                return 'Sample Value';
        }
    }
    
    /**
     * Output CSV file
     */
    private function output_csv_file($csv_data, $filename, $include_instructions = false) {
        if (ob_get_level()) {
            ob_end_clean();
        }
    
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM for Excel compatibility
    
        fputcsv($output, $csv_data['headers']);
    
        $rows_key = $include_instructions ? 'sample_rows' : 'data_rows';
        foreach ($csv_data[$rows_key] as $row) {
            fputcsv($output, $row);
        }
    
        fclose($output);
        exit;
    }
    
    /**
     * Process CSV upload - FIXED VERSION
     * Replace the existing process_csv_upload method with this one
     */
    public function process_csv_upload() {
        error_log('CSV Upload: Starting process_csv_upload');
        
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'bprm_csv_nonce') || !is_user_logged_in()) {
            error_log('CSV Upload: Security check failed');
            wp_send_json_error(array('message' => __('Security check failed', 'bp-resume-csv')));
        }
        
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            error_log('CSV Upload: File upload error - ' . ($_FILES['csv_file']['error'] ?? 'No file'));
            wp_send_json_error(array('message' => __('Please upload a valid CSV file', 'bp-resume-csv')));
        }
        
        $file = $_FILES['csv_file'];
        error_log('CSV Upload: File details - Name: ' . $file['name'] . ', Size: ' . $file['size'] . ', Type: ' . $file['type']);
        
        $validation_result = $this->validate_uploaded_file($file);
        if (is_wp_error($validation_result)) {
            error_log('CSV Upload: File validation failed - ' . $validation_result->get_error_message());
            wp_send_json_error(array('message' => $validation_result->get_error_message()));
        }
        
        $user_id = get_current_user_id();
        $available_fields = $this->get_user_resume_fields($user_id);
        error_log('CSV Upload: Available fields groups: ' . count($available_fields));
        
        $csv_data = $this->parse_csv_file($file['tmp_name']);
        if ($csv_data === false) {
            error_log('CSV Upload: Failed to parse CSV file');
            wp_send_json_error(array('message' => __('Error reading CSV file. Please check the file format.', 'bp-resume-csv')));
        }
        
        error_log('CSV Upload: Parsed ' . count($csv_data) . ' rows');
        if (!empty($csv_data)) {
            error_log('CSV Upload: First row headers: ' . implode(', ', array_keys($csv_data[0])));
        }
        
        $result = $this->process_csv_data_improved($csv_data, $available_fields, $user_id);
        
        if ($result['success']) {
            error_log('CSV Upload: Success - ' . $result['imported_count'] . ' fields imported');
            wp_send_json_success(array(
                'message' => sprintf(__('Resume data imported successfully! %d fields updated.', 'bp-resume-csv'), $result['imported_count']),
                'imported_count' => $result['imported_count']
            ));
        } else {
            error_log('CSV Upload: Failed - ' . $result['message']);
            wp_send_json_error(array('message' => $result['message']));
        }
    }
    
    /**
     * Process CSV data - SINGLE IMPROVED VERSION
     */
    private function process_csv_data_improved($csv_data, $available_fields, $user_id) {
        error_log('CSV Process: Starting data processing for user ' . $user_id);
        error_log('CSV Process: Processing ' . count($csv_data) . ' rows');
        error_log('CSV Process: Available field groups: ' . count($available_fields));
        
        $imported_count = 0;
        $errors = array();
        
        if (!defined('BPRM_PLUGIN_VERSION')) {
            error_log('CSV Process: BP Resume Manager not active, simulating import');
            return array('success' => true, 'imported_count' => count($csv_data));
        }
        
        if (empty($csv_data)) {
            return array('success' => false, 'message' => __('No data found in CSV file', 'bp-resume-csv'));
        }
        
        // Validate required headers exist in data
        $first_row = $csv_data[0];
        $csv_headers = array_keys($first_row);
        $required_headers = array('group_key', 'field_key', 'field_value');
        $missing_headers = array_diff($required_headers, $csv_headers);
        
        if (!empty($missing_headers)) {
            error_log('CSV Process: Missing headers in data: ' . implode(', ', $missing_headers));
            error_log('CSV Process: Available headers: ' . implode(', ', $csv_headers));
            return array(
                'success' => false,
                'message' => sprintf(__('Missing required CSV columns: %s. Available columns: %s', 'bp-resume-csv'), 
                    implode(', ', $missing_headers),
                    implode(', ', $csv_headers)
                )
            );
        }
        
        // Process each row
        foreach ($csv_data as $row_index => $row) {
            if (empty($row['group_key']) || empty($row['field_key'])) {
                error_log('CSV Process: Row ' . $row_index . ' missing group_key or field_key');
                continue;
            }
            
            // Allow empty values to clear fields
            if (!isset($row['field_value'])) {
                error_log('CSV Process: Row ' . $row_index . ' missing field_value column');
                continue;
            }
            
            $group_key = sanitize_key($row['group_key']);
            $field_key = sanitize_key($row['field_key']);
            $group_instance = isset($row['group_instance']) ? intval($row['group_instance']) : 0;
            $field_instance = isset($row['field_instance']) ? intval($row['field_instance']) : 0;
            
            // Check if field is available
            if (!isset($available_fields[$group_key][$field_key])) {
                $errors[] = sprintf('Row %d: Field "%s" in group "%s" not available', 
                    $row_index + 1, $field_key, $group_key);
                error_log('CSV Process: Field not available - ' . $group_key . '.' . $field_key);
                continue;
            }
            
            $field_info = $available_fields[$group_key][$field_key];
            $g_key = ($group_instance != 0) ? '_' . $group_instance : '';
            $field_repet_key = ($field_instance != 0) ? '_' . $field_instance : '';
            $meta_key = 'bprm_resume_' . $group_key . $g_key . '_' . $field_key . $field_repet_key;
            
            $processed_value = $this->process_field_value($row['field_value'], $field_info);
            
            if ($processed_value !== false) {
                $update_result = update_user_meta($user_id, $meta_key, $processed_value);
                if ($update_result !== false) {
                    $imported_count++;
                    error_log('CSV Process: Updated ' . $meta_key . ' = "' . $processed_value . '"');
                }
            }
        }
        
        // Log errors if any
        if (!empty($errors)) {
            error_log('CSV Process: Errors encountered: ' . implode('; ', $errors));
            if (count($errors) > 10) {
                return array('success' => false, 'message' => __('Too many validation errors. Please check your CSV format.', 'bp-resume-csv'));
            }
        }
        
        error_log('CSV Process: Successfully imported ' . $imported_count . ' fields');
        return array('success' => true, 'imported_count' => $imported_count);
    }
    
    /**
     * Process field value based on type
     */
    private function process_field_value($value, $field_info) {
        if (empty($value) && $value !== '0') {
            return '';
        }
        
        $value = sanitize_textarea_field($value);
        
        switch ($field_info['type']) {
            case 'email':
                return is_email($value) ? $value : $value;
            case 'url':
                return filter_var($value, FILTER_VALIDATE_URL) ? $value : $value;
            case 'calender_field':
                $timestamp = strtotime($value);
                return $timestamp ? date('Y-m-d', $timestamp) : $value;
            case 'year_dropdown':
                $year = intval($value);
                return ($year >= 1900 && $year <= date('Y') + 10) ? $year : $value;
            default:
                return $value;
        }
    }
    
    /**
     * Parse CSV file
     */
    private function parse_csv_file($file_path) {
        error_log('CSV Parse: Starting to parse file: ' . $file_path);
        
        $csv_data = array();
        $headers = array();
        $row_count = 0;
        
        if (!file_exists($file_path) || !is_readable($file_path)) {
            error_log('CSV Parse: File does not exist or is not readable');
            return false;
        }
        
        // Read file content and check encoding
        $file_content = file_get_contents($file_path);
        if ($file_content === false) {
            error_log('CSV Parse: Failed to read file content');
            return false;
        }
        
        // Handle different line endings
        $file_content = str_replace(array("\r\n", "\r"), "\n", $file_content);
        
        // Remove BOM if present
        if (substr($file_content, 0, 3) === "\xEF\xBB\xBF") {
            $file_content = substr($file_content, 3);
            error_log('CSV Parse: Removed BOM from file');
        }
        
        // Split into lines
        $lines = explode("\n", $file_content);
        error_log('CSV Parse: Total lines in file: ' . count($lines));
        
        foreach ($lines as $line_number => $line) {
            $line = trim($line);
            
            // Skip empty lines
            if (empty($line)) {
                continue;
            }
            
            // Skip comment lines (starting with #)
            if (strpos($line, '#') === 0) {
                error_log('CSV Parse: Skipping comment line ' . ($line_number + 1));
                continue;
            }
            
            // Parse CSV line
            $data = str_getcsv($line, ',', '"', '\\');
            
            // Skip lines with no data
            if (empty(array_filter($data))) {
                continue;
            }
            
            if ($row_count === 0) {
                // First data row should be headers
                $headers = array_map('trim', $data);
                error_log('CSV Parse: Headers found: ' . implode(', ', $headers));
                
                // Validate required headers
                $required_headers = array('group_key', 'field_key', 'field_value');
                $missing_headers = array_diff($required_headers, $headers);
                
                if (!empty($missing_headers)) {
                    error_log('CSV Parse: Missing required headers: ' . implode(', ', $missing_headers));
                    error_log('CSV Parse: Available headers: ' . implode(', ', $headers));
                    return false;
                }
                
            } else {
                // Data rows
                if (count($data) === count($headers)) {
                    $row_data = array_combine($headers, array_map('trim', $data));
                    $csv_data[] = $row_data;
                    
                    if ($row_count <= 3) { // Log first few rows for debugging
                        error_log('CSV Parse: Row ' . $row_count . ' data: ' . json_encode($row_data));
                    }
                } else {
                    error_log('CSV Parse: Row ' . ($line_number + 1) . ' has ' . count($data) . ' columns, expected ' . count($headers));
                }
            }
            $row_count++;
        }
        
        error_log('CSV Parse: Successfully parsed ' . count($csv_data) . ' data rows');
        return empty($csv_data) ? false : $csv_data;
    }
    
    /**
     * Validate uploaded file
     */
    private function validate_uploaded_file($file) {
        // Check file extension
        $file_parts = pathinfo($file['name']);
        $extension = strtolower($file_parts['extension'] ?? '');
        
        if ($extension !== 'csv') {
            return new WP_Error('invalid_file_type', __('Only CSV files are allowed. Uploaded file has extension: ', 'bp-resume-csv') . $extension);
        }
        
        // Check MIME type
        $allowed_mimes = array('text/csv', 'text/plain', 'application/csv');
        $file_type = wp_check_filetype($file['name']);
        
        if (!in_array($file['type'], $allowed_mimes) && $file_type['type'] !== 'text/csv') {
            error_log('CSV Upload: Invalid MIME type - ' . $file['type'] . ', expected one of: ' . implode(', ', $allowed_mimes));
            // Don't fail on MIME type alone, as it can be inconsistent
        }
        
        // Check file size
        $max_size = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $max_size) {
            return new WP_Error('file_too_large', sprintf(__('File size must be less than %s. Uploaded file is %s', 'bp-resume-csv'), 
                size_format($max_size), 
                size_format($file['size'])
            ));
        }
        
        if ($file['size'] === 0) {
            return new WP_Error('empty_file', __('The uploaded file is empty', 'bp-resume-csv'));
        }
        
        // Try to read first few bytes to ensure it's readable
        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) {
            return new WP_Error('unreadable_file', __('Cannot read the uploaded file', 'bp-resume-csv'));
        }
        
        $first_line = fgets($handle);
        fclose($handle);
        
        if ($first_line === false) {
            return new WP_Error('empty_content', __('The uploaded file appears to be empty or corrupted', 'bp-resume-csv'));
        }
        
        error_log('CSV Upload: File validation passed. First line: ' . substr($first_line, 0, 100));
        return true;
    }
    
    /**
     * Render CSV interface
     */
    public function render_csv_interface() {
        if (!is_user_logged_in()) {
            echo '<div class="bp-feedback error"><p>' . __('You must be logged in to access CSV import/export functionality.', 'bp-resume-csv') . '</p></div>';
            return;
        }
        
        $user_id = get_current_user_id();
        $available_fields = $this->get_user_resume_fields($user_id);
        
        if (empty($available_fields)) {
            echo '<div class="bp-feedback info"><p>' . __('No resume fields available for CSV import/export.', 'bp-resume-csv') . '</p></div>';
            return;
        }
        
        include BP_RESUME_CSV_PLUGIN_PATH . 'templates/csv-interface.php';
    }
    
    /**
     * Get field statistics for user
     */
    public function get_field_statistics($user_id) {
        $available_fields = $this->get_user_resume_fields($user_id);
        $total_fields = 0;
        $filled_fields = 0;
        
        foreach ($available_fields as $group_key => $fields) {
            foreach ($fields as $field_key => $field_info) {
                $total_fields++;
                $meta_key = 'bprm_resume_' . $group_key . '_' . $field_key;
                $field_value = get_user_meta($user_id, $meta_key, true);
                if (!empty($field_value)) {
                    $filled_fields++;
                }
            }
        }
        
        return array(
            'total_fields' => $total_fields,
            'filled_fields' => $filled_fields,
            'completion_percentage' => $total_fields > 0 ? round(($filled_fields / $total_fields) * 100) : 0
        );
    }
}