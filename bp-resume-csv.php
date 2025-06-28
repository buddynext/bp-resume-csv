<?php
/**
 * Plugin Name: BP Resume CSV Import/Export
 * Plugin URI: https://wbcomdesigns.com/
 * Description: CSV Import/Export functionality for BuddyPress Resume Manager. Allows users to download sample CSV templates and upload resume data in bulk.
 * Version: 1.0.0
 * Author: Wbcom Designs
 * Author URI: https://wbcomdesigns.com/
 * Text Domain: bp-resume-csv
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('BP_RESUME_CSV_VERSION', '1.0.0');
define('BP_RESUME_CSV_PLUGIN_FILE', __FILE__);
define('BP_RESUME_CSV_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('BP_RESUME_CSV_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Main plugin class
 */
class BP_Resume_CSV_Plugin {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * Get single instance
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'), 15);
        register_activation_hook(__FILE__, array($this, 'on_activation'));
        register_deactivation_hook(__FILE__, array($this, 'on_deactivation'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Check if required plugins are active
        if (!$this->check_dependencies()) {
            add_action('admin_notices', array($this, 'dependency_notice'));
            return;
        }
        
        // Load plugin files
        $this->load_files();
        
        // Initialize components
        $this->init_components();
        
        // Load textdomain
        load_plugin_textdomain('bp-resume-csv', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Hook into BP Resume Manager
        $this->integrate_with_bp_resume();
        
        // Load enhanced functionality
        $this->load_enhanced_functionality();
    }
    
    /**
     * Check plugin dependencies
     */
    private function check_dependencies() {
        // For now, just return true to allow activation
        // You can enable this check later when BP Resume Manager is installed
        return true;
        
        // Uncomment these lines when you want to enforce dependencies:
        // $buddypress_active = class_exists('BuddyPress');
        // $bp_resume_active = defined('BPRM_PLUGIN_VERSION');
        // return $buddypress_active && $bp_resume_active;
    }
    
    /**
     * Load required files
     */
    private function load_files() {
        // Load main classes
        require_once BP_RESUME_CSV_PLUGIN_PATH . 'includes/class-bp-resume-csv-handler.php';
        
        // Load additional helper files if they exist
        if (file_exists(BP_RESUME_CSV_PLUGIN_PATH . 'includes/helpers.php')) {
            require_once BP_RESUME_CSV_PLUGIN_PATH . 'includes/helpers.php';
        }
    }
    
    /**
     * Initialize components
     */
    private function init_components() {
        // Initialize main handler
        new BP_Resume_CSV_Handler();
    }
    
    /**
     * Load enhanced functionality
     */
    private function load_enhanced_functionality() {
        // Load enhanced handler if BP Resume Manager is available
        if (defined('BPRM_PLUGIN_VERSION') && file_exists(BP_RESUME_CSV_PLUGIN_PATH . 'includes/class-bp-resume-csv-handler-enhanced.php')) {
            require_once BP_RESUME_CSV_PLUGIN_PATH . 'includes/class-bp-resume-csv-handler-enhanced.php';
        }
        
        // Add cache clearing functionality
        add_action('update_option_bprm_resume_settings', array($this, 'clear_field_cache'));
        add_action('update_option_bprm_groups_settings', array($this, 'clear_field_cache'));
        add_action('update_site_option_bprm_resume_settings', array($this, 'clear_field_cache'));
        add_action('update_site_option_bprm_groups_settings', array($this, 'clear_field_cache'));
    }
    
    /**
     * Integrate with BP Resume Manager
     */
    private function integrate_with_bp_resume() {
        // Add CSV tab to resume navigation
        add_action('bp_setup_nav', array($this, 'add_csv_nav_item'), 100);
        
        // Hook into resume data save events
        add_action('bprm_resume_data_saved', array($this, 'on_resume_data_saved'), 10, 2);
    }
    
    /**
     * Add CSV navigation item
     */
    public function add_csv_nav_item() {
        if (!bp_is_user() || !function_exists('bp_is_current_component')) {
            return;
        }
        
        global $bp;
        
        // Get resume tab slug from BP Resume Manager settings
        $bprm_settings = get_option('bprm_settings', array());
        $resume_slug = isset($bprm_settings['tab_url']) ? $bprm_settings['tab_url'] : 'resume';
        
        if (bp_is_current_component($resume_slug)) {
            $resume_url = trailingslashit(bp_displayed_user_domain() . $resume_slug);
            
            bp_core_new_subnav_item(array(
                'name' => __('CSV Import/Export', 'bp-resume-csv'),
                'slug' => 'csv-import',
                'parent_url' => $resume_url,
                'parent_slug' => $resume_slug,
                'screen_function' => array($this, 'csv_import_screen'),
                'position' => 100,
                'user_has_access' => bp_is_my_profile() || current_user_can('manage_options')
            ));
        }
    }
    
    /**
     * CSV import screen function
     */
    public function csv_import_screen() {
        add_action('bp_template_content', array($this, 'csv_import_content'));
        bp_core_load_template('buddypress/members/single/plugins');
    }
    
    /**
     * CSV import content
     */
    public function csv_import_content() {
        // Ensure scripts are enqueued for this specific page
        $this->enqueue_csv_scripts();
        
        // Use enhanced handler if available
        if (class_exists('BP_Resume_CSV_Handler_Enhanced')) {
            $csv_handler = new BP_Resume_CSV_Handler_Enhanced();
        } else {
            $csv_handler = new BP_Resume_CSV_Handler();
        }
        
        // Check if we should use enhanced template
        if (file_exists(BP_RESUME_CSV_PLUGIN_PATH . 'templates/csv-interface-enhanced.php')) {
            include BP_RESUME_CSV_PLUGIN_PATH . 'templates/csv-interface-enhanced.php';
        } else {
            $csv_handler->render_csv_interface();
        }
    }
    
    /**
     * Ensure CSV scripts are enqueued
     */
    private function enqueue_csv_scripts() {
        // Force enqueue scripts if not already done
        if (!wp_script_is('bp-resume-csv-handler', 'enqueued')) {
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
     * Clear field cache when resume settings are updated
     */
    public function clear_field_cache() {
        wp_cache_delete('bprm_resume_settings', 'options');
        wp_cache_delete('bprm_groups_settings', 'options');
        
        // Clear transients if any
        delete_transient('bp_resume_csv_fields_cache');
    }
    
    /**
     * Handle resume data saved event
     */
    public function on_resume_data_saved($user_id, $data_type) {
        // Log activity if needed (simplified)
        do_action('bprm_csv_data_saved', $user_id, $data_type);
    }
    
    /**
     * On activation
     */
    public function on_activation() {
        // Set activation flag for admin notice
        set_transient('bp_resume_csv_activated', true, 30);
        
        // Create default options
        $default_options = array(
            'max_file_size' => 5, // 5MB
            'enable_logging' => 1,
            'user_restrictions' => array()
        );
        
        add_option('bp_resume_csv_options', $default_options);
        
        // Create directories if needed
        $this->create_plugin_directories();
        
        // Clear any relevant caches
        wp_cache_flush();
    }
    
    /**
     * On deactivation
     */
    public function on_deactivation() {
        // Clear transients
        delete_transient('bp_resume_csv_activated');
        
        // Clear any caches
        wp_cache_flush();
    }
    
    /**
     * Create plugin directories
     */
    private function create_plugin_directories() {
        $upload_dir = wp_upload_dir();
        $plugin_upload_dir = $upload_dir['basedir'] . '/bp-resume-csv/';
        
        if (!file_exists($plugin_upload_dir)) {
            wp_mkdir_p($plugin_upload_dir);
            
            // Create .htaccess for security
            $htaccess_content = "Options -Indexes\n";
            $htaccess_content .= "<Files *.csv>\n";
            $htaccess_content .= "Order allow,deny\n";
            $htaccess_content .= "Deny from all\n";
            $htaccess_content .= "</Files>\n";
            
            file_put_contents($plugin_upload_dir . '.htaccess', $htaccess_content);
        }
    }
    
    /**
     * Admin notice for missing dependencies
     */
    public function dependency_notice() {
        // Only show notice if BuddyPress or BP Resume Manager is actually missing
        $missing = array();
        
        if (!class_exists('BuddyPress')) {
            $missing[] = 'BuddyPress is not active';
        }
        
        if (!defined('BPRM_PLUGIN_VERSION')) {
            $missing[] = 'BP Resume Manager is not active';
        }
        
        if (!empty($missing)) {
            ?>
            <div class="notice notice-info is-dismissible">
                <p>
                    <strong>BP Resume CSV Import/Export:</strong> 
                    <?php echo implode(' and ', $missing); ?>. 
                    Some features may be limited until required plugins are activated.
                </p>
            </div>
            <?php
        }
    }
    
    /**
     * Get plugin settings
     */
    public static function get_settings() {
        return get_option('bp_resume_csv_options', array(
            'max_file_size' => 5,
            'enable_logging' => 1,
            'user_restrictions' => array()
        ));
    }
    
    /**
     * Check if user has access to CSV functionality
     */
    public static function user_has_csv_access($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return false;
        }
        
        $settings = self::get_settings();
        $restrictions = isset($settings['user_restrictions']) ? $settings['user_restrictions'] : array();
        
        if (empty($restrictions)) {
            return true;
        }
        
        $user = get_userdata($user_id);
        $user_roles = $user->roles;
        
        // Check if any user role is restricted
        foreach ($user_roles as $role) {
            if (in_array($role, $restrictions)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get max upload size based on settings
     */
    public static function get_max_upload_size() {
        $settings = self::get_settings();
        $max_size = isset($settings['max_file_size']) ? $settings['max_file_size'] : 5;
        
        // Convert to bytes
        return $max_size * 1024 * 1024;
    }
}

/**
 * Initialize the plugin
 */
function bp_resume_csv_init() {
    return BP_Resume_CSV_Plugin::get_instance();
}

// Initialize the plugin
bp_resume_csv_init();

/**
 * Additional integration hooks and filters
 */

/**
 * Add CSV integration notice to resume edit page (NOTICE ONLY, NOT THE INTERFACE)
 */
function bp_resume_csv_add_edit_notice() {
    // Only show notice on edit page, NOT the csv-import page
    if (bp_is_current_component('resume') && bp_is_current_action('edit')) {
        echo '<div class="bprm-csv-integration-notice">';
        echo '<p><strong>' . __('Tip:', 'bp-resume-csv') . '</strong> ';
        printf(
            __('You can also <a href="%s">import/export your resume data via CSV</a> for bulk editing.', 'bp-resume-csv'),
            bp_displayed_user_domain() . 'resume/csv-import/'
        );
        echo '</p>';
        echo '</div>';
    }
}
add_action('bp_before_profile_edit_content', 'bp_resume_csv_add_edit_notice');

/**
 * Process CSV field value filter
 */
function bp_resume_csv_process_field_value($value, $field_type, $field_info) {
    // Custom processing for specific field types can be added here
    return $value;
}
add_filter('bprm_csv_process_field_value', 'bp_resume_csv_process_field_value', 10, 3);

/**
 * Handle CSV import completion
 */
function bp_resume_csv_data_imported($user_id, $imported_count) {
    // Send notification email to user if desired
    $user = get_userdata($user_id);
    if ($user && $user->user_email) {
        $subject = __('Resume Data Import Completed', 'bp-resume-csv');
        $message = sprintf(
            __('Your resume data has been successfully imported. %d fields were updated.', 'bp-resume-csv'),
            $imported_count
        );
        
        wp_mail($user->user_email, $subject, $message);
    }
}
add_action('bprm_csv_data_imported', 'bp_resume_csv_data_imported', 10, 2);

/**
 * Add CSV export link to resume display (LINK ONLY, NOT THE INTERFACE)
 */
function bp_resume_csv_add_export_link() {
    // Only show link on view page, NOT on csv-import page
    if (bp_is_current_component('resume') && bp_is_current_action('view')) {
        if (bp_is_my_profile() && BP_Resume_CSV_Plugin::user_has_csv_access()) {
            echo '<div class="bprm-csv-export-link">';
            echo '<p><a href="' . bp_displayed_user_domain() . 'resume/csv-import/" class="button">';
            echo __('Import/Export Resume Data', 'bp-resume-csv');
            echo '</a></p>';
            echo '</div>';
        }
    }
}
add_action('bp_before_profile_loop_content', 'bp_resume_csv_add_export_link');

/**
 * Enqueue admin styles for integration notice
 */
function bp_resume_csv_add_styles() {
    if (bp_is_user_profile() && bp_is_current_component('resume')) {
        ?>
        <style>
        .bprm-csv-integration-notice {
            background: #e8f4fd;
            border: 1px solid #b8daff;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .bprm-csv-integration-notice p {
            margin: 0;
        }
        .bprm-csv-export-link {
            margin: 15px 0;
            padding: 15px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            text-align: center;
        }
        </style>
        <?php
    }
}
add_action('wp_head', 'bp_resume_csv_add_styles');

/**
 * Uninstall cleanup function
 */
function bp_resume_csv_uninstall_cleanup() {
    // Clean up options
    delete_option('bp_resume_csv_options');
    delete_option('bp_resume_csv_import_log');
    
    // Clean up transients
    delete_transient('bp_resume_csv_activated');
    
    // Clean up upload directory
    $upload_dir = wp_upload_dir();
    $plugin_upload_dir = $upload_dir['basedir'] . '/bp-resume-csv/';
    
    if (file_exists($plugin_upload_dir)) {
        // Remove files
        $files = glob($plugin_upload_dir . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        
        // Remove directory
        rmdir($plugin_upload_dir);
    }
}

// Register uninstall hook with named function
register_uninstall_hook(__FILE__, 'bp_resume_csv_uninstall_cleanup');