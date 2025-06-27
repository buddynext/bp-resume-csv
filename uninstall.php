<?php
/**
 * Uninstall script for BP Resume CSV Import/Export
 * 
 * File: uninstall.php
 * 
 * Only runs when plugin is deleted (not deactivated)
 */

// Exit if not called from WordPress
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Clean up plugin data
 */

// Clean up plugin options
delete_option('bp_resume_csv_options');
delete_option('bp_resume_csv_import_log');

// Clean up transients
delete_transient('bp_resume_csv_activated');

// Clean up site options (for multisite)
if (is_multisite()) {
    delete_site_option('bp_resume_csv_options');
    delete_site_option('bp_resume_csv_import_log');
}

// Clean up upload directory
$upload_dir = wp_upload_dir();
$plugin_upload_dir = $upload_dir['basedir'] . '/bp-resume-csv/';

if (file_exists($plugin_upload_dir)) {
    // Remove all files in directory
    $files = glob($plugin_upload_dir . '*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    
    // Remove the directory itself
    if (is_dir($plugin_upload_dir)) {
        rmdir($plugin_upload_dir);
    }
}

// Clean up any scheduled events
wp_clear_scheduled_hook('bprm_csv_cleanup_logs');

// Clean up user meta (only CSV-specific meta, not resume data)
global $wpdb;

// Remove any CSV-specific user meta
$wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'bp_resume_csv_%'");

// Note: We don't delete actual resume data (bprm_resume_*) as that belongs to BP Resume Manager
// We only clean up data specifically created by this CSV plugin

/**
 * Optional: Remove capabilities if any were added
 */
// remove_cap('administrator', 'manage_resume_csv');
// remove_cap('editor', 'manage_resume_csv');

/**
 * Clear any caches
 */
if (function_exists('wp_cache_flush')) {
    wp_cache_flush();
}

// Log uninstall for debugging (optional)
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('BP Resume CSV Import/Export plugin has been uninstalled and cleaned up.');
}