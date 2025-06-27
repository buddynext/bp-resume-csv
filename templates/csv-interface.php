<?php
/**
 * CSV Interface Template
 * 
 * File: templates/csv-interface.php
 * 
 * Simplified template for displaying the CSV import/export interface
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
    
    <!-- Header Section -->
    <div class="csv-header">
        <h2><?php _e('CSV Import/Export', 'bp-resume-csv'); ?></h2>
        <p class="csv-subtitle"><?php printf(__('Manage your resume data efficiently with CSV files. You have %d fields available for import/export.', 'bp-resume-csv'), $total_fields); ?></p>
        
        <?php if ($field_stats['total_fields'] > 0): ?>
        <div class="completion-stats">
            <div class="completion-circle">
                <span class="completion-percentage"><?php echo $field_stats['completion_percentage']; ?>%</span>
                <span class="completion-label"><?php _e('Complete', 'bp-resume-csv'); ?></span>
            </div>
            <p class="completion-info">
                <?php printf(__('%d of %d fields filled', 'bp-resume-csv'), $field_stats['filled_fields'], $field_stats['total_fields']); ?>
            </p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Action Cards -->
    <div class="csv-actions">
        
        <!-- Download Card -->
        <div class="csv-card download-card">
            <div class="card-header">
                <div class="card-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M21 15V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M7 10L12 15L17 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 15V3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="card-content">
                    <h3><?php _e('Download Templates & Data', 'bp-resume-csv'); ?></h3>
                    <p><?php _e('Download an empty CSV template or export your current resume data.', 'bp-resume-csv'); ?></p>
                </div>
            </div>
            <div class="card-actions">
                <button type="button" id="bprm-download-template" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M14 2H6C5.46957 2 4.96086 2.21071 4.58579 2.58579C4.21071 2.96086 4 3.46957 4 4V20C4 20.5304 4.21071 21.0391 4.58579 21.4142C4.96086 21.7893 5.46957 22 6 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V8L14 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M14 2V8H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <?php _e('Download Template', 'bp-resume-csv'); ?>
                </button>
                <button type="button" id="bprm-export-data" class="btn btn-secondary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2V6C12 6.53043 12.2107 7.03914 12.5858 7.41421C12.9609 7.78929 13.4696 8 14 8H18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M18 22H6C5.46957 22 4.96086 21.7893 4.58579 21.4142C4.21071 21.0391 4 20.5304 4 20V4C4 3.46957 4.21071 2.96086 4.58579 2.58579C4.96086 2.21071 4.53043 2 6 2H12L18 8V22Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <?php _e('Export Data', 'bp-resume-csv'); ?>
                </button>
            </div>
        </div>

        <!-- Upload Card -->
        <div class="csv-card upload-card">
            <div class="card-header">
                <div class="card-icon upload-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M21 15V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M17 8L12 3L7 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 3V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="card-content">
                    <h3><?php _e('Import Data', 'bp-resume-csv'); ?></h3>
                    <p><?php _e('Upload a CSV file to update your resume information. Max size: 5MB.', 'bp-resume-csv'); ?></p>
                </div>
            </div>
            
            <form id="bprm-csv-upload-form" enctype="multipart/form-data">
                <div class="file-upload-zone" id="file-drop-zone">
                    <input type="file" name="csv_file" id="bprm-csv-file" accept=".csv" required>
                    <div class="file-upload-content">
                        <div class="upload-icon-large">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M14 2H6C5.46957 2 4.96086 2.21071 4.58579 2.58579C4.21071 2.96086 4 3.46957 4 4V20C4 20.5304 4.21071 21.0391 4.58579 21.4142C4.96086 21.7893 5.46957 22 6 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V8L14 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M14 2V8H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h4><?php _e('Choose CSV File', 'bp-resume-csv'); ?></h4>
                        <p><?php _e('Drag and drop your file here, or click to browse', 'bp-resume-csv'); ?></p>
                    </div>
                    <div class="file-selected-info" style="display: none;">
                        <div class="file-preview">
                            <div class="file-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M14 2H6C5.46957 2 4.96086 2.21071 4.58579 2.58579C4.21071 2.96086 4 3.46957 4 4V20C4 20.5304 4.21071 21.0391 4.58579 21.4142C4.96086 21.7893 5.46957 22 6 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V8L14 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M14 2V8H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                            <div class="file-details">
                                <span class="file-name"></span>
                                <span class="file-size"></span>
                            </div>
                            <button type="button" class="file-remove">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-success btn-block upload-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M21 15V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M17 8L12 3L7 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 3V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <?php _e('Import CSV Data', 'bp-resume-csv'); ?>
                </button>
                
                <div class="import-warning">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 9V13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 17H12.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                    </svg>
                    <span><?php _e('Warning: Uploading CSV data will replace your existing resume information.', 'bp-resume-csv'); ?></span>
                </div>
            </form>
        </div>
        
    </div>

    <!-- Messages Container -->
    <div id="bprm-csv-messages" class="csv-messages"></div>
    
    <!-- Progress Indicator -->
    <div class="csv-progress-container" id="csv-progress" style="display: none;">
        <div class="progress-wrapper">
            <div class="progress-bar">
                <div class="progress-fill" style="width: 0%"></div>
            </div>
            <div class="progress-info">
                <span class="progress-label"><?php _e('Processing...', 'bp-resume-csv'); ?></span>
                <span class="progress-percentage">0%</span>
            </div>
        </div>
    </div>
    
    <!-- Important Guidelines -->
    <div class="csv-guidelines">
        <h3><?php _e('Important Guidelines', 'bp-resume-csv'); ?></h3>
        
        <div class="guideline-item">
            <div class="guideline-header">
                <div class="guideline-icon">📋</div>
                <h4><?php _e('Column Headers', 'bp-resume-csv'); ?></h4>
            </div>
            <p><?php _e('Never modify the column headers in the CSV file', 'bp-resume-csv'); ?></p>
        </div>
        
        <div class="guideline-item">
            <div class="guideline-header">
                <div class="guideline-icon">📅</div>
                <h4><?php _e('Date Format', 'bp-resume-csv'); ?></h4>
            </div>
            <p><?php _e('Use YYYY-MM-DD format for all dates (e.g., 2024-12-25)', 'bp-resume-csv'); ?></p>
        </div>
        
        <div class="guideline-item">
            <div class="guideline-header">
                <div class="guideline-icon">📧</div>
                <h4><?php _e('Email Validation', 'bp-resume-csv'); ?></h4>
            </div>
            <p><?php _e('Ensure email addresses are in valid format (user@domain.com)', 'bp-resume-csv'); ?></p>
        </div>
        
        <div class="guideline-item">
            <div class="guideline-header">
                <div class="guideline-icon">💾</div>
                <h4><?php _e('File Size', 'bp-resume-csv'); ?></h4>
            </div>
            <p><?php _e('Maximum file size is 5MB. Compress large files if needed', 'bp-resume-csv'); ?></p>
        </div>
    </div>

    <!-- Quick Help -->
    <div class="csv-help">
        <h4><?php _e('How to use CSV Import/Export', 'bp-resume-csv'); ?></h4>
        <ol>
            <li><?php _e('Download the template CSV file to see the correct format', 'bp-resume-csv'); ?></li>
            <li><?php _e('Fill in your data following the examples in the template', 'bp-resume-csv'); ?></li>
            <li><?php _e('Save your file as CSV format and upload it using the form above', 'bp-resume-csv'); ?></li>
        </ol>
    </div>

</div>

<style>
/* Simplified CSV Interface Styles */
.bprm-csv-interface {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Header */
.csv-header {
    text-align: center;
    background: white;
    border-radius: 12px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.csv-header h2 {
    margin: 0 0 10px 0;
    font-size: 28px;
    color: #2c3e50;
}

.csv-subtitle {
    margin: 0 0 20px 0;
    color: #7f8c8d;
    font-size: 16px;
}

.completion-stats {
    margin-top: 20px;
}

.completion-circle {
    display: inline-block;
    text-align: center;
    background: #f8f9fa;
    border-radius: 50%;
    width: 80px;
    height: 80px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    margin-bottom: 10px;
}

.completion-percentage {
    font-size: 20px;
    font-weight: bold;
    color: #3498db;
}

.completion-label {
    font-size: 12px;
    color: #7f8c8d;
}

.completion-info {
    margin: 0;
    color: #7f8c8d;
    font-size: 14px;
}

/* Actions */
.csv-actions {
    display: grid;
    gap: 25px;
    margin-bottom: 30px;
}

.csv-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    border: 1px solid #e8ecef;
    transition: all 0.3s ease;
}

.csv-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.12);
}

.card-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
}

.card-icon {
    width: 50px;
    height: 50px;
    background: #3498db;
    color: white;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.upload-icon {
    background: #e74c3c;
}

.card-content h3 {
    margin: 0 0 5px 0;
    font-size: 18px;
    color: #2c3e50;
}

.card-content p {
    margin: 0;
    color: #7f8c8d;
    font-size: 14px;
}

/* Buttons */
.card-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s ease;
    min-height: 44px;
}

.btn-block {
    width: 100%;
    justify-content: center;
}

.btn-primary {
    background: #3498db;
    color: white;
}

.btn-primary:hover {
    background: #2980b9;
}

.btn-secondary {
    background: #95a5a6;
    color: white;
}

.btn-secondary:hover {
    background: #7f8c8d;
}

.btn-success {
    background: #27ae60;
    color: white;
}

.btn-success:hover {
    background: #229954;
}

/* File Upload */
.file-upload-zone {
    border: 2px dashed #bdc3c7;
    border-radius: 10px;
    padding: 30px 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    margin: 20px 0;
    background: #f8f9fa;
}

.file-upload-zone:hover,
.file-upload-zone.dragover {
    border-color: #3498db;
    background: #e3f2fd;
}

#bprm-csv-file {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
}

.upload-icon-large {
    color: #bdc3c7;
    margin-bottom: 15px;
}

.file-upload-content h4 {
    margin: 0 0 8px 0;
    color: #2c3e50;
}

.file-upload-content p {
    margin: 0;
    color: #7f8c8d;
}

/* File Selected */
.file-selected-info {
    padding: 15px;
    background: #d5f4e6;
    border: 1px solid #27ae60;
    border-radius: 8px;
    margin: 15px 0;
}

.file-preview {
    display: flex;
    align-items: center;
    gap: 12px;
}

.file-icon {
    color: #27ae60;
}

.file-details {
    flex: 1;
}

.file-name {
    display: block;
    font-weight: 500;
    color: #1e8449;
}

.file-size {
    display: block;
    font-size: 12px;
    color: #239b56;
}

.file-remove {
    background: none;
    border: none;
    color: #c0392b;
    cursor: pointer;
    padding: 5px;
    border-radius: 4px;
}

.file-remove:hover {
    background: #fadbd8;
}

/* Warning */
.import-warning {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px;
    background: #fef9e7;
    border: 1px solid #f39c12;
    border-radius: 6px;
    font-size: 13px;
    color: #b7950b;
    margin-top: 15px;
}

/* Messages */
.csv-messages {
    margin: 20px 0;
}

.csv-message {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 15px;
}

.csv-message.success {
    background: #d5f4e6;
    border: 1px solid #27ae60;
    color: #1e8449;
}

.csv-message.error {
    background: #fadbd8;
    border: 1px solid #e74c3c;
    color: #c0392b;
}

/* Progress */
.csv-progress-container {
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.progress-bar {
    width: 100%;
    height: 8px;
    background: #ecf0f1;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 10px;
}

.progress-fill {
    height: 100%;
    background: #3498db;
    transition: width 0.3s ease;
}

.progress-info {
    display: flex;
    justify-content: space-between;
    font-size: 14px;
    color: #7f8c8d;
}

/* Guidelines */
.csv-guidelines {
    background: white;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.csv-guidelines h3 {
    margin: 0 0 20px 0;
    color: #2c3e50;
    font-size: 20px;
}

.guideline-item {
    background: #f8f9fa;
    border: 1px solid #e8ecef;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 12px;
}

.guideline-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
}

.guideline-icon {
    font-size: 20px;
}

.guideline-item h4 {
    margin: 0;
    font-size: 16px;
    color: #2c3e50;
}

.guideline-item p {
    margin: 0;
    color: #7f8c8d;
    font-size: 14px;
}

/* Help */
.csv-help {
    background: #e8f4fd;
    border: 1px solid #3498db;
    border-radius: 10px;
    padding: 20px;
}

.csv-help h4 {
    margin: 0 0 15px 0;
    color: #2c3e50;
}

.csv-help ol {
    margin: 0;
    padding-left: 20px;
}

.csv-help li {
    margin-bottom: 8px;
    color: #34495e;
    line-height: 1.5;
}

/* Loading states */
.btn.loading {
    opacity: 0.7;
    pointer-events: none;
}

.btn.loading::after {
    content: '';
    width: 14px;
    height: 14px;
    border: 2px solid currentColor;
    border-top-color: transparent;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-left: 8px;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 600px) {
    .bprm-csv-interface {
        padding: 15px;
    }
    
    .csv-header {
        padding: 20px;
    }
    
    .csv-card {
        padding: 20px;
    }
    
    .card-actions {
        flex-direction: column;
    }
    
    .btn {
        justify-content: center;
    }
    
    .card-header {
        flex-direction: column;
        text-align: center;
        gap: 10px;
    }
}
</style>