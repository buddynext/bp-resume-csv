<?php
/**
 * Clean CSV Interface Template
 * 
 * File: templates/csv-interface-enhanced.php
 * 
 * No debug mode or admin backend menus
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();

// Use enhanced handler if available, otherwise fall back to standard handler
if (class_exists('BP_Resume_CSV_Handler_Enhanced')) {
    $csv_handler = new BP_Resume_CSV_Handler_Enhanced();
} else {
    $csv_handler = new BP_Resume_CSV_Handler();
}

$available_fields = $csv_handler->get_user_resume_fields($user_id);
$field_stats = $csv_handler->get_field_statistics($user_id);

$total_fields = 0;
foreach ($available_fields as $fields) {
    $total_fields += count($fields);
}

// Check if enhanced CSS is loaded, if not, use inline styles
$enhanced_css_path = BP_RESUME_CSV_PLUGIN_PATH . 'assets/css/csv-style-enhanced.css';
$use_inline_css = !file_exists($enhanced_css_path) || !wp_style_is('bp-resume-csv-style-enhanced', 'enqueued');

if ($use_inline_css) {
    // Force load CSS directly in template
    $enhanced_css_url = BP_RESUME_CSV_PLUGIN_URL . 'assets/css/csv-style-enhanced.css';
    if (file_exists($enhanced_css_path)) {
        echo '<link rel="stylesheet" href="' . esc_url($enhanced_css_url) . '?v=' . BP_RESUME_CSV_VERSION . '">' . "\n";
    }
}
?>

<div class="bp-resume-csv-interface">
    
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
                <?php if (defined('BPRM_PLUGIN_VERSION')): ?>
                <li><a href="<?php echo admin_url('admin.php?page=bp_resume_manager'); ?>"><?php _e('Go to Resume Manager Settings', 'bp-resume-csv'); ?></a></li>
                <?php else: ?>
                <li><?php _e('Install and activate BP Resume Manager plugin', 'bp-resume-csv'); ?></li>
                <?php endif; ?>
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
                            <?php if (class_exists('BP_Resume_CSV_Handler_Enhanced')): ?>
                            <span class="enhanced-badge">✨ Enhanced Detection</span>
                            <?php endif; ?>
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
                            <?php if (class_exists('BP_Resume_CSV_Handler_Enhanced')): ?>
                            <span class="enhanced-badge">✨ Enhanced Export</span>
                            <?php endif; ?>
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
                            <?php if (isset($field_info['repeater']) && $field_info['repeater'] === 'yes'): ?>
                                <span class="field-badge repeater"><?php _e('Repeater', 'bp-resume-csv'); ?></span>
                            <?php endif; ?>
                            <?php if (isset($field_info['required']) && $field_info['required'] === 'yes'): ?>
                                <span class="field-badge required"><?php _e('Required', 'bp-resume-csv'); ?></span>
                            <?php endif; ?>
                            <?php if (isset($field_info['group_repeater']) && $field_info['group_repeater'] === 'yes'): ?>
                                <span class="field-badge group-repeater"><?php _e('Group Repeater', 'bp-resume-csv'); ?></span>
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
    
</div>

<!-- Minimal Essential CSS (Fallback) -->
<?php if ($use_inline_css && !file_exists($enhanced_css_path)): ?>
<style>
/* Essential CSS for CSV Interface */
.bp-resume-csv-interface {
    max-width: 1200px;
    margin: 0 auto;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    line-height: 1.6;
}

.csv-interface-header {
    text-align: center;
    margin-bottom: 40px;
    padding: 40px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    color: white;
    box-shadow: 0 8px 32px rgba(102, 126, 234, 0.3);
}

.csv-header-content h2 {
    margin: 0 0 10px 0;
    font-size: 32px;
    font-weight: 700;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.csv-description {
    font-size: 18px;
    opacity: 0.9;
    margin: 0 0 30px 0;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
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
    background: rgba(255, 255, 255, 0.15);
    padding: 20px;
    border-radius: 8px;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    transition: transform 0.2s ease;
}

.csv-stat-item:hover {
    transform: translateY(-2px);
    background: rgba(255, 255, 255, 0.2);
}

.stat-number {
    font-size: 28px;
    font-weight: bold;
    display: block;
    margin-bottom: 5px;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
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

.section-header h3 {
    margin: 0 0 10px 0;
    font-size: 24px;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 10px;
}

.section-header p {
    margin: 0;
    color: #6b7280;
    font-size: 16px;
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
    transition: all 0.3s ease;
    background: #fafbfc;
}

.export-option:hover {
    border-color: #3b82f6;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
    background: #f8faff;
    transform: translateY(-2px);
}

.option-content {
    flex: 1;
    margin-right: 20px;
}

.option-content h4 {
    margin: 0 0 8px 0;
    color: #1f2937;
    font-size: 18px;
    font-weight: 600;
}

.option-content p {
    margin: 0 0 10px 0;
    color: #6b7280;
    line-height: 1.5;
    font-size: 14px;
}

.option-meta {
    font-size: 13px;
    color: #9ca3af;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.field-count {
    background: #f3f4f6;
    padding: 2px 8px;
    border-radius: 12px;
    font-weight: 500;
}

.enhanced-badge {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: inline-block;
    margin-left: 8px;
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
    min-width: 140px;
    justify-content: center;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.csv-button:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.csv-button-primary {
    background: #3b82f6;
    color: white;
}

.csv-button-primary:hover {
    background: #2563eb;
}

.csv-button-secondary {
    background: #6b7280;
    color: white;
}

.csv-button-secondary:hover {
    background: #4b5563;
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
    min-width: 180px;
}

.import-notice {
    display: flex;
    gap: 15px;
    padding: 20px;
    background: #fef3cd;
    border: 1px solid #f59e0b;
    border-radius: 8px;
    margin-bottom: 25px;
    border-left: 4px solid #f59e0b;
}

.notice-icon {
    font-size: 24px;
    flex-shrink: 0;
}

.notice-content h4 {
    margin: 0 0 5px 0;
    color: #d97706;
    font-size: 16px;
}

.notice-content p {
    margin: 0;
    color: #92400e;
    line-height: 1.5;
}

.file-drop-zone {
    border: 2px dashed #d1d5db;
    border-radius: 8px;
    padding: 40px 20px;
    text-align: center;
    background: #f9fafb;
    transition: all 0.3s ease;
    min-height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.file-drop-zone.dragover {
    border-color: #3b82f6;
    background: #eff6ff;
    transform: scale(1.02);
}

.file-drop-zone.has-file {
    border-color: #10b981;
    background: #f0fdf4;
}

.upload-icon {
    font-size: 48px;
    margin-bottom: 15px;
    opacity: 0.7;
}

.file-upload-content h4 {
    margin: 0 0 10px 0;
    color: #1f2937;
    font-size: 18px;
}

.file-upload-content p {
    margin: 0 0 20px 0;
    color: #6b7280;
    font-size: 16px;
}

.file-requirements {
    margin-top: 15px;
    color: #9ca3af;
    font-size: 14px;
}

.file-selected-info {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
    background: white;
    border-radius: 6px;
    border: 1px solid #d1d5db;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.selected-file-icon {
    font-size: 32px;
    color: #3b82f6;
}

.selected-file-details {
    flex: 1;
}

.selected-file-details h4 {
    margin: 0 0 5px 0;
    color: #1f2937;
    font-size: 16px;
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
    width: 32px;
    height: 32px;
    cursor: pointer;
    font-size: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-left: auto;
    transition: all 0.2s ease;
    flex-shrink: 0;
}

.file-remove:hover {
    background: #dc2626;
    transform: scale(1.1);
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
    transition: all 0.2s ease;
}

.field-group:hover {
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    border-color: #d1d5db;
}

.group-title {
    margin: 0 0 15px 0;
    color: #1f2937;
    font-size: 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 10px;
    border-bottom: 1px solid #e5e7eb;
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
    padding: 10px 0;
    border-bottom: 1px solid #f3f4f6;
    gap: 10px;
    flex-wrap: wrap;
}

.field-item:last-child {
    border-bottom: none;
}

.field-name {
    font-weight: 500;
    color: #1f2937;
    flex: 1;
    min-width: 120px;
}

.field-type {
    font-size: 11px;
    color: #6b7280;
    background: #e5e7eb;
    padding: 3px 8px;
    border-radius: 4px;
    font-family: monospace;
    font-weight: 500;
}

.field-badge {
    font-size: 9px;
    padding: 2px 6px;
    border-radius: 4px;
    text-transform: uppercase;
    font-weight: 600;
    letter-spacing: 0.5px;
    white-space: nowrap;
}

.field-badge.repeater {
    background: #dbeafe;
    color: #1e40af;
}

.field-badge.required {
    background: #fee2e2;
    color: #dc2626;
}

.field-badge.group-repeater {
    background: #fef3cd;
    color: #d97706;
}

.csv-no-fields-message {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    border: 1px solid #e5e7eb;
}

.no-fields-icon {
    font-size: 64px;
    margin-bottom: 20px;
    opacity: 0.6;
}

.csv-no-fields-message h3 {
    color: #1f2937;
    margin: 0 0 15px 0;
    font-size: 24px;
}

.csv-no-fields-message p {
    color: #6b7280;
    font-size: 16px;
    margin-bottom: 20px;
}

.admin-help {
    background: #f3f4f6;
    padding: 20px;
    border-radius: 8px;
    margin: 20px auto 0;
    text-align: left;
    max-width: 600px;
    border: 1px solid #e5e7eb;
}

.admin-help p {
    margin: 0 0 10px 0;
    color: #374151;
}

.admin-help ul {
    margin: 10px 0 15px 20px;
    color: #4b5563;
}

.admin-help li {
    margin-bottom: 8px;
    line-height: 1.5;
}

.admin-help a {
    color: #3b82f6;
    text-decoration: none;
    font-weight: 500;
}

.admin-help a:hover {
    text-decoration: underline;
}

.csv-interface-footer {
    text-align: center;
    padding: 30px 20px;
    border-top: 1px solid #e5e7eb;
    margin-top: 40px;
    background: #f9fafb;
    border-radius: 8px;
}

.footer-links {
    display: flex;
    justify-content: center;
    gap: 20px;
    flex-wrap: wrap;
    margin-bottom: 10px;
}

.footer-links a {
    color: #6b7280;
    text-decoration: none;
    font-size: 14px;
    padding: 8px 12px;
    border-radius: 4px;
    transition: all 0.2s ease;
    font-weight: 500;
}

.footer-links a:hover {
    color: #3b82f6;
    background: #f3f4f6;
}

.footer-info {
    margin-top: 10px;
    text-align: center;
    opacity: 0.7;
    font-size: 13px;
}

.csv-messages-container {
    margin: 20px 0;
}

.csv-message {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 10px;
    position: relative;
    border-left: 4px solid;
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.csv-message.success {
    background: #f0fdf4;
    border-left-color: #22c55e;
    color: #166534;
}

.csv-message.error {
    background: #fef2f2;
    border-left-color: #ef4444;
    color: #dc2626;
}

.csv-message.info {
    background: #eff6ff;
    border-left-color: #3b82f6;
    color: #1e40af;
}

.message-dismiss {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    opacity: 0.6;
    padding: 5px;
    border-radius: 4px;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.message-dismiss:hover {
    opacity: 1;
    background: rgba(0, 0, 0, 0.1);
}

/* Responsive Design */
@media (max-width: 768px) {
    .bp-resume-csv-interface {
        padding: 0 10px;
    }
    
    .csv-interface-header {
        padding: 30px 15px;
        margin-bottom: 20px;
    }
    
    .csv-header-content h2 {
        font-size: 24px;
    }
    
    .csv-description {
        font-size: 16px;
    }
    
    .csv-stats-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .csv-section {
        padding: 20px 15px;
        margin-bottom: 20px;
    }
    
    .export-options {
        grid-template-columns: 1fr;
    }
    
    .export-option {
        flex-direction: column;
        text-align: center;
        gap: 20px;
        padding: 20px;
    }
    
    .option-content {
        margin-right: 0;
        margin-bottom: 15px;
    }
    
    .fields-grid {
        grid-template-columns: 1fr;
    }
    
    .field-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    
    .field-name {
        min-width: auto;
        width: 100%;
    }
    
    .footer-links {
        flex-direction: column;
        gap: 10px;
    }
    
    .file-drop-zone {
        padding: 30px 15px;
        min-height: 160px;
    }
    
    .upload-icon {
        font-size: 36px;
    }
    
    .csv-button {
        min-width: 120px;
        padding: 10px 20px;
    }
    
    .csv-button-large {
        min-width: 140px;
        padding: 14px 28px;
        font-size: 15px;
    }
    
    .enhanced-badge {
        display: block;
        margin: 5px 0 0 0;
        text-align: center;
        width: fit-content;
    }
}
</style>
<?php endif; ?>