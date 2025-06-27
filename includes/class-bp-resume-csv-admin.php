<?php
/**
 * CSV Admin Class
 * 
 * File: includes/class-bp-resume-csv-admin.php
 * 
 * Handles admin functionality for BP Resume CSV Import/Export plugin
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class BP_Resume_CSV_Admin {
    
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
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_filter('plugin_action_links_' . plugin_basename(BP_RESUME_CSV_PLUGIN_FILE), array($this, 'add_plugin_links'));
        add_filter('plugin_row_meta', array($this, 'add_plugin_row_meta'), 10, 2);
        add_action('admin_notices', array($this, 'show_admin_notices'));
        add_action('wp_ajax_bprm_csv_admin_action', array($this, 'handle_admin_ajax'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Add under Tools menu for easy access
        add_management_page(
            __('BP Resume CSV Import/Export', 'bp-resume-csv'),
            __('Resume CSV', 'bp-resume-csv'),
            'manage_options',
            'bp-resume-csv',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('bp_resume_csv_settings', 'bp_resume_csv_options', array(
            'sanitize_callback' => array($this, 'sanitize_settings')
        ));
        
        add_settings_section(
            'bp_resume_csv_general',
            __('General Settings', 'bp-resume-csv'),
            array($this, 'general_section_callback'),
            'bp_resume_csv_settings'
        );
        
        add_settings_field(
            'max_file_size',
            __('Maximum CSV File Size', 'bp-resume-csv'),
            array($this, 'max_file_size_callback'),
            'bp_resume_csv_settings',
            'bp_resume_csv_general'
        );
        
        add_settings_field(
            'enable_logging',
            __('Enable Import Logging', 'bp-resume-csv'),
            array($this, 'enable_logging_callback'),
            'bp_resume_csv_settings',
            'bp_resume_csv_general'
        );
        
        add_settings_field(
            'user_restrictions',
            __('User Access Restrictions', 'bp-resume-csv'),
            array($this, 'user_restrictions_callback'),
            'bp_resume_csv_settings',
            'bp_resume_csv_general'
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'bp-resume-csv') !== false) {
            wp_enqueue_style(
                'bp-resume-csv-admin',
                BP_RESUME_CSV_PLUGIN_URL . 'assets/css/admin-style.css',
                array(),
                BP_RESUME_CSV_VERSION
            );
            
            wp_enqueue_script(
                'bp-resume-csv-admin',
                BP_RESUME_CSV_PLUGIN_URL . 'assets/js/admin-script.js',
                array('jquery'),
                BP_RESUME_CSV_VERSION,
                true
            );
            
            wp_localize_script('bp-resume-csv-admin', 'bprm_csv_admin', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('bprm_csv_admin_nonce'),
                'strings' => array(
                    'confirm_reset' => __('Are you sure you want to reset all settings?', 'bp-resume-csv'),
                    'processing' => __('Processing...', 'bp-resume-csv'),
                )
            ));
        }
    }
    
    /**
     * Add plugin action links
     */
    public function add_plugin_links($links) {
        $plugin_links = array(
            '<a href="' . admin_url('tools.php?page=bp-resume-csv') . '">' . __('Settings', 'bp-resume-csv') . '</a>',
        );
        return array_merge($plugin_links, $links);
    }
    
    /**
     * Add plugin row meta
     */
    public function add_plugin_row_meta($links, $file) {
        if (plugin_basename(BP_RESUME_CSV_PLUGIN_FILE) === $file) {
            $new_links = array(
                '<a href="https://wbcomdesigns.com/support/" target="_blank">' . __('Support', 'bp-resume-csv') . '</a>',
                '<a href="https://docs.wbcomdesigns.com/" target="_blank">' . __('Documentation', 'bp-resume-csv') . '</a>',
            );
            $links = array_merge($links, $new_links);
        }
        return $links;
    }
    
    /**
     * Show admin notices
     */
    public function show_admin_notices() {
        // Check if dependencies are met
        if (!class_exists('BuddyPress')) {
            echo '<div class="notice notice-warning"><p>';
            echo __('BP Resume CSV Import/Export: BuddyPress is not active. Some features may be limited.', 'bp-resume-csv');
            echo '</p></div>';
        }
        
        if (!defined('BPRM_PLUGIN_VERSION')) {
            echo '<div class="notice notice-warning"><p>';
            echo __('BP Resume CSV Import/Export: BP Resume Manager is not active. Core functionality requires this plugin.', 'bp-resume-csv');
            echo '</p></div>';
        }
        
        // Show setup notice for new installations
        if (get_transient('bp_resume_csv_activated')) {
            delete_transient('bp_resume_csv_activated');
            echo '<div class="notice notice-success is-dismissible"><p>';
            printf(
                __('BP Resume CSV Import/Export is now active! <a href="%s">Configure settings</a>', 'bp-resume-csv'),
                admin_url('tools.php?page=bp-resume-csv')
            );
            echo '</p></div>';
        }
    }
    
    /**
     * Handle admin AJAX requests
     */
    public function handle_admin_ajax() {
        check_ajax_referer('bprm_csv_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'bp-resume-csv'));
        }
        
        $action_type = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : '';
        
        switch ($action_type) {
            case 'get_stats':
                $this->get_usage_statistics();
                break;
            case 'reset_settings':
                $this->reset_plugin_settings();
                break;
            case 'test_functionality':
                $this->test_plugin_functionality();
                break;
            default:
                wp_send_json_error(__('Invalid action', 'bp-resume-csv'));
        }
    }
    
    /**
     * Get usage statistics
     */
    private function get_usage_statistics() {
        global $wpdb;
        
        // Get users with resume data
        $users_with_resume = $wpdb->get_var(
            "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->usermeta} WHERE meta_key LIKE 'bprm_resume_%'"
        );
        
        // Get total resume fields count
        $total_resume_fields = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key LIKE 'bprm_resume_%' AND meta_key NOT LIKE '%_count'"
        );
        
        // Get CSV import logs (if logging is enabled)
        $csv_imports = get_option('bp_resume_csv_import_log', array());
        $recent_imports = array_slice($csv_imports, -10); // Last 10 imports
        
        wp_send_json_success(array(
            'users_with_resume' => intval($users_with_resume),
            'total_resume_fields' => intval($total_resume_fields),
            'recent_imports' => $recent_imports,
            'csv_imports_count' => count($csv_imports)
        ));
    }
    
    /**
     * Reset plugin settings
     */
    private function reset_plugin_settings() {
        delete_option('bp_resume_csv_options');
        delete_option('bp_resume_csv_import_log');
        
        wp_send_json_success(__('Settings reset successfully', 'bp-resume-csv'));
    }
    
    /**
     * Test plugin functionality
     */
    private function test_plugin_functionality() {
        $test_results = array();
        
        // Test 1: Check if required classes exist
        $test_results['classes'] = array(
            'BP_Resume_CSV_Handler' => class_exists('BP_Resume_CSV_Handler'),
            'BP_Resume_CSV_Admin' => class_exists('BP_Resume_CSV_Admin')
        );
        
        // Test 2: Check file permissions
        $upload_dir = wp_upload_dir();
        $test_results['file_permissions'] = is_writable($upload_dir['basedir']);
        
        // Test 3: Check dependencies
        $test_results['dependencies'] = array(
            'buddypress' => class_exists('BuddyPress'),
            'bp_resume_manager' => defined('BPRM_PLUGIN_VERSION'),
            'php_version' => version_compare(PHP_VERSION, '7.4', '>=')
        );
        
        // Test 4: Check AJAX endpoints
        $test_results['ajax_endpoints'] = array(
            'download_sample_csv' => has_action('wp_ajax_bprm_download_sample_csv'),
            'upload_csv_data' => has_action('wp_ajax_bprm_upload_csv_data'),
            'export_current_data' => has_action('wp_ajax_bprm_export_current_data')
        );
        
        wp_send_json_success($test_results);
    }
    
    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        if (isset($input['max_file_size'])) {
            $sanitized['max_file_size'] = absint($input['max_file_size']);
        }
        
        if (isset($input['enable_logging'])) {
            $sanitized['enable_logging'] = $input['enable_logging'] ? 1 : 0;
        }
        
        if (isset($input['user_restrictions'])) {
            $sanitized['user_restrictions'] = array_map('sanitize_text_field', $input['user_restrictions']);
        }
        
        return $sanitized;
    }
    
    /**
     * General section callback
     */
    public function general_section_callback() {
        echo '<p>' . __('Configure the CSV import/export functionality settings.', 'bp-resume-csv') . '</p>';
    }
    
    /**
     * Max file size callback
     */
    public function max_file_size_callback() {
        $options = get_option('bp_resume_csv_options', array());
        $value = isset($options['max_file_size']) ? $options['max_file_size'] : 5;
        
        echo '<input type="number" name="bp_resume_csv_options[max_file_size]" value="' . esc_attr($value) . '" min="1" max="50" />';
        echo '<span class="description">' . __('Maximum file size in MB (default: 5MB)', 'bp-resume-csv') . '</span>';
    }
    
    /**
     * Enable logging callback
     */
    public function enable_logging_callback() {
        $options = get_option('bp_resume_csv_options', array());
        $checked = isset($options['enable_logging']) && $options['enable_logging'];
        
        echo '<input type="checkbox" name="bp_resume_csv_options[enable_logging]" value="1" ' . checked(1, $checked, false) . ' />';
        echo '<span class="description">' . __('Log CSV import activities for debugging and analytics', 'bp-resume-csv') . '</span>';
    }
    
    /**
     * User restrictions callback
     */
    public function user_restrictions_callback() {
        $options = get_option('bp_resume_csv_options', array());
        $restrictions = isset($options['user_restrictions']) ? $options['user_restrictions'] : array();
        
        global $wp_roles;
        $roles = $wp_roles->get_names();
        
        echo '<fieldset>';
        echo '<legend class="screen-reader-text">' . __('User Access Restrictions', 'bp-resume-csv') . '</legend>';
        
        foreach ($roles as $role_key => $role_name) {
            $checked = in_array($role_key, $restrictions);
            echo '<label><input type="checkbox" name="bp_resume_csv_options[user_restrictions][]" value="' . esc_attr($role_key) . '" ' . checked(true, $checked, false) . ' /> ' . esc_html($role_name) . '</label><br>';
        }
        
        echo '<p class="description">' . __('Select user roles that should NOT have access to CSV functionality', 'bp-resume-csv') . '</p>';
        echo '</fieldset>';
    }
    
    /**
     * Admin page
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('BP Resume CSV Import/Export', 'bp-resume-csv'); ?></h1>
            
            <?php settings_errors(); ?>
            
            <div class="bp-resume-csv-admin-wrapper">
                <div class="bp-resume-csv-main-content">
                    
                    <!-- Plugin Status -->
                    <div class="postbox">
                        <h2 class="hndle"><?php _e('Plugin Status', 'bp-resume-csv'); ?></h2>
                        <div class="inside">
                            <?php $this->render_plugin_status(); ?>
                        </div>
                    </div>
                    
                    <!-- Settings Form -->
                    <div class="postbox">
                        <h2 class="hndle"><?php _e('Settings', 'bp-resume-csv'); ?></h2>
                        <div class="inside">
                            <form method="post" action="options.php">
                                <?php
                                settings_fields('bp_resume_csv_settings');
                                do_settings_sections('bp_resume_csv_settings');
                                submit_button();
                                ?>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Usage Statistics -->
                    <div class="postbox">
                        <h2 class="hndle"><?php _e('Usage Statistics', 'bp-resume-csv'); ?></h2>
                        <div class="inside">
                            <div id="usage-stats-container">
                                <p><button type="button" id="load-stats" class="button"><?php _e('Load Statistics', 'bp-resume-csv'); ?></button></p>
                            </div>
                        </div>
                    </div>
                    
                </div>
                
                <div class="bp-resume-csv-sidebar">
                    
                    <!-- Quick Actions -->
                    <div class="postbox">
                        <h2 class="hndle"><?php _e('Quick Actions', 'bp-resume-csv'); ?></h2>
                        <div class="inside">
                            <p><button type="button" id="test-functionality" class="button button-secondary"><?php _e('Test Functionality', 'bp-resume-csv'); ?></button></p>
                            <p><button type="button" id="reset-settings" class="button button-link-delete"><?php _e('Reset Settings', 'bp-resume-csv'); ?></button></p>
                        </div>
                    </div>
                    
                    <!-- About -->
                    <div class="postbox">
                        <h2 class="hndle"><?php _e('About This Plugin', 'bp-resume-csv'); ?></h2>
                        <div class="inside">
                            <p><?php printf(__('Version: %s', 'bp-resume-csv'), BP_RESUME_CSV_VERSION); ?></p>
                            <p><?php _e('This plugin adds CSV import/export functionality to BuddyPress Resume Manager.', 'bp-resume-csv'); ?></p>
                            <p>
                                <a href="https://docs.wbcomdesigns.com/" target="_blank" class="button button-small"><?php _e('Documentation', 'bp-resume-csv'); ?></a>
                                <a href="https://wbcomdesigns.com/support/" target="_blank" class="button button-small"><?php _e('Support', 'bp-resume-csv'); ?></a>
                            </p>
                        </div>
                    </div>
                    
                    <!-- System Info -->
                    <div class="postbox">
                        <h2 class="hndle"><?php _e('System Information', 'bp-resume-csv'); ?></h2>
                        <div class="inside">
                            <?php $this->render_system_info(); ?>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
        
        <style>
        .bp-resume-csv-admin-wrapper {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }
        .bp-resume-csv-main-content {
            flex: 2;
        }
        .bp-resume-csv-sidebar {
            flex: 1;
        }
        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 8px 0;
            padding: 5px 0;
        }
        .status-indicator .dashicons {
            font-size: 18px;
            width: 18px;
            height: 18px;
        }
        .status-indicator.success .dashicons {
            color: #00a32a;
        }
        .status-indicator.error .dashicons {
            color: #d63638;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 15px 0;
        }
        .stat-item {
            padding: 20px;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            text-align: center;
            transition: transform 0.2s ease;
        }
        .stat-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .stat-number {
            font-size: 28px;
            font-weight: bold;
            color: #0073aa;
            display: block;
            margin-bottom: 5px;
        }
        .stat-label {
            font-size: 14px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        @media (max-width: 1200px) {
            .bp-resume-csv-admin-wrapper {
                flex-direction: column;
            }
        }
        </style>
        <?php
    }
    
    /**
     * Render plugin status
     */
    private function render_plugin_status() {
        $status_items = array(
            array(
                'label' => __('BuddyPress', 'bp-resume-csv'),
                'status' => class_exists('BuddyPress'),
                'version' => class_exists('BuddyPress') ? bp_get_version() : null
            ),
            array(
                'label' => __('BP Resume Manager', 'bp-resume-csv'),
                'status' => defined('BPRM_PLUGIN_VERSION'),
                'version' => defined('BPRM_PLUGIN_VERSION') ? BPRM_PLUGIN_VERSION : null
            ),
            array(
                'label' => __('PHP Version', 'bp-resume-csv'),
                'status' => version_compare(PHP_VERSION, '7.4', '>='),
                'version' => PHP_VERSION
            ),
            array(
                'label' => __('WordPress Version', 'bp-resume-csv'),
                'status' => version_compare(get_bloginfo('version'), '5.0', '>='),
                'version' => get_bloginfo('version')
            )
        );
        
        foreach ($status_items as $item) {
            $icon = $item['status'] ? 'yes-alt' : 'dismiss';
            $class = $item['status'] ? 'success' : 'error';
            
            echo '<div class="status-indicator ' . $class . '">';
            echo '<span class="dashicons dashicons-' . $icon . '"></span>';
            echo '<span>' . $item['label'];
            if ($item['version']) {
                echo ' (v' . $item['version'] . ')';
            }
            echo '</span>';
            echo '</div>';
        }
    }
    
    /**
     * Render system information
     */
    private function render_system_info() {
        $upload_dir = wp_upload_dir();
        
        $system_info = array(
            __('WordPress Version', 'bp-resume-csv') => get_bloginfo('version'),
            __('PHP Version', 'bp-resume-csv') => PHP_VERSION,
            __('MySQL Version', 'bp-resume-csv') => $GLOBALS['wpdb']->db_version(),
            __('Upload Directory Writable', 'bp-resume-csv') => is_writable($upload_dir['basedir']) ? __('Yes', 'bp-resume-csv') : __('No', 'bp-resume-csv'),
            __('Memory Limit', 'bp-resume-csv') => ini_get('memory_limit'),
            __('Max Upload Size', 'bp-resume-csv') => size_format(wp_max_upload_size()),
            __('Max Post Size', 'bp-resume-csv') => ini_get('post_max_size'),
        );
        
        echo '<div class="system-info">';
        echo '<ul>';
        foreach ($system_info as $label => $value) {
            echo '<li><strong>' . $label . ':</strong> ' . $value . '</li>';
        }
        echo '</ul>';
        echo '</div>';
    }
    
    /**
     * Log CSV import activity
     */
    public static function log_import_activity($user_id, $action, $details = '') {
        $options = get_option('bp_resume_csv_options', array());
        
        if (!isset($options['enable_logging']) || !$options['enable_logging']) {
            return;
        }
        
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'user_id' => $user_id,
            'action' => $action,
            'details' => $details,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        );
        
        $log = get_option('bp_resume_csv_import_log', array());
        $log[] = $log_entry;
        
        // Keep only last 100 entries
        if (count($log) > 100) {
            $log = array_slice($log, -100);
        }
        
        update_option('bp_resume_csv_import_log', $log);
    }
    
    /**
     * Get plugin statistics
     */
    public static function get_plugin_statistics() {
        global $wpdb;
        
        return array(
            'total_users' => count_users()['total_users'],
            'users_with_resume' => $wpdb->get_var(
                "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->usermeta} WHERE meta_key LIKE 'bprm_resume_%'"
            ),
            'total_imports' => count(get_option('bp_resume_csv_import_log', array())),
            'plugin_version' => BP_RESUME_CSV_VERSION
        );
    }
}