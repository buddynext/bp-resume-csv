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

// Clean up plugin options
delete_option('bp_resume_csv_options');
delete_option('bp_resume_csv_import_log');

// Clean up transients
delete_transient('bp_resume_csv_activated');

// Note: We don't delete user resume data as that belongs to BP Resume Manager
// We only clean up data specifically created by this CSV plugin