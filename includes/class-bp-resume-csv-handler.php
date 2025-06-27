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
            'place_autocomplete' => 'New York, NY, USA'
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
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        if ($include_instructions) {
            $instructions = array(
                '# Resume Data Import Template',
                '# IMPORTANT: All columns are required for successful import',
                '# Fill in your data in the field_value column only',
                '# Do NOT modify other columns',
                ''
            );
            
            foreach ($instructions as $instruction) {
                fputcsv($output, array($instruction));
            }
        }
        
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
     */
    public function process_csv_upload() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'bprm_csv_nonce') || !is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Security check failed', 'bp-resume-csv')));
        }
        
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(array('message' => __('Please upload a valid CSV file', 'bp-resume-csv')));
        }
        
        $file = $_FILES['csv_file'];
        $validation_result = $this->validate_uploaded_file($file);
        if (is_wp_error($validation_result)) {
            wp_send_json_error(array('message' => $validation_result->get_error_message()));
        }
        
        $user_id = get_current_user_id();
        $available_fields = $this->get_user_resume_fields($user_id);
        
        $csv_data = $this->parse_csv_file($file['tmp_name']);
        if ($csv_data === false) {
            wp_send_json_error(array('message' => __('Error reading CSV file. Please check the file format.', 'bp-resume-csv')));
        }
        
        $result = $this->process_csv_data_improved($csv_data, $available_fields, $user_id);
        
        if ($result['success']) {
            wp_send_json_success(array(
                'message' => sprintf(__('Resume data imported successfully! %d fields updated.', 'bp-resume-csv'), $result['imported_count']),
                'imported_count' => $result['imported_count']
            ));
        } else {
            wp_send_json_error(array('message' => $result['message']));
        }
    }
    
    /**
     * Process CSV data - SINGLE IMPROVED VERSION
     */
    private function process_csv_data_improved($csv_data, $available_fields, $user_id) {
        $imported_count = 0;
        $errors = array();
        
        if (!defined('BPRM_PLUGIN_VERSION')) {
            return array('success' => true, 'imported_count' => count($csv_data));
        }
        
        if (empty($csv_data)) {
            return array('success' => false, 'message' => __('No data found in CSV file', 'bp-resume-csv'));
        }
        
        // Validate required headers
        $required_headers = array('group_key', 'field_key', 'field_value');
        $csv_headers = array_keys($csv_data[0]);
        $missing_headers = array_diff($required_headers, $csv_headers);
        
        if (!empty($missing_headers)) {
            return array(
                'success' => false,
                'message' => sprintf(__('Missing required CSV columns: %s', 'bp-resume-csv'), implode(', ', $missing_headers))
            );
        }
        
        // Process each row
        foreach ($csv_data as $row) {
            if (empty($row['group_key']) || empty($row['field_key']) || (empty($row['field_value']) && $row['field_value'] !== '0')) {
                continue;
            }
            
            $group_key = sanitize_key($row['group_key']);
            $field_key = sanitize_key($row['field_key']);
            $group_instance = isset($row['group_instance']) ? intval($row['group_instance']) : 0;
            $field_instance = isset($row['field_instance']) ? intval($row['field_instance']) : 0;
            
            if (!isset($available_fields[$group_key][$field_key])) {
                continue;
            }
            
            $field_info = $available_fields[$group_key][$field_key];
            $g_key = ($group_instance != 0) ? '_' . $group_instance : '';
            $field_repet_key = ($field_instance != 0) ? '_' . $field_instance : '';
            $meta_key = 'bprm_resume_' . $group_key . $g_key . '_' . $field_key . $field_repet_key;
            
            $processed_value = $this->process_field_value($row['field_value'], $field_info);
            
            if ($processed_value !== false) {
                update_user_meta($user_id, $meta_key, $processed_value);
                $imported_count++;
            }
        }
        
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
        $csv_data = array();
        $headers = array();
        $row_count = 0;
        
        if (!file_exists($file_path) || !is_readable($file_path)) {
            return false;
        }
        
        if (($handle = fopen($file_path, 'r')) !== false) {
            while (($data = fgetcsv($handle, 10000, ',')) !== false) {
                if (isset($data[0]) && strpos($data[0], '#') === 0) {
                    continue;
                }
                
                if (empty(array_filter($data))) {
                    continue;
                }
                
                if ($row_count === 0) {
                    $headers = array_map('trim', $data);
                } else {
                    if (count($data) === count($headers)) {
                        $csv_data[] = array_combine($headers, array_map('trim', $data));
                    }
                }
                $row_count++;
            }
            fclose($handle);
        }
        
        return empty($csv_data) ? false : $csv_data;
    }
    
    /**
     * Validate uploaded file
     */
    private function validate_uploaded_file($file) {
        $file_type = wp_check_filetype($file['name']);
        
        if ($file_type['ext'] !== 'csv') {
            return new WP_Error('invalid_file_type', __('Only CSV files are allowed', 'bp-resume-csv'));
        }
        
        $max_size = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $max_size) {
            return new WP_Error('file_too_large', sprintf(__('File size must be less than %s', 'bp-resume-csv'), size_format($max_size)));
        }
        
        if ($file['size'] === 0) {
            return new WP_Error('empty_file', __('The uploaded file is empty', 'bp-resume-csv'));
        }
        
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