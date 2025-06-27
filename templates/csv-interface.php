<?php
/**
 * CSV Interface Template
 * 
 * File: templates/csv-interface.php
 * 
 * Template for displaying the CSV import/export interface
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get field statistics
$csv_handler = new BP_Resume_CSV_Handler();
$field_stats = $csv_handler->get_field_statistics($user_id);
?>

<div class="bprm-csv-interface">
    <h3><?php _e('CSV Import/Export', 'bp-resume-csv'); ?></h3>
    
    <div class="bprm-csv-intro">
        <p><?php printf(
            __('Manage your resume data efficiently with CSV files. You have %d fields available for import/export.', 'bp-resume-csv'), 
            $total_fields
        ); ?></p>
        
        <?php if ($field_stats['total_fields'] > 0): ?>
        <div class="bprm-progress-summary">
            <div class="progress-info">
                <span class="progress-text">
                    <?php printf(
                        __('Profile Completion: %d%% (%d of %d fields filled)', 'bp-resume-csv'),
                        $field_stats['completion_percentage'],
                        $field_stats['filled_fields'],
                        $field_stats['total_fields']
                    ); ?>
                </span>
            </div>
            <div class="progress-bar-container">
                <div class="progress-bar" style="width: <?php echo $field_stats['completion_percentage']; ?>%"></div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="bprm-csv-actions">
        
        <!-- Download Section -->
        <div class="bprm-csv-section download-section">
            <h4>
                <span class="dashicons dashicons-download"></span>
                <?php _e('Download Templates & Data', 'bp-resume-csv'); ?>
            </h4>
            <div class="bprm-csv-buttons">
                <button type="button" id="bprm-download-template" class="button button-secondary">
                    <span class="dashicons dashicons-media-spreadsheet"></span>
                    <?php _e('Download Empty Template', 'bp-resume-csv'); ?>
                </button>
                <button type="button" id="bprm-export-data" class="button button-secondary">
                    <span class="dashicons dashicons-portfolio"></span>
                    <?php _e('Export Current Data', 'bp-resume-csv'); ?>
                </button>
            </div>
            <p class="description">
                <?php _e('Download an empty CSV template to fill with your data, or export your current resume data as CSV for backup.', 'bp-resume-csv'); ?>
            </p>
        </div>

        <!-- Upload Section -->
        <div class="bprm-csv-section upload-section">
            <h4>
                <span class="dashicons dashicons-upload"></span>
                <?php _e('Import Data', 'bp-resume-csv'); ?>
            </h4>
            <form id="bprm-csv-upload-form" enctype="multipart/form-data">
                <div class="bprm-file-input-wrapper">
                    <input type="file" name="csv_file" id="bprm-csv-file" accept=".csv" required>
                    <label for="bprm-csv-file" class="button button-secondary">
                        <span class="dashicons dashicons-media-default"></span>
                        <?php _e('Choose CSV File', 'bp-resume-csv'); ?>
                    </label>
                    <span class="file-name"></span>
                </div>
                <button type="submit" class="button button-primary">
                    <span class="dashicons dashicons-yes"></span>
                    <?php _e('Import CSV Data', 'bp-resume-csv'); ?>
                </button>
            </form>
            <p class="description">
                <strong><?php _e('Warning:', 'bp-resume-csv'); ?></strong>
                <?php _e('Uploading CSV data will replace your existing resume information. Make sure to export your current data first if you want to keep a backup.', 'bp-resume-csv'); ?>
            </p>
        </div>
        
    </div>

    <!-- Messages Container -->
    <div id="bprm-csv-messages"></div>
    
    <!-- CSV Preview (will be populated by JavaScript) -->
    <div class="csv-preview" style="display: none;">
        <h5><?php _e('File Preview:', 'bp-resume-csv'); ?></h5>
        <pre></pre>
    </div>
    
    <!-- Help Section -->
    <div class="bprm-csv-help">
        <h4>
            <span class="dashicons dashicons-info"></span>
            <?php _e('How to use CSV Import/Export', 'bp-resume-csv'); ?>
        </h4>
        
        <div class="help-steps">
            <div class="help-step">
                <div class="step-number">1</div>
                <div class="step-content">
                    <h5><?php _e('Download Template', 'bp-resume-csv'); ?></h5>
                    <p><?php _e('Download the empty template or export your current data to get the correct CSV structure.', 'bp-resume-csv'); ?></p>
                </div>
            </div>
            
            <div class="help-step">
                <div class="step-number">2</div>
                <div class="step-content">
                    <h5><?php _e('Edit in Spreadsheet', 'bp-resume-csv'); ?></h5>
                    <p><?php _e('Open the CSV file in Excel, Google Sheets, or any spreadsheet application and fill in your data.', 'bp-resume-csv'); ?></p>
                </div>
            </div>
            
            <div class="help-step">
                <div class="step-number">3</div>
                <div class="step-content">
                    <h5><?php _e('Save and Upload', 'bp-resume-csv'); ?></h5>
                    <p><?php _e('Save the file as CSV format and upload it using the import form above to update your resume.', 'bp-resume-csv'); ?></p>
                </div>
            </div>
        </div>
        
        <div class="help-tips">
            <h5><?php _e('Important Tips:', 'bp-resume-csv'); ?></h5>
            <ul>
                <li><?php _e('Do not modify the column headers in the CSV file', 'bp-resume-csv'); ?></li>
                <li><?php _e('For repeater fields, add multiple rows with the same group_instance number', 'bp-resume-csv'); ?></li>
                <li><?php _e('For dropdown fields, use only the values listed in the "field_options_available" column', 'bp-resume-csv'); ?></li>
                <li><?php _e('Use YYYY-MM-DD format for date fields (e.g., 2024-12-25)', 'bp-resume-csv'); ?></li>
                <li><?php _e('For email fields, ensure valid email format (user@domain.com)', 'bp-resume-csv'); ?></li>
                <li><?php _e('Maximum file size allowed: 5MB', 'bp-resume-csv'); ?></li>
            </ul>
        </div>
        
        <div class="help-examples">
            <h5><?php _e('Field Examples:', 'bp-resume-csv'); ?></h5>
            <div class="examples-grid">
                <div class="example-item">
                    <strong><?php _e('Text Field:', 'bp-resume-csv'); ?></strong>
                    <code>John Doe</code>
                </div>
                <div class="example-item">
                    <strong><?php _e('Email Field:', 'bp-resume-csv'); ?></strong>
                    <code>john.doe@example.com</code>
                </div>
                <div class="example-item">
                    <strong><?php _e('Date Field:', 'bp-resume-csv'); ?></strong>
                    <code>1990-01-15</code>
                </div>
                <div class="example-item">
                    <strong><?php _e('URL Field:', 'bp-resume-csv'); ?></strong>
                    <code>https://linkedin.com/in/johndoe</code>
                </div>
                <div class="example-item">
                    <strong><?php _e('Skills (Text+Dropdown):', 'bp-resume-csv'); ?></strong>
                    <code>{"text":"PHP Programming","dropdown_val":"5"}</code>
                </div>
                <div class="example-item">
                    <strong><?php _e('Multiple Values:', 'bp-resume-csv'); ?></strong>
                    <code>Option1,Option2,Option3</code>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Available Fields Summary -->
    <div class="bprm-fields-summary">
        <h4>
            <span class="dashicons dashicons-list-view"></span>
            <?php _e('Your Available Resume Fields', 'bp-resume-csv'); ?>
        </h4>
        
        <div class="fields-grid">
            <?php foreach ($available_fields as $group_key => $fields): ?>
            <div class="field-group">
                <h5><?php echo esc_html($fields[array_keys($fields)[0]]['group_name']); ?></h5>
                <ul class="field-list">
                    <?php foreach ($fields as $field_key => $field_info): ?>
                    <li>
                        <span class="field-name"><?php echo esc_html($field_info['title']); ?></span>
                        <span class="field-type"><?php echo esc_html($field_info['type']); ?></span>
                        <?php if ($field_info['repeater'] === 'yes'): ?>
                        <span class="field-repeater"><?php _e('Repeater', 'bp-resume-csv'); ?></span>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
/* Additional styles for the template */
.bprm-csv-intro {
    margin-bottom: 25px;
    padding: 15px;
    background: #f0f6fc;
    border: 1px solid #c5d9ed;
    border-radius: 6px;
}

.bprm-progress-summary {
    margin-top: 15px;
}

.progress-info {
    margin-bottom: 8px;
}

.progress-text {
    font-weight: 500;
    color: #1e3a8a;
}

.progress-bar-container {
    width: 100%;
    height: 8px;
    background: #e5e7eb;
    border-radius: 4px;
    overflow: hidden;
}

.progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #3b82f6, #1e40af);
    transition: width 0.3s ease;
}

.download-section {
    border-left: 4px solid #059669;
}

.upload-section {
    border-left: 4px solid #dc2626;
}

.help-steps {
    display: grid;
    gap: 20px;
    margin: 20px 0;
}

.help-step {
    display: flex;
    align-items: flex-start;
    gap: 15px;
    padding: 15px;
    background: #fafafa;
    border-radius: 6px;
}

.step-number {
    width: 30px;
    height: 30px;
    background: #0073aa;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    flex-shrink: 0;
}

.step-content h5 {
    margin: 0 0 5px 0;
    color: #333;
}

.step-content p {
    margin: 0;
    color: #666;
    line-height: 1.5;
}

.help-tips ul {
    margin: 10px 0;
    padding-left: 20px;
}

.help-tips li {
    margin-bottom: 8px;
    line-height: 1.5;
}

.examples-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    margin: 15px 0;
}

.example-item {
    padding: 10px;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 4px;
}

.example-item strong {
    display: block;
    margin-bottom: 5px;
    color: #495057;
}

.example-item code {
    background: #fff;
    padding: 4px 8px;
    border: 1px solid #dee2e6;
    border-radius: 3px;
    font-size: 12px;
    word-break: break-all;
}

.bprm-fields-summary {
    margin-top: 30px;
    padding: 20px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
}

.fields-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin: 15px 0;
}

.field-group {
    background: white;
    padding: 15px;
    border: 1px solid #e9ecef;
    border-radius: 4px;
}

.field-group h5 {
    margin: 0 0 10px 0;
    color: #0073aa;
    border-bottom: 1px solid #e9ecef;
    padding-bottom: 8px;
}

.field-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.field-list li {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 5px 0;
    border-bottom: 1px solid #f1f3f4;
}

.field-list li:last-child {
    border-bottom: none;
}

.field-name {
    flex: 1;
    font-weight: 500;
}

.field-type {
    font-size: 11px;
    background: #e9ecef;
    color: #495057;
    padding: 2px 6px;
    border-radius: 3px;
    text-transform: uppercase;
}

.field-repeater {
    font-size: 10px;
    background: #ffeaa7;
    color: #2d3436;
    padding: 2px 6px;
    border-radius: 3px;
    text-transform: uppercase;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .examples-grid {
        grid-template-columns: 1fr;
    }
    
    .fields-grid {
        grid-template-columns: 1fr;
    }
    
    .help-step {
        flex-direction: column;
        text-align: center;
    }
    
    .step-number {
        align-self: center;
    }
}
</style>