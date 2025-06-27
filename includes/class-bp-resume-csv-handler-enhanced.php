<?php
/**
 * Enhanced CSV Handler Class
 * 
 * File: includes/class-bp-resume-csv-handler-enhanced.php
 * 
 * Handles all CSV import/export functionality with improved field detection
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class BP_Resume_CSV_Handler_Enhanced extends BP_Resume_CSV_Handler {
    
    /**
     * Get user's available resume fields with enhanced detection
     */
    public function get_user_resume_fields($user_id) {
        // Clear any cached data first
        wp_cache_delete('bprm_resume_settings', 'options');
        wp_cache_delete('bprm_groups_settings', 'options');
        
        // Check if BP Resume Manager is available
        if (!defined('BPRM_PLUGIN_VERSION')) {
            return $this->get_sample_resume_fields();
        }
        
        // Get fresh settings data
        if (is_multisite() && is_plugin_active_for_network('bp-resume-manager/bp-resume-manager.php')) {
            $bprm_settings = get_site_option('bprm_resume_settings');
            $grp_args = get_site_option('bprm_groups_settings');
        } else {
            $bprm_settings = get_option('bprm_resume_settings');
            $grp_args = get_option('bprm_groups_settings');
        }
        
        // Debug logging
        error_log('BP Resume CSV: bprm_settings count: ' . (is_array($bprm_settings) ? count($bprm_settings) : 'not array'));
        error_log('BP Resume CSV: grp_args count: ' . (is_array($grp_args) ? count($grp_args) : 'not array'));
        
        if (empty($bprm_settings) || empty($grp_args)) {
            error_log('BP Resume CSV: Settings are empty, using sample fields');
            return $this->get_sample_resume_fields();
        }
        
        $user_meta = get_userdata($user_id);
        $user_role = $user_meta ? $user_meta->roles : array();
        $mem_type = function_exists('bp_get_member_type') ? bp_get_member_type($user_id) : '';
        
        $available_fields = array();
        
        foreach ($grp_args as $group_index => $group_info) {
            error_log('BP Resume CSV: Processing group: ' . $group_index);
            
            // Check if group should be displayed in resume
            if (!isset($group_info['resume_display']) || $group_info['resume_display'] !== 'yes') {
                error_log('BP Resume CSV: Group ' . $group_index . ' not set for resume display');
                continue;
            }
            
            // Check group availability for user
            if (!$this->check_group_availability($group_info, $user_role, $mem_type)) {
                error_log('BP Resume CSV: Group ' . $group_index . ' not available for user');
                continue;
            }
            
            // Check if group has fields
            if (!isset($bprm_settings[$group_index]) || !is_array($bprm_settings[$group_index])) {
                error_log('BP Resume CSV: Group ' . $group_index . ' has no fields');
                continue;
            }
            
            $group_field_count = 0;
            foreach ($bprm_settings[$group_index] as $field_key => $field_info) {
                // Skip identifier fields
                if ($field_key === 'bprm_identifier') {
                    continue;
                }
                
                // Check if field should be displayed
                if (!isset($field_info['display']) || $field_info['display'] !== 'yes') {
                    error_log('BP Resume CSV: Field ' . $field_key . ' not set for display');
                    continue;
                }
                
                // Ensure field has required data
                if (!isset($field_info['field_tile']) || !isset($field_info['field_type']['type'])) {
                    error_log('BP Resume CSV: Field ' . $field_key . ' missing required data');
                    continue;
                }
                
                $available_fields[$group_index][$field_key] = array(
                    'title' => $field_info['field_tile'],
                    'type' => $field_info['field_type']['type'],
                    'options' => isset($field_info['field_type']['options']) ? $field_info['field_type']['options'] : array(),
                    'repeater' => isset($field_info['repeater']) ? $field_info['repeater'] : 'no',
                    'group_name' => $group_info['g_name'],
                    'group_repeater' => isset($group_info['repeater']) ? $group_info['repeater'] : 'no',
                    'required' => isset($field_info['required']) ? $field_info['required'] : 'no',
                    'section_title' => isset($field_info['section_title']) ? $field_info['section_title'] : '',
                    'section_icon' => isset($field_info['section_icon']) ? $field_info['section_icon'] : '',
                    'appr_sec' => isset($field_info['appr_sec']) ? $field_info['appr_sec'] : ''
                );
                
                $group_field_count++;
            }
            
            error_log('BP Resume CSV: Group ' . $group_index . ' has ' . $group_field_count . ' available fields');
        }
        
        error_log('BP Resume CSV: Total available groups: ' . count($available_fields));
        
        return apply_filters('bprm_csv_available_fields', $available_fields, $user_id);
    }
    
    /**
     * Enhanced group availability check with better validation
     */
    private function check_group_availability($group_info, $user_role, $mem_type) {
        if (!isset($group_info['grp_avail'])) {
            return true; // No restrictions
        }
        
        $grp_avail = $group_info['grp_avail'];
        
        if ('user_roles' === $grp_avail) {
            $roles = isset($group_info['roles']) ? $group_info['roles'] : array('all');
            if (is_array($roles)) {
                if (in_array('all', $roles, true)) {
                    return true;
                }
                if (!empty($user_role)) {
                    $roles_result = array_intersect($roles, $user_role);
                    return !empty($roles_result);
                }
            }
            return 'all' === $roles;
        } elseif ('mem_type' === $grp_avail) {
            $mtypes = isset($group_info['mtypes']) ? $group_info['mtypes'] : array('all');
            if (is_array($mtypes)) {
                if (in_array('all', $mtypes, true)) {
                    return true;
                }
                if (!empty($mem_type)) {
                    return in_array($mem_type, $mtypes, true);
                }
            }
            return 'all' === $mtypes || empty($mem_type);
        }
        
        return true;
    }
    
    /**
     * Enhanced CSV structure generation with debug info
     */
    private function generate_csv_structure($available_fields) {
        error_log('BP Resume CSV: Generating CSV structure for ' . count($available_fields) . ' groups');
        
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
        $total_fields = 0;
        
        foreach ($available_fields as $group_key => $fields) {
            foreach ($fields as $field_key => $field_info) {
                $total_fields++;
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
                    $field_info['required'],
                    $field_info['section_title'],
                    $field_info['section_icon']
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
                        $options_text,
                        $field_info['required'],
                        $field_info['section_title'],
                        $field_info['section_icon']
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
                        $options_text,
                        $first_field_info['required'],
                        $first_field_info['section_title'],
                        $first_field_info['section_icon']
                    );
                }
            }
        }
        
        error_log('BP Resume CSV: Generated ' . count($sample_rows) . ' sample rows for ' . $total_fields . ' fields');
        
        return array(
            'headers' => apply_filters('bprm_csv_headers', $headers),
            'sample_rows' => apply_filters('bprm_csv_sample_rows', $sample_rows, $available_fields)
        );
    }
    
    /**
     * Enhanced export data generation with debug info
     */
    private function generate_export_data($available_fields, $user_id) {
        error_log('BP Resume CSV: Generating export data for user ' . $user_id);
        
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
        $exported_count = 0;
        
        // If BP Resume Manager is not available, use sample data
        if (!defined('BPRM_PLUGIN_VERSION')) {
            return $this->generate_sample_export_data($user_id);
        }
        
        foreach ($available_fields as $group_key => $fields) {
            $group_count = get_user_meta($user_id, 'bprm_resume_' . $group_key . '_count', true);
            $group_count = ($group_count != '') ? $group_count : 1;
            
            error_log('BP Resume CSV: Processing group ' . $group_key . ' with ' . $group_count . ' instances');
            
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
                        
                        // Export all fields, even empty ones, so users can see the structure
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
                            $field_info['required'],
                            $field_info['section_title'],
                            $field_info['section_icon']
                        );
                        
                        if (!empty($field_value) || $field_value === '0') {
                            $exported_count++;
                        }
                    }
                }
            }
        }
        
        error_log('BP Resume CSV: Exported ' . count($data_rows) . ' total rows, ' . $exported_count . ' with data');
        
        return array(
            'headers' => $headers,
            'data_rows' => $data_rows
        );
    }
    
    /**
     * Enhanced CSV processing with better error handling
     */
    private function process_csv_data($csv_data, $available_fields, $user_id) {
        error_log('BP Resume CSV: Processing ' . count($csv_data) . ' CSV rows for user ' . $user_id);
        
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
        $valid_rows = 0;
        
        foreach ($csv_data as $row_index => $row) {
            if (empty($row['group_key']) || empty($row['field_key'])) {
                error_log('BP Resume CSV: Row ' . $row_index . ' missing group_key or field_key');
                continue;
            }
            
            $group_key = sanitize_key($row['group_key']);
            $field_key = sanitize_key($row['field_key']);
            $group_instance = intval($row['group_instance']);
            $field_instance = intval($row['field_instance']);
            
            // Validate field exists and is available
            if (!isset($available_fields[$group_key][$field_key])) {
                $errors[] = sprintf(
                    __('Row %d: Field "%s" in group "%s" is not available for your profile', 'bp-resume-csv'), 
                    $row_index + 1,
                    $field_key, 
                    $group_key
                );
                continue;
            }
            
            $organized_data[$group_key][$group_instance][$field_key][$field_instance] = $row['field_value'];
            $valid_rows++;
        }
        
        error_log('BP Resume CSV: Processed ' . $valid_rows . ' valid rows, ' . count($errors) . ' errors');
        
        if (!empty($errors)) {
            return array(
                'success' => false,
                'message' => implode('\n', array_slice($errors, 0, 10)) // Limit error messages
            );
        }
        
        if ($valid_rows === 0) {
            return array(
                'success' => false,
                'message' => __('No valid data found in CSV file', 'bp-resume-csv')
            );
        }
        
        // Clear existing data first for groups being imported
        $this->clear_existing_resume_data($user_id, array_keys($organized_data));
        
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
                            error_log('BP Resume CSV: Imported ' . $meta_key . ' = ' . $processed_value);
                        }
                    }
                }
            }
            
            // Update group count
            $group_count = count($group_instances);
            update_user_meta($user_id, 'bprm_resume_' . $group_key . '_count', $group_count);
            error_log('BP Resume CSV: Updated ' . $group_key . ' count to ' . $group_count);
        }
        
        return array(
            'success' => true,
            'imported_count' => $imported_count
        );
    }
    
    /**
     * Debug method to show current resume settings
     */
    public function debug_resume_settings($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $available_fields = $this->get_user_resume_fields($user_id);
        
        echo '<pre>';
        echo "=== BP Resume CSV Debug Info ===\n";
        echo "User ID: " . $user_id . "\n";
        echo "Available Fields Count: " . count($available_fields) . "\n\n";
        
        foreach ($available_fields as $group_key => $fields) {
            echo "Group: " . $group_key . " (" . count($fields) . " fields)\n";
            foreach ($fields as $field_key => $field_info) {
                echo "  - " . $field_key . ": " . $field_info['title'] . " (" . $field_info['type'] . ")\n";
            }
            echo "\n";
        }
        echo '</pre>';
    }
}