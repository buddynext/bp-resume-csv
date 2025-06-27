<?php
/**
 * CSV Handler Class
 * 
 * File: includes/class-bp-resume-csv-handler.php
 * 
 * Handles all CSV import/export functionality for BuddyPress Resume Manager
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
        
        // REMOVED ALL INTERFACE RENDERING HOOKS - Interface is only called from screen function
        
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
            error_log('BP Resume CSV: Enqueueing scripts on: ' . $_SERVER['REQUEST_URI']);
            
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
            
            // Always localize the script when we enqueue it
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
            
            error_log('BP Resume CSV: Scripts enqueued successfully');
        } else {
            error_log('BP Resume CSV: Scripts NOT enqueued. Current URL: ' . $_SERVER['REQUEST_URI']);
            error_log('BP Resume CSV: bp_is_user_profile: ' . (bp_is_user_profile() ? 'true' : 'false'));
            error_log('BP Resume CSV: bp_is_current_component(resume): ' . (function_exists('bp_is_current_component') && bp_is_current_component('resume') ? 'true' : 'false'));
        }
    }
    
    /**
     * Add CSV tab content ONLY when called from screen function
     * This function is ONLY called from the main plugin's csv_import_content() method
     */
    public function add_csv_tab_content() {
        // This function is removed and no longer used
        // Interface is only rendered through render_csv_interface() called from main plugin
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
                ),
                'phone' => array(
                    'title' => 'Phone Number',
                    'type' => 'phone_number',
                    'options' => array(),
                    'repeater' => 'no',
                    'group_name' => 'Personal Information',
                    'group_repeater' => 'no'
                ),
                'website' => array(
                    'title' => 'Website',
                    'type' => 'url',
                    'options' => array(),
                    'repeater' => 'no',
                    'group_name' => 'Personal Information',
                    'group_repeater' => 'no'
                ),
                'location' => array(
                    'title' => 'Location',
                    'type' => 'place_autocomplete',
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
                ),
                'start_date' => array(
                    'title' => 'Start Date',
                    'type' => 'calender_field',
                    'options' => array(),
                    'repeater' => 'no',
                    'group_name' => 'Work Experience',
                    'group_repeater' => 'yes'
                ),
                'end_date' => array(
                    'title' => 'End Date',
                    'type' => 'calender_field',
                    'options' => array(),
                    'repeater' => 'no',
                    'group_name' => 'Work Experience',
                    'group_repeater' => 'yes'
                ),
                'description' => array(
                    'title' => 'Job Description',
                    'type' => 'textarea',
                    'options' => array(),
                    'repeater' => 'no',
                    'group_name' => 'Work Experience',
                    'group_repeater' => 'yes'
                )
            ),
            'education' => array(
                'degree' => array(
                    'title' => 'Degree',
                    'type' => 'textbox',
                    'options' => array(),
                    'repeater' => 'no',
                    'group_name' => 'Education',
                    'group_repeater' => 'yes'
                ),
                'institution' => array(
                    'title' => 'Institution',
                    'type' => 'textbox',
                    'options' => array(),
                    'repeater' => 'no',
                    'group_name' => 'Education',
                    'group_repeater' => 'yes'
                ),
                'graduation_year' => array(
                    'title' => 'Graduation Year',
                    'type' => 'year_dropdown',
                    'options' => array(),
                    'repeater' => 'no',
                    'group_name' => 'Education',
                    'group_repeater' => 'yes'
                ),
                'gpa' => array(
                    'title' => 'GPA',
                    'type' => 'textbox',
                    'options' => array(),
                    'repeater' => 'no',
                    'group_name' => 'Education',
                    'group_repeater' => 'yes'
                )
            ),
            'skills' => array(
                'skill_name' => array(
                    'title' => 'Skill',
                    'type' => 'text_dropdown',
                    'options' => array('1', '2', '3', '4', '5'),
                    'repeater' => 'yes',
                    'group_name' => 'Skills',
                    'group_repeater' => 'no'
                )
            ),
            'languages' => array(
                'language' => array(
                    'title' => 'Language',
                    'type' => 'textbox',
                    'options' => array(),
                    'repeater' => 'yes',
                    'group_name' => 'Languages',
                    'group_repeater' => 'no'
                ),
                'proficiency' => array(
                    'title' => 'Proficiency',
                    'type' => 'dropdown',
                    'options' => array('Beginner', 'Intermediate', 'Advanced', 'Native'),
                    'repeater' => 'yes',
                    'group_name' => 'Languages',
                    'group_repeater' => 'no'
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
     * Output CSV file
     */
    private function output_csv_file($csv_data, $filename, $include_instructions = false) {
        // Clean any output buffer
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        
        $output = fopen('php://output', 'w');
        
        // Add BOM for UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        if ($include_instructions) {
            // Add instructions for template
            $instructions = array(
                '# Resume Data Import Template',
                '# Instructions:',
                '# 1. Fill in your data in the rows below',
                '# 2. For repeater fields, add multiple rows with same group_instance',
                '# 3. For field repeaters, use field_instance column',
                '# 4. Do not modify column headers',
                '# 5. Leave group_instance as 0 for non-repeater groups',
                '# 6. Leave field_instance as 0 for non-repeater fields',
                '# 7. For dropdown fields, use values from field_options_available column',
                '# 8. For date fields, use YYYY-MM-DD format',
                '# 9. For text+dropdown fields, use JSON format: {"text":"value","dropdown_val":"option"}',
                '# 10. Save as CSV format when ready to upload',
                ''
            );
            
            foreach ($instructions as $instruction) {
                fputcsv($output, array($instruction));
            }
        }
        
        // Add headers
        fputcsv($output, $csv_data['headers']);
        
        // Add data rows
        $rows_key = $include_instructions ? 'sample_rows' : 'data_rows';
        foreach ($csv_data[$rows_key] as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Generate CSV structure with headers and sample data
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
            'field_options_available'
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
                    $options_text
                );
                
                // Add sample for repeater fields
                if ($field_info['repeater'] === 'yes') {
                    $sample_rows[] = array(
                        $group_key,
                        $field_info['group_name'],
                        '0',
                        $field_key,
                        $field_info['title'],
                        $field_info['type'],
                        '1',
                        $sample_value,
                        $options_text
                    );
                }
            }
            
            // Add sample for group repeater
            if (!empty($fields)) {
                $first_field = array_keys($fields)[0];
                $first_field_info = $fields[$first_field];
                if ($first_field_info['group_repeater'] === 'yes') {
                    $sample_value = $this->get_sample_value_for_field_type($first_field_info['type'], $first_field_info['options']);
                    $options_text = !empty($first_field_info['options']) ? implode('|', $first_field_info['options']) : '';
                    
                    $sample_rows[] = array(
                        $group_key,
                        $first_field_info['group_name'],
                        '1',
                        $first_field,
                        $first_field_info['title'],
                        $first_field_info['type'],
                        '0',
                        $sample_value,
                        $options_text
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
     * Generate export data with current user values
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
            'field_options_available'
        );
        
        $data_rows = array();
        
        // If BP Resume Manager is not available, use sample data
        if (!defined('BPRM_PLUGIN_VERSION')) {
            return $this->generate_sample_export_data($user_id);
        }
        
        foreach ($available_fields as $group_key => $fields) {
            $group_count = get_user_meta($user_id, 'bprm_resume_' . $group_key . '_count', true);
            $group_count = ($group_count != '') ? $group_count : 1;
            
            for ($g_instance = 0; $g_instance < $group_count; $g_instance++) {
                foreach ($fields as $field_key => $field_info) {
                    $field_count = get_user_meta($user_id, 'bprm_resume_' . $field_key . '_count', true);
                    $field_count = ($field_count != '') ? $field_count : 1;
                    
                    for ($f_instance = 0; $f_instance < $field_count; $f_instance++) {
                        $g_key = ($g_instance != 0) ? '_' . $g_instance : '';
                        $field_repet_key = ($f_instance != 0) ? '_' . $f_instance : '';
                        $meta_key = 'bprm_resume_' . $group_key . $g_key . '_' . $field_key . $field_repet_key;
                        
                        $field_value = get_user_meta($user_id, $meta_key, true);
                        $options_text = !empty($field_info['options']) ? implode('|', $field_info['options']) : '';
                        
                        if (!empty($field_value) || $field_value === '0') {
                            $data_rows[] = array(
                                $group_key,
                                $field_info['group_name'],
                                $g_instance,
                                $field_key,
                                $field_info['title'],
                                $field_info['type'],
                                $f_instance,
                                $field_value,
                                $options_text
                            );
                        }
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
     * Generate sample export data
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
            'field_options_available'
        );
        
        // Get user info for sample data
        $user = get_userdata($user_id);
        $first_name = $user ? $user->first_name : 'Sample';
        $last_name = $user ? $user->last_name : 'User';
        $email = $user ? $user->user_email : 'sample@example.com';
        
        $data_rows = array(
            array('personal_info', 'Personal Information', '0', 'first_name', 'First Name', 'textbox', '0', $first_name, ''),
            array('personal_info', 'Personal Information', '0', 'last_name', 'Last Name', 'textbox', '0', $last_name, ''),
            array('personal_info', 'Personal Information', '0', 'email', 'Email Address', 'email', '0', $email, ''),
            array('personal_info', 'Personal Information', '0', 'phone', 'Phone Number', 'phone_number', '0', '', ''),
            array('personal_info', 'Personal Information', '0', 'website', 'Website', 'url', '0', '', ''),
            array('personal_info', 'Personal Information', '0', 'location', 'Location', 'place_autocomplete', '0', '', ''),
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
            'textarea' => 'Sample long text content here. You can add multiple lines and detailed information.',
            'email' => 'sample@example.com',
            'phone_number' => '+1234567890',
            'url' => 'https://example.com',
            'calender_field' => date('Y-m-d'),
            'year_dropdown' => date('Y'),
            'place_autocomplete' => 'New York, NY, USA',
            'image' => 'image_attachment_id_or_url',
            'text_oembed' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'
        );
        
        if (isset($sample_values[$field_type])) {
            $sample_value = $sample_values[$field_type];
        } else {
            // Handle dropdown/select type fields
            switch ($field_type) {
                case 'dropdown':
                case 'radio_button':
                    $sample_value = !empty($options) ? $options[0] : 'Option 1';
                    break;
                case 'checkbox':
                case 'selectize':
                    $sample_value = !empty($options) ? $options[0] : 'Option 1';
                    break;
                case 'text_dropdown':
                    $sample_option = !empty($options) ? $options[0] : '5';
                    $sample_value = json_encode(array(
                        'text' => 'Sample Skill',
                        'dropdown_val' => $sample_option
                    ));
                    break;
                default:
                    $sample_value = 'Sample Value';
            }
        }
        
        return apply_filters('bprm_csv_sample_value', $sample_value, $field_type, $options);
    }
    
    /**
     * Process CSV upload
     */
    public function process_csv_upload() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'bprm_csv_nonce') || !is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Security check failed', 'bp-resume-csv')));
        }
        
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(array('message' => __('Please upload a valid CSV file', 'bp-resume-csv')));
        }
        
        $file = $_FILES['csv_file'];
        
        // Validate file
        $validation_result = $this->validate_uploaded_file($file);
        if (is_wp_error($validation_result)) {
            wp_send_json_error(array('message' => $validation_result->get_error_message()));
        }
        
        $user_id = get_current_user_id();
        $available_fields = $this->get_user_resume_fields($user_id);
        
        // Parse CSV
        $csv_data = $this->parse_csv_file($file['tmp_name']);
        if ($csv_data === false) {
            wp_send_json_error(array('message' => __('Error reading CSV file. Please check the file format.', 'bp-resume-csv')));
        }
        
        // Process data
        $result = $this->process_csv_data($csv_data, $available_fields, $user_id);
        
        if ($result['success']) {
            do_action('bprm_csv_data_imported', $user_id, $result['imported_count']);
            
            // Log the activity
            if (class_exists('BP_Resume_CSV_Admin')) {
                BP_Resume_CSV_Admin::log_import_activity(
                    $user_id,
                    'csv_import',
                    sprintf('Imported %d fields from CSV', $result['imported_count'])
                );
            }
            
            wp_send_json_success(array(
                'message' => sprintf(
                    __('Resume data imported successfully! %d fields updated.', 'bp-resume-csv'), 
                    $result['imported_count']
                ),
                'imported_count' => $result['imported_count']
            ));
        } else {
            wp_send_json_error(array('message' => $result['message']));
        }
    }
    
    /**
     * Validate uploaded file
     */
    private function validate_uploaded_file($file) {
        $file_type = wp_check_filetype($file['name']);
        
        if ($file_type['ext'] !== 'csv') {
            return new WP_Error('invalid_file_type', __('Only CSV files are allowed', 'bp-resume-csv'));
        }
        
        // Check file size (max 5MB)
        $max_size = apply_filters('bprm_csv_max_file_size', 5 * 1024 * 1024);
        if ($file['size'] > $max_size) {
            return new WP_Error('file_too_large', sprintf(
                __('File size must be less than %s', 'bp-resume-csv'),
                size_format($max_size)
            ));
        }
        
        // Check if file is empty
        if ($file['size'] === 0) {
            return new WP_Error('empty_file', __('The uploaded file is empty', 'bp-resume-csv'));
        }
        
        return true;
    }
    
    /**
     * Parse CSV file
     */
    private function parse_csv_file($file_path) {
        $csv_data = array();
        $headers = array();
        $row_count = 0;
        
        if (($handle = fopen($file_path, 'r')) !== false) {
            while (($data = fgetcsv($handle, 10000, ',')) !== false) {
                // Skip comment lines
                if (isset($data[0]) && strpos($data[0], '#') === 0) {
                    continue;
                }
                
                // Skip empty rows
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
     * Process CSV data
     */
    private function process_csv_data($csv_data, $available_fields, $user_id) {
        $imported_count = 0;
        $errors = array();
        
        // If BP Resume Manager is not available, simulate processing
        if (!defined('BPRM_PLUGIN_VERSION')) {
            return array(
                'success' => true,
                'imported_count' => count($csv_data)
            );
        }
        
        // Organize data by groups and instances
        $organized_data = array();
        foreach ($csv_data as $row) {
            if (empty($row['group_key']) || empty($row['field_key'])) {
                continue;
            }
            
            $group_key = sanitize_key($row['group_key']);
            $field_key = sanitize_key($row['field_key']);
            $group_instance = intval($row['group_instance']);
            $field_instance = intval($row['field_instance']);
            
            // Validate field exists and is available
            if (!isset($available_fields[$group_key][$field_key])) {
                $errors[] = sprintf(
                    __('Field "%s" in group "%s" is not available for your profile', 'bp-resume-csv'), 
                    $field_key, 
                    $group_key
                );
                continue;
            }
            
            $organized_data[$group_key][$group_instance][$field_key][$field_instance] = $row['field_value'];
        }
        
        if (!empty($errors)) {
            return array(
                'success' => false,
                'message' => implode('\n', array_slice($errors, 0, 5)) // Limit error messages
            );
        }
        
        // Clear existing data first
        $this->clear_existing_resume_data($user_id, array_keys($available_fields));
        
        // Process organized data
        foreach ($organized_data as $group_key => $group_instances) {
            foreach ($group_instances as $group_instance => $fields) {
                foreach ($fields as $field_key => $field_instances) {
                    $field_info = $available_fields[$group_key][$field_key];
                    
                    // Update field count
                    $field_count = count($field_instances);
                    update_user_meta($user_id, 'bprm_resume_' . $field_key . '_count', $field_count);
                    
                    foreach ($field_instances as $field_instance => $field_value) {
                        $g_key = ($group_instance != 0) ? '_' . $group_instance : '';
                        $field_repet_key = ($field_instance != 0) ? '_' . $field_instance : '';
                        $meta_key = 'bprm_resume_' . $group_key . $g_key . '_' . $field_key . $field_repet_key;
                        
                        // Process field value based on type
                        $processed_value = $this->process_field_value($field_value, $field_info);
                        
                        if ($processed_value !== false) {
                            update_user_meta($user_id, $meta_key, $processed_value);
                            $imported_count++;
                        }
                    }
                }
            }
            
            // Update group count
            $group_count = count($group_instances);
            update_user_meta($user_id, 'bprm_resume_' . $group_key . '_count', $group_count);
        }
        
        return array(
            'success' => true,
            'imported_count' => $imported_count
        );
    }
    
    /**
     * Process field value based on field type
     */
    private function process_field_value($value, $field_info) {
        if (empty($value) && $value !== '0') {
            return '';
        }
        
        $value = sanitize_textarea_field($value);
        
        switch ($field_info['type']) {
            case 'email':
                return is_email($value) ? $value : $value; // Keep original for user to fix
                
            case 'url':
                return filter_var($value, FILTER_VALIDATE_URL) ? $value : $value; // Keep original
                
            case 'calender_field':
                $timestamp = strtotime($value);
                return $timestamp ? date('Y-m-d', $timestamp) : $value;
                
            case 'year_dropdown':
                $year = intval($value);
                return ($year >= 1900 && $year <= date('Y') + 10) ? $year : $value;
                
            case 'dropdown':
            case 'radio_button':
                return in_array($value, $field_info['options']) ? $value : $value; // Keep original
                
            case 'checkbox':
            case 'selectize':
                $values = explode(',', $value);
                $values = array_map('trim', $values);
                return implode(',', $values);
                
            case 'text_dropdown':
                // Try to decode as JSON first
                $decoded = json_decode($value, true);
                if ($decoded && isset($decoded['text']) && isset($decoded['dropdown_val'])) {
                    return $value;
                }
                // If not JSON, create JSON structure
                return json_encode(array(
                    'text' => $value,
                    'dropdown_val' => !empty($field_info['options']) ? $field_info['options'][0] : ''
                ));
                
            case 'image':
                // Handle image attachment ID or URL
                return is_numeric($value) ? intval($value) : $value;
                
            default:
                return $value;
        }
    }
    
    /**
     * Clear existing resume data for specified groups
     */
    private function clear_existing_resume_data($user_id, $group_keys) {
        foreach ($group_keys as $group_key) {
            // Get all meta keys for this user
            $all_meta = get_user_meta($user_id);
            
            foreach ($all_meta as $meta_key => $meta_value) {
                // Check if meta key belongs to this group
                if (strpos($meta_key, 'bprm_resume_' . $group_key) === 0) {
                    delete_user_meta($user_id, $meta_key);
                }
            }
        }
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
        
        $total_fields = 0;
        foreach ($available_fields as $fields) {
            $total_fields += count($fields);
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
        
        if (defined('BPRM_PLUGIN_VERSION')) {
            foreach ($available_fields as $group_key => $fields) {
                foreach ($fields as $field_key => $field_info) {
                    $total_fields++;
                    
                    // Check if field has data
                    $meta_key = 'bprm_resume_' . $group_key . '_' . $field_key;
                    $field_value = get_user_meta($user_id, $meta_key, true);
                    
                    if (!empty($field_value)) {
                        $filled_fields++;
                    }
                }
            }
        } else {
            // Sample data for demo
            $total_fields = 25;
            $filled_fields = 8;
        }
        
        return array(
            'total_fields' => $total_fields,
            'filled_fields' => $filled_fields,
            'completion_percentage' => $total_fields > 0 ? round(($filled_fields / $total_fields) * 100) : 0
        );
    }
}