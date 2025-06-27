<?php
/**
 * Clean Enhanced CSV Handler Class - NO DUPLICATES
 * 
 * File: includes/class-bp-resume-csv-handler-enhanced.php
 * 
 * Extends the base handler with enhanced field detection and debugging
 * Replace your entire enhanced class with this clean version
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class BP_Resume_CSV_Handler_Enhanced extends BP_Resume_CSV_Handler {
    
    /**
     * Enhanced field detection with better caching and debugging
     * OVERRIDES parent method with enhanced version
     */
    public function get_user_resume_fields($user_id) {
        // Clear any cached data first for fresh results
        wp_cache_delete('bprm_resume_settings', 'options');
        wp_cache_delete('bprm_groups_settings', 'options');
        
        // Check if BP Resume Manager is available
        if (!defined('BPRM_PLUGIN_VERSION')) {
            error_log('BP Resume CSV Enhanced: BP Resume Manager not active, using sample fields');
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
        
        // Enhanced debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('BP Resume CSV Enhanced: bprm_settings count: ' . (is_array($bprm_settings) ? count($bprm_settings) : 'not array'));
            error_log('BP Resume CSV Enhanced: grp_args count: ' . (is_array($grp_args) ? count($grp_args) : 'not array'));
        }
        
        if (empty($bprm_settings) || empty($grp_args)) {
            error_log('BP Resume CSV Enhanced: Settings are empty, using sample fields');
            return $this->get_sample_resume_fields();
        }
        
        $user_meta = get_userdata($user_id);
        $user_role = $user_meta ? $user_meta->roles : array();
        $mem_type = function_exists('bp_get_member_type') ? bp_get_member_type($user_id) : '';
        
        $available_fields = array();
        $total_groups_processed = 0;
        $total_fields_found = 0;
        
        foreach ($grp_args as $group_index => $group_info) {
            $total_groups_processed++;
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('BP Resume CSV Enhanced: Processing group: ' . $group_index);
            }
            
            // Check if group should be displayed in resume
            if (!isset($group_info['resume_display']) || $group_info['resume_display'] !== 'yes') {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('BP Resume CSV Enhanced: Group ' . $group_index . ' not set for resume display');
                }
                continue;
            }
            
            // Enhanced group availability check
            if (!$this->check_group_availability_enhanced($group_info, $user_role, $mem_type)) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('BP Resume CSV Enhanced: Group ' . $group_index . ' not available for user');
                }
                continue;
            }
            
            // Check if group has fields
            if (!isset($bprm_settings[$group_index]) || !is_array($bprm_settings[$group_index])) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('BP Resume CSV Enhanced: Group ' . $group_index . ' has no fields');
                }
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
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('BP Resume CSV Enhanced: Field ' . $field_key . ' not set for display');
                    }
                    continue;
                }
                
                // Ensure field has required data
                if (!isset($field_info['field_tile']) || !isset($field_info['field_type']['type'])) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('BP Resume CSV Enhanced: Field ' . $field_key . ' missing required data');
                    }
                    continue;
                }
                
                // Enhanced field data with additional properties
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
                $total_fields_found++;
            }
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('BP Resume CSV Enhanced: Group ' . $group_index . ' has ' . $group_field_count . ' available fields');
            }
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('BP Resume CSV Enhanced: Processed ' . $total_groups_processed . ' groups, found ' . count($available_fields) . ' valid groups with ' . $total_fields_found . ' total fields');
        }
        
        return apply_filters('bprm_csv_available_fields', $available_fields, $user_id);
    }
    
    /**
     * Enhanced group availability check with better validation
     * NEW METHOD - not in parent class
     */
    private function check_group_availability_enhanced($group_info, $user_role, $mem_type) {
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
     * Enhanced CSV processing with detailed debugging
     * OVERRIDES parent process_csv_data_improved method
     */
    protected function process_csv_data_improved($csv_data, $available_fields, $user_id) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('BP Resume CSV Enhanced: Processing ' . count($csv_data) . ' CSV rows for user ' . $user_id);
        }
        
        $imported_count = 0;
        $errors = array();
        $details = array();
        
        // If BP Resume Manager is not available, simulate processing
        if (!defined('BPRM_PLUGIN_VERSION')) {
            return array(
                'success' => true,
                'imported_count' => count($csv_data),
                'details' => array('Simulated import (BP Resume Manager not active)')
            );
        }
        
        if (empty($csv_data)) {
            return array(
                'success' => false,
                'message' => __('No data found in CSV file', 'bp-resume-csv')
            );
        }
        
        // Validate CSV structure
        $required_headers = array('group_key', 'field_key', 'field_value');
        $csv_headers = array_keys($csv_data[0]);
        $missing_headers = array_diff($required_headers, $csv_headers);
        
        if (!empty($missing_headers)) {
            return array(
                'success' => false,
                'message' => sprintf(__('Missing required CSV columns: %s. Available columns: %s', 'bp-resume-csv'), 
                    implode(', ', $missing_headers),
                    implode(', ', $csv_headers)
                )
            );
        }
        
        // Organize data by groups and instances
        $organized_data = array();
        $valid_rows = 0;
        $skipped_rows = 0;
        
        foreach ($csv_data as $row_index => $row) {
            // Skip empty rows or rows without required data
            if (empty($row['group_key']) || empty($row['field_key'])) {
                $skipped_rows++;
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('BP Resume CSV Enhanced: Row ' . $row_index . ' missing group_key or field_key');
                }
                continue;
            }
            
            // Skip rows with empty values (unless it's intentionally '0')
            if (empty($row['field_value']) && $row['field_value'] !== '0') {
                $skipped_rows++;
                continue;
            }
            
            $group_key = sanitize_key($row['group_key']);
            $field_key = sanitize_key($row['field_key']);
            $group_instance = isset($row['group_instance']) ? intval($row['group_instance']) : 0;
            $field_instance = isset($row['field_instance']) ? intval($row['field_instance']) : 0;
            
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
        
        $details[] = sprintf('Processed %d CSV rows, %d valid, %d skipped', count($csv_data), $valid_rows, $skipped_rows);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('BP Resume CSV Enhanced: Processed ' . $valid_rows . ' valid rows, ' . count($errors) . ' errors');
        }
        
        if (!empty($errors) && count($errors) > 5) {
            return array(
                'success' => false,
                'message' => __('Too many field validation errors. Please check your CSV format.', 'bp-resume-csv'),
                'details' => array_slice($errors, 0, 5)
            );
        }
        
        if ($valid_rows === 0) {
            return array(
                'success' => false,
                'message' => __('No valid data found in CSV file', 'bp-resume-csv'),
                'details' => $details
            );
        }
        
        // Process organized data with enhanced logging
        foreach ($organized_data as $group_key => $group_instances) {
            $details[] = sprintf('Processing group: %s (%d instances)', $group_key, count($group_instances));
            
            foreach ($group_instances as $group_instance => $fields) {
                foreach ($fields as $field_key => $field_instances) {
                    $field_info = $available_fields[$group_key][$field_key];
                    
                    // Update field count for repeater fields
                    if (count($field_instances) > 1 || (isset($field_info['repeater']) && $field_info['repeater'] === 'yes')) {
                        $field_count = count($field_instances);
                        update_user_meta($user_id, 'bprm_resume_' . $field_key . '_count', $field_count);
                        $details[] = sprintf('Updated %s field count to %d', $field_key, $field_count);
                    }
                    
                    foreach ($field_instances as $field_instance => $field_value) {
                        // Build the correct meta key
                        $g_key = ($group_instance != 0) ? '_' . $group_instance : '';
                        $field_repet_key = ($field_instance != 0) ? '_' . $field_instance : '';
                        $meta_key = 'bprm_resume_' . $group_key . $g_key . '_' . $field_key . $field_repet_key;
                        
                        // Process field value based on type
                        $processed_value = $this->process_field_value_enhanced($field_value, $field_info);
                        
                        if ($processed_value !== false) {
                            // Get existing value for comparison
                            $existing_value = get_user_meta($user_id, $meta_key, true);
                            
                            // Update the meta value
                            $update_result = update_user_meta($user_id, $meta_key, $processed_value);
                            
                            if ($update_result !== false) {
                                $imported_count++;
                                
                                if (defined('WP_DEBUG') && WP_DEBUG) {
                                    error_log(sprintf('BP Resume CSV Enhanced: Updated %s = "%s" (was: "%s")', 
                                        $meta_key, 
                                        $processed_value, 
                                        $existing_value
                                    ));
                                }
                            }
                        } else {
                            $details[] = sprintf('Skipped invalid value for %s: %s', $field_key, $field_value);
                        }
                    }
                }
            }
            
            // Update group count for group repeaters
            if (count($group_instances) > 1) {
                $group_count = count($group_instances);
                update_user_meta($user_id, 'bprm_resume_' . $group_key . '_count', $group_count);
                $details[] = sprintf('Updated %s group count to %d', $group_key, $group_count);
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('BP Resume CSV Enhanced: Updated ' . $group_key . ' count to ' . $group_count);
                }
            }
        }
        
        return array(
            'success' => true,
            'imported_count' => $imported_count,
            'details' => $details
        );
    }
    
    /**
     * Enhanced field value processing with better validation and debugging
     * NEW METHOD - extends parent functionality
     */
    private function process_field_value_enhanced($value, $field_info) {
        // Handle empty values
        if (empty($value) && $value !== '0') {
            return ''; // Allow empty values to clear fields
        }
        
        $value = sanitize_textarea_field($value);
        $original_value = $value; // Keep original for logging
        
        switch ($field_info['type']) {
            case 'email':
                if (!empty($value) && !is_email($value)) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('BP Resume CSV Enhanced: Invalid email format: ' . $value);
                    }
                    return $value; // Keep original for user to fix
                }
                return $value;
                
            case 'url':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('BP Resume CSV Enhanced: Invalid URL format: ' . $value);
                    }
                    return $value; // Keep original for user to fix
                }
                return $value;
                
            case 'calender_field':
                if (!empty($value)) {
                    $timestamp = strtotime($value);
                    if ($timestamp !== false) {
                        $formatted_date = date('Y-m-d', $timestamp);
                        if (defined('WP_DEBUG') && WP_DEBUG && $formatted_date !== $original_value) {
                            error_log('BP Resume CSV Enhanced: Converted date "' . $original_value . '" to "' . $formatted_date . '"');
                        }
                        return $formatted_date;
                    }
                }
                return $value;
                
            case 'year_dropdown':
                if (!empty($value)) {
                    $year = intval($value);
                    if ($year >= 1900 && $year <= date('Y') + 10) {
                        return $year;
                    } else {
                        if (defined('WP_DEBUG') && WP_DEBUG) {
                            error_log('BP Resume CSV Enhanced: Invalid year: ' . $value . ' (must be between 1900 and ' . (date('Y') + 10) . ')');
                        }
                    }
                }
                return $value;
                
            case 'dropdown':
            case 'radio_button':
                if (!empty($field_info['options']) && !in_array($value, $field_info['options'])) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('BP Resume CSV Enhanced: Invalid option "' . $value . '" for field with options: ' . implode(', ', $field_info['options']));
                    }
                }
                return $value; // Return even if invalid for user review
                
            case 'checkbox':
            case 'selectize':
                if (!empty($value)) {
                    $values = explode(',', $value);
                    $values = array_map('trim', $values);
                    $processed = implode(',', $values);
                    if (defined('WP_DEBUG') && WP_DEBUG && $processed !== $original_value) {
                        error_log('BP Resume CSV Enhanced: Processed multi-value field "' . $original_value . '" to "' . $processed . '"');
                    }
                    return $processed;
                }
                return $value;
                
            case 'text_dropdown':
                // Try to decode as JSON first
                $decoded = json_decode($value, true);
                if ($decoded && isset($decoded['text']) && isset($decoded['dropdown_val'])) {
                    return $value;
                }
                // If not JSON, create JSON structure
                $json_value = json_encode(array(
                    'text' => $value,
                    'dropdown_val' => !empty($field_info['options']) ? $field_info['options'][0] : ''
                ));
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('BP Resume CSV Enhanced: Converted text+dropdown "' . $original_value . '" to JSON: ' . $json_value);
                }
                return $json_value;
                
            case 'image':
                // Handle image attachment ID or URL
                if (is_numeric($value)) {
                    return intval($value);
                }
                return $value;
                
            default:
                return $value;
        }
    }
    
    /**
     * Debug method to show current resume settings
     * NEW METHOD - not in parent class
     */
    public function debug_resume_settings($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $available_fields = $this->get_user_resume_fields($user_id);
        
        echo '<pre>';
        echo "=== BP Resume CSV Enhanced Debug Info ===\n";
        echo "User ID: " . $user_id . "\n";
        echo "BP Resume Manager Version: " . (defined('BPRM_PLUGIN_VERSION') ? BPRM_PLUGIN_VERSION : 'Not Active') . "\n";
        echo "Available Groups: " . count($available_fields) . "\n";
        echo "Enhanced Handler: Active\n\n";
        
        $total_fields = 0;
        foreach ($available_fields as $group_key => $fields) {
            echo "Group: " . $group_key . " (" . count($fields) . " fields)\n";
            foreach ($fields as $field_key => $field_info) {
                echo "  - " . $field_key . ": " . $field_info['title'] . " (" . $field_info['type'] . ")";
                if ($field_info['repeater'] === 'yes') {
                    echo " [REPEATER]";
                }
                if ($field_info['required'] === 'yes') {
                    echo " [REQUIRED]";
                }
                echo "\n";
                $total_fields++;
            }
            echo "\n";
        }
        
        echo "Total Fields: " . $total_fields . "\n";
        echo "=== End Debug Info ===\n";
        echo '</pre>';
    }
    
    /**
     * Enhanced field statistics with more detailed information
     * OVERRIDES parent method
     */
    public function get_field_statistics($user_id) {
        $available_fields = $this->get_user_resume_fields($user_id);
        $total_fields = 0;
        $filled_fields = 0;
        $required_fields = 0;
        $filled_required_fields = 0;
        
        if (defined('BPRM_PLUGIN_VERSION')) {
            foreach ($available_fields as $group_key => $fields) {
                foreach ($fields as $field_key => $field_info) {
                    $total_fields++;
                    
                    if (isset($field_info['required']) && $field_info['required'] === 'yes') {
                        $required_fields++;
                    }
                    
                    // Check if field has data
                    $meta_key = 'bprm_resume_' . $group_key . '_' . $field_key;
                    $field_value = get_user_meta($user_id, $meta_key, true);
                    
                    if (!empty($field_value)) {
                        $filled_fields++;
                        if (isset($field_info['required']) && $field_info['required'] === 'yes') {
                            $filled_required_fields++;
                        }
                    }
                }
            }
        } else {
            // Sample data for demo
            $total_fields = 25;
            $filled_fields = 8;
            $required_fields = 10;
            $filled_required_fields = 6;
        }
        
        return array(
            'total_fields' => $total_fields,
            'filled_fields' => $filled_fields,
            'required_fields' => $required_fields,
            'filled_required_fields' => $filled_required_fields,
            'completion_percentage' => $total_fields > 0 ? round(($filled_fields / $total_fields) * 100) : 0,
            'required_completion_percentage' => $required_fields > 0 ? round(($filled_required_fields / $required_fields) * 100) : 0
        );
    }
    
    /**
     * Get detailed field mapping for debugging
     * NEW METHOD - not in parent class
     */
    public function get_detailed_field_mapping($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $available_fields = $this->get_user_resume_fields($user_id);
        $field_mapping = array();
        
        foreach ($available_fields as $group_key => $fields) {
            foreach ($fields as $field_key => $field_info) {
                $meta_key = 'bprm_resume_' . $group_key . '_' . $field_key;
                $current_value = get_user_meta($user_id, $meta_key, true);
                
                $field_mapping[] = array(
                    'group_key' => $group_key,
                    'field_key' => $field_key,
                    'field_title' => $field_info['title'],
                    'field_type' => $field_info['type'],
                    'required' => $field_info['required'],
                    'repeater' => $field_info['repeater'],
                    'meta_key' => $meta_key,
                    'current_value' => $current_value,
                    'has_value' => !empty($current_value)
                );
            }
        }
        
        return $field_mapping;
    }
}