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
        add_action('bp_before_profile_edit_content', array($this, 'add_csv_interface'));
        add_action('bp_template_content', array($this, 'add_csv_tab_content'));
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        if (bp_is_user_profile() && bp_is_current_component('resume')) {
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
     * Add CSV interface to resume page
     */
    public function add_csv_interface() {
        if (bp_is_current_component('resume') && (bp_is_current_action('edit') || bp_is_current_action('csv-import'))) {
            $this->render_csv_interface();
        }
    }
    
    /**
     * Add CSV tab content
     */
    public function add_csv_tab_content() {
        if (bp_is_current_component('resume') && bp_is_current_action('csv-import')) {
            $this->render_csv_interface();
        }
    }
    
    /**
     * Get user's available resume fields
     */
    public function get_user_resume_fields($user_id) {
        if (is_multisite() && is_plugin_active_for_network('bp-resume-manager/bp-resume-manager.php')) {
            $bprm_settings = get_site_option('bprm_resume_settings');
            $grp_args = get_site_option('bprm_groups_settings');
        } else {
            $bprm_settings = get_option('bprm_resume_settings');
            $grp_args = get_option('bprm_groups_settings');
        }
        
        if (empty($bprm_settings) || empty($grp_args)) {
            return array();
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
        if (!wp_verify_nonce($_POST['nonce'], 'bprm_csv_nonce') || !is_user_logged_in()) {
            wp_die(__('Security check failed', 'bp-resume-csv'));
        }
        
        $user_id = get_current_user_id();
        $available_fields = $this->get_user_resume_fields($user_id);
        
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
        if (!wp_verify_nonce($_POST['nonce'], 'bprm_csv_nonce') || !is_user_logged_in()) {
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
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
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
        if (!wp_verify_nonce($_POST['nonce'], 'bprm_csv_nonce') || !is_user_logged_in()) {
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
        
        return array(
            'total_fields' => $total_fields,
            'filled_fields' => $filled_fields,
            'completion_percentage' => $total_fields > 0 ? round(($filled_fields / $total_fields) * 100) : 0
        );
    }
}