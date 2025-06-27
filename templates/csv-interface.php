<?php
/**
 * Enhanced CSV Interface Template
 * 
 * File: templates/csv-interface-enhanced.php
 * 
 * Template for CSV import/export interface with enhanced field detection
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$csv_handler = new BP_Resume_CSV_Handler_Enhanced();
$available_fields = $csv_handler->get_user_resume_fields($user_id);
$field_stats = $csv_handler->get_field_statistics($user_id);

$total_fields = 0;
foreach ($available_fields as $fields) {
    $total_fields += count($fields);
}

// Debug mode check
$debug_mode = isset($_GET['csv_debug']) && current_user_can('manage_options');
?>

<div class="bp-resume-csv-interface">
    
    <!-- Debug Information (Admin Only) -->
    <?php if ($debug_mode): ?>
    <div class="csv-debug-panel" style="background: #f0f8ff; border: 1px solid #0073aa; padding: 20px; margin-bottom: 20px; border-radius: 8px;">
        <h3 style="margin-top: 0; color: #0073aa;">🔧 Debug Information</h3>
        <div style="font-family: monospace; font-size: 12px;">
            <p><strong>Plugin Status:</strong></p>
            <ul>
                <li>BP Resume Manager: <?php echo defined('BPRM_PLUGIN_VERSION') ? '✓ Active (v' . BPRM_PLUGIN_VERSION . ')' : '✗ Not found'; ?></li>
                <li>CSV Plugin: <?php echo defined('BP_RESUME_CSV_VERSION') ? '✓ Active (v' . BP_RESUME_CSV_VERSION . ')' : '✗ Not found'; ?></li>
                <li>User ID: <?php echo $user_id; ?></li>
                <li>Available Groups: <?php echo count($available_fields); ?></li>
                <li>Total Fields: <?php echo $total_fields; ?></li>
            </ul>
            
            <?php if (!empty($available_fields)): ?>
            <details style="margin-top: 15px;">
                <summary style="cursor: pointer; font-weight: bold;">📋 Field Structure</summary>
                <div style="margin-top: 10px; background: white; padding: 10px; border-radius: 4px;">
                    <?php foreach ($available_fields as $group_key => $fields): ?>
                    <h4 style="margin: 10px 0 5px 0; color: #666;">Group: <?php echo esc_html($group_key); ?> (<?php echo count($fields); ?> fields)</h4>
                    <ul style="margin: 0 0 15px 20px;">
                        <?php foreach ($fields as $field_key => $field_info): ?>
                        <li>
                            <strong><?php echo esc_html($field_key); ?>:</strong> 
                            <?php echo esc_html($field_info['title']); ?> 
                            (<?php echo esc_html($field_info['type']); ?>)
                            <?php if ($field_info['repeater'] === 'yes'): ?>
                                <span style="color: #d63638;">[Repeater]</span>
                            <?php endif; ?>
                            <?php if ($field_info['required'] === 'yes'): ?>
                                <span style="color: #d63638;">[Required]</span>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endforeach; ?>
                </div>
            </details>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Header -->
    <div class="csv-interface-header">
        <div class="csv-header-content">
            <h2><?php _e('Resume Data Import/Export', 'bp-resume-csv'); ?></h2>
            <p class="csv-description">
                <?php _e('Import and export your resume data using CSV files. This allows you to backup your data or bulk edit your resume information.', 'bp-resume-csv'); ?>
            </p>
        </div>
        
        <!-- Statistics -->
        <div class="csv-stats-grid">
            <div class="csv-stat-item">
                <div class="stat-number"><?php echo $total_fields; ?></div>
                <div class="stat-label"><?php _e('Available Fields', 'bp-resume-csv'); ?></div>
            </div>
            <div class="csv-stat-item">
                <div class="stat-number"><?php echo $field_stats['filled_fields']; ?></div>
                <div class="stat-label"><?php _e('Filled Fields', 'bp-resume-csv'); ?></div>
            </div>
            <div class="csv-stat-item">
                <div class="stat-number"><?php echo $field_stats['completion_percentage']; ?>%</div>
                <div class="stat-label"><?php _e('Complete', 'bp-resume-csv'); ?></div>
            </div>
        </div>
    </div>

    <!-- Messages Container -->
    <div id="bprm-csv-messages" class="csv-messages-container" style="display: none;"></div>

    <?php if (empty($available_fields)): ?>
    <!-- No Fields Available -->
    <div class="csv-no-fields-message">
        <div class="no-fields-icon">📋</div>
        <h3><?php _e('No Resume Fields Available', 'bp-resume-csv'); ?></h3>
        <p><?php _e('No resume fields are currently configured for CSV import/export. Please contact your site administrator to set up resume fields.', 'bp-resume-csv'); ?></p>
        
        <?php if (current_user_can('manage_options')): ?>
        <div class="admin-help">
            <p><strong><?php _e('Administrator Note:', 'bp-resume-csv'); ?></strong></p>
            <ul>
                <li><?php _e('Ensure BP Resume Manager is active and configured', 'bp-resume-csv'); ?></li>
                <li><?php _e('Create resume field groups and fields in the admin panel', 'bp-resume-csv'); ?></li>
                <li><?php _e('Make sure fields are set to "Display" and groups are enabled for "Resume Display"', 'bp-resume-csv'); ?></li>
                <li><a href="<?php echo admin_url('admin.php?page=bp_resume_manager'); ?>"><?php _e('Go to Resume Manager Settings', 'bp-resume-csv'); ?></a></li>
            </ul>
        </div>
        <?php endif; ?>
    </div>
    
    <?php else: ?>
    <!-- Main Interface -->
    <div class="csv-interface-content">
        
        <!-- Export Section -->
        <div class="csv-section csv-export-section">
            <div class="section-header">
                <h3><span class="section-icon">📤</span> <?php _e('Export Resume Data', 'bp-resume-csv'); ?></h3>
                <p><?php _e('Download your current resume data or get a template file for importing data.', 'bp-resume-csv'); ?></p>
            </div>
            
            <div class="export-options">
                <div class="export-option">
                    <div class="option-content">
                        <h4><?php _e('Download Template', 'bp-resume-csv'); ?></h4>
                        <p><?php _e('Get a CSV template file with all available fields and sample data to help you format your import data correctly.', 'bp-resume-csv'); ?></p>
                        <div class="option-meta">
                            <span class="field-count"><?php echo $total_fields; ?> <?php _e('fields available', 'bp-resume-csv'); ?></span>
                        </div>
                    </div>
                    <button type="button" id="bprm-download-template" class="csv-button csv-button-secondary">
                        <span class="button-icon">📋</span>
                        <?php _e('Download Template', 'bp-resume-csv'); ?>
                    </button>
                </div>
                
                <div class="export-option">
                    <div class="option-content">
                        <h4><?php _e('Export Current Data', 'bp-resume-csv'); ?></h4>
                        <p><?php _e('Download your current resume data as a CSV file. This can be used as a backup or to edit your data in a spreadsheet.', 'bp-resume-csv'); ?></p>
                        <div class="option-meta">
                            <span class="field-count"><?php echo $field_stats['filled_fields']; ?> <?php _e('fields with data', 'bp-resume-csv'); ?></span>
                        </div>
                    </div>
                    <button type="button" id="bprm-export-data" class="csv-button csv-button-primary">
                        <span class="button-icon">💾</span>
                        <?php _e('Export Data', 'bp-resume-csv'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Import Section -->
        <div class="csv-section csv-import-section">
            <div class="section-header">
                <h3><span class="section-icon">📥</span> <?php _e('Import Resume Data', 'bp-resume-csv'); ?></h3>
                <p><?php _e('Upload a CSV file to import or update your resume data. Make sure to download the template first to see the correct format.', 'bp-resume-csv'); ?></p>
            </div>
            
            <!-- Important Notice -->
            <div class="import-notice">
                <div class="notice-icon">⚠️</div>
                <div class="notice-content">
                    <h4><?php _e('Important:', 'bp-resume-csv'); ?></h4>
                    <p><?php _e('Importing data will replace your existing resume information. Make sure to export your current data first if you want to keep a backup.', 'bp-resume-csv'); ?></p>
                </div>
            </div>
            
            <!-- Upload Form -->
            <form id="bprm-csv-upload-form" enctype="multipart/form-data">
                <div class="file-upload-section">
                    <div id="file-drop-zone" class="file-drop-zone">
                        <div class="file-upload-content">
                            <div class="upload-icon">📁</div>
                            <h4><?php _e('Choose CSV File', 'bp-resume-csv'); ?></h4>
                            <p><?php _e('Drag and drop your CSV file here, or click to browse', 'bp-resume-csv'); ?></p>
                            <input type="file" id="bprm-csv-file" name="csv_file" accept=".csv" style="display: none;">
                            <button type="button" class="csv-button csv-button-outline" onclick="document.getElementById('bprm-csv-file').click();">
                                <?php _e('Browse Files', 'bp-resume-csv'); ?>
                            </button>
                            <div class="file-requirements">
                                <small>
                                    <?php printf(__('Maximum file size: %s | Supported format: CSV', 'bp-resume-csv'), size_format(BP_Resume_CSV_Plugin::get_max_upload_size())); ?>
                                </small>
                            </div>
                        </div>
                        
                        <div class="file-selected-info" style="display: none;">
                            <div class="selected-file-icon">📄</div>
                            <div class="selected-file-details">
                                <h4 class="file-name"></h4>
                                <p class="file-size"></p>
                            </div>
                            <button type="button" class="file-remove" title="<?php _e('Remove file', 'bp-resume-csv'); ?>">×</button>
                        </div>
                    </div>
                </div>
                
                <div class="import-actions">
                    <button type="submit" class="csv-button csv-button-primary csv-button-large">
                        <span class="button-icon">🚀</span>
                        <?php _e('Import Data', 'bp-resume-csv'); ?>
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Field Information -->
        <div class="csv-section csv-fields-info">
            <div class="section-header">
                <h3><span class="section-icon">📊</span> <?php _e('Available Fields', 'bp-resume-csv'); ?></h3>
                <p><?php _e('These are the resume fields available for import/export based on your profile settings.', 'bp-resume-csv'); ?></p>
            </div>
            
            <div class="fields-grid">
                <?php foreach ($available_fields as $group_key => $fields): ?>
                <div class="field-group">
                    <h4 class="group-title">
                        <?php echo esc_html($fields[array_key_first($fields)]['group_name']); ?>
                        <span class="field-count">(<?php echo count($fields); ?> <?php _e('fields', 'bp-resume-csv'); ?>)</span>
                    </h4>
                    <ul class="field-list">
                        <?php foreach ($fields as $field_key => $field_info): ?>
                        <li class="field-item">
                            <span class="field-name"><?php echo esc_html($field_info['title']); ?></span>
                            <span class="field-type"><?php echo esc_html($field_info['type']); ?></span>
                            <?php if ($field_info['repeater'] === 'yes'): ?>
                                <span class="field-badge repeater"><?php _e('Repeater', 'bp-resume-csv'); ?></span>
                            <?php endif; ?>
                            <?php if ($field_info['required'] === 'yes'): ?>
                                <span class="field-badge required"><?php _e('Required', 'bp-resume-csv'); ?></span>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
    </div>
    <?php endif; ?>
    
    <!-- Footer -->
    <div class="csv-interface-footer">
        <div class="footer-links">
            <a href="https://docs.wbcomdesigns.com/" target="_blank"><?php _e('Documentation', 'bp-resume-csv'); ?></a>
            <a href="https://wbcomdesigns.com/support/" target="_blank"><?php _e('Support', 'bp-resume-csv'); ?></a>
            <?php if (current_user_can('manage_options')): ?>
            <a href="<?php echo add_query_arg('csv_debug', '1'); ?>"><?php _e('Debug Mode', 'bp-resume-csv'); ?></a>
            <?php endif; ?>
        </div>
    </div>
    
</div>

<style>
.bp-resume-csv-interface {
    max-width: 1200px;
    margin: 0 auto;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

.csv-interface-header {
    text-align: center;
    margin-bottom: 40px;
    padding: 40px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    color: white;
}

.csv-header-content h2 {
    margin: 0 0 10px 0;
    font-size: 32px;
    font-weight: 700;
}

.csv-description {
    font-size: 18px;
    opacity: 0.9;
    margin: 0 0 30px 0;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

.csv-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 20px;
    max-width: 500px;
    margin: 0 auto;
}

.csv-stat-item {
    text-align: center;
    background: rgba(255, 255, 255, 0.1);
    padding: 20px;
    border-radius: 8px;
    backdrop-filter: blur(10px);
}

.stat-number {
    font-size: 28px;
    font-weight: bold;
    display: block;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 14px;
    opacity: 0.8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.csv-section {
    background: white;
    border-radius: 12px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    border: 1px solid #e5e7eb;
}

.section-header {
    margin-bottom: 25px;
}

.section-header h3 {
    margin: 0 0 10px 0;
    font-size: 24px;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 10px;
}

.section-icon {
    font-size: 20px;
}

.export-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 20px;
}

.export-option {
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    padding: 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.2s ease;
}

.export-option:hover {
    border-color: #3b82f6;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
}

.option-content h4 {
    margin: 0 0 8px 0;
    color: #1f2937;
    font-size: 18px;
}

.option-content p {
    margin: 0 0 10px 0;
    color: #6b7280;
    line-height: 1.5;
}

.option-meta {
    font-size: 14px;
    color: #9ca3af;
}

.csv-button {
    padding: 12px 24px;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
    text-decoration: none;
    font-size: 14px;
    white-space: nowrap;
}

.csv-button-primary {
    background: #3b82f6;
    color: white;
}

.csv-button-primary:hover {
    background: #2563eb;
    transform: translateY(-1px);
}

.csv-button-secondary {
    background: #6b7280;
    color: white;
}

.csv-button-secondary:hover {
    background: #4b5563;
    transform: translateY(-1px);
}

.csv-button-outline {
    background: transparent;
    color: #3b82f6;
    border: 2px solid #3b82f6;
}

.csv-button-outline:hover {
    background: #3b82f6;
    color: white;
}

.csv-button-large {
    padding: 16px 32px;
    font-size: 16px;
}

.import-notice {
    display: flex;
    gap: 15px;
    padding: 20px;
    background: #fef3cd;
    border: 1px solid #f59e0b;
    border-radius: 8px;
    margin-bottom: 25px;
}

.notice-icon {
    font-size: 24px;
    flex-shrink: 0;
}

.notice-content h4 {
    margin: 0 0 5px 0;
    color: #d97706;
}

.notice-content p {
    margin: 0;
    color: #92400e;
}

.file-drop-zone {
    border: 2px dashed #d1d5db;
    border-radius: 8px;
    padding: 40px;
    text-align: center;
    background: #f9fafb;
    transition: all 0.2s ease;
    position: relative;
}

.file-drop-zone.dragover {
    border-color: #3b82f6;
    background: #eff6ff;
}

.file-drop-zone.has-file {
    border-color: #10b981;
    background: #f0fdf4;
}

.upload-icon {
    font-size: 48px;
    margin-bottom: 15px;
}

.file-upload-content h4 {
    margin: 0 0 10px 0;
    color: #1f2937;
}

.file-upload-content p {
    margin: 0 0 20px 0;
    color: #6b7280;
}

.file-requirements {
    margin-top: 15px;
    color: #9ca3af;
}

.file-selected-info {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
    background: white;
    border-radius: 6px;
    border: 1px solid #d1d5db;
}

.selected-file-icon {
    font-size: 32px;
}

.selected-file-details h4 {
    margin: 0 0 5px 0;
    color: #1f2937;
}

.selected-file-details p {
    margin: 0;
    color: #6b7280;
    font-size: 14px;
}

.file-remove {
    background: #ef4444;
    color: white;
    border: none;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    cursor: pointer;
    font-size: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-left: auto;
}

.import-actions {
    text-align: center;
    margin-top: 25px;
}

.fields-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.field-group {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 20px;
    background: #f9fafb;
}

.group-title {
    margin: 0 0 15px 0;
    color: #1f2937;
    font-size: 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.field-count {
    font-size: 12px;
    color: #6b7280;
    font-weight: normal;
}

.field-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.field-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #e5e7eb;
    gap: 10px;
}

.field-item:last-child {
    border-bottom: none;
}

.field-name {
    font-weight: 500;
    color: #1f2937;
}

.field-type {
    font-size: 12px;
    color: #6b7280;
    background: #e5e7eb;
    padding: 2px 6px;
    border-radius: 4px;
    font-family: monospace;
}

.field-badge {
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 4px;
    text-transform: uppercase;
    font-weight: 600;
    letter-spacing: 0.5px;
}

.field-badge.repeater {
    background: #dbeafe;
    color: #1e40af;
}

.field-badge.required {
    background: #fee2e2;
    color: #dc2626;
}

.csv-no-fields-message {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.no-fields-icon {
    font-size: 64px;
    margin-bottom: 20px;
}

.csv-no-fields-message h3 {
    color: #1f2937;
    margin: 0 0 15px 0;
}

.admin-help {
    background: #f3f4f6;
    padding: 20px;
    border-radius: 8px;
    margin-top: 20px;
    text-align: left;
    max-width: 500px;
    margin-left: auto;
    margin-right: auto;
}

.admin-help ul {
    margin: 10px 0 0 20px;
}

.admin-help li {
    margin-bottom: 5px;
    color: #4b5563;
}

.csv-interface-footer {
    text-align: center;
    padding: 30px 20px;
    border-top: 1px solid #e5e7eb;
    margin-top: 40px;
}

.footer-links {
    display: flex;
    justify-content: center;
    gap: 20px;
    flex-wrap: wrap;
}

.footer-links a {
    color: #6b7280;
    text-decoration: none;
    font-size: 14px;
    padding: 5px 10px;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.footer-links a:hover {
    color: #3b82f6;
    background: #f3f4f6;
}

.csv-messages-container {
    margin: 20px 0;
}

.csv-message {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 10px;
    position: relative;
}

.csv-message.success {
    background: #f0fdf4;
    border: 1px solid #22c55e;
    color: #166534;
}

.csv-message.error {
    background: #fef2f2;
    border: 1px solid #ef4444;
    color: #dc2626;
}

.csv-message.info {
    background: #eff6ff;
    border: 1px solid #3b82f6;
    color: #1e40af;
}

.message-dismiss {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    opacity: 0.6;
    padding: 5px;
    border-radius: 4px;
}

.message-dismiss:hover {
    opacity: 1;
    background: rgba(0, 0, 0, 0.1);
}

@media (max-width: 768px) {
    .csv-interface-header {
        padding: 30px 15px;
    }
    
    .csv-header-content h2 {
        font-size: 24px;
    }
    
    .csv-description {
        font-size: 16px;
    }
    
    .csv-stats-grid {
        grid-template-columns: 1fr;
    }
    
    .export-options {
        grid-template-columns: 1fr;
    }
    
    .export-option {
        flex-direction: column;
        text-align: center;
        gap: 20px;
    }
    
    .fields-grid {
        grid-template-columns: 1fr;
    }
    
    .field-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
    
    .footer-links {
        flex-direction: column;
        gap: 10px;
    }
}
</style>