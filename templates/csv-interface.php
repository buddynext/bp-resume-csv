<?php
/**
 * CSV Interface Template
 * 
 * File: templates/csv-interface.php
 * 
 * Professional template for displaying the CSV import/export interface
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
        <div class="csv-header-content">
            <div class="csv-header-text">
                <h2><?php _e('CSV Import/Export', 'bp-resume-csv'); ?></h2>
                <p class="csv-subtitle"><?php printf(__('Manage your resume data efficiently with CSV files. You have %d fields available for import/export.', 'bp-resume-csv'), $total_fields); ?></p>
            </div>
            <?php if ($field_stats['total_fields'] > 0): ?>
            <div class="csv-header-stats">
                <div class="completion-card">
                    <div class="completion-circle">
                        <svg class="progress-ring" width="80" height="80">
                            <circle class="progress-ring-bg" cx="40" cy="40" r="36" stroke="#e5e7eb" stroke-width="8" fill="transparent"/>
                            <circle class="progress-ring-fill" cx="40" cy="40" r="36" stroke="#3b82f6" stroke-width="8" fill="transparent" 
                                    stroke-dasharray="226.19" stroke-dashoffset="<?php echo 226.19 - (226.19 * $field_stats['completion_percentage'] / 100); ?>" 
                                    transform="rotate(-90 40 40)"/>
                        </svg>
                        <div class="completion-text">
                            <span class="completion-percentage"><?php echo $field_stats['completion_percentage']; ?>%</span>
                            <span class="completion-label"><?php _e('Complete', 'bp-resume-csv'); ?></span>
                        </div>
                    </div>
                    <div class="completion-details">
                        <span class="completion-info">
                            <?php printf(__('%d of %d fields filled', 'bp-resume-csv'), $field_stats['filled_fields'], $field_stats['total_fields']); ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Action Cards Grid -->
    <div class="csv-cards-container">
        
        <!-- Download Card -->
        <div class="csv-card download-card">
            <div class="card-header">
                <div class="card-icon download-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M21 15V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V15" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M7 10L12 15L17 10" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 15V3" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="card-content">
                    <h3><?php _e('Download Templates & Data', 'bp-resume-csv'); ?></h3>
                    <p><?php _e('Download an empty CSV template to fill with your data, or export your current resume data as CSV for backup.', 'bp-resume-csv'); ?></p>
                </div>
            </div>
            <div class="card-actions">
                <button type="button" id="bprm-download-template" class="btn btn-primary btn-block">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M14 2H6C5.46957 2 4.96086 2.21071 4.58579 2.58579C4.21071 2.96086 4 3.46957 4 4V20C4 20.5304 4.21071 21.0391 4.58579 21.4142C4.96086 21.7893 5.46957 22 6 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V8L14 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M14 2V8H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <?php _e('Download Empty Template', 'bp-resume-csv'); ?>
                </button>
                <button type="button" id="bprm-export-data" class="btn btn-secondary btn-block">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2V6C12 6.53043 12.2107 7.03914 12.5858 7.41421C12.9609 7.78929 13.4696 8 14 8H18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M18 22H6C5.46957 22 4.96086 21.7893 4.58579 21.4142C4.21071 21.0391 4 20.5304 4 20V4C4 3.46957 4.21071 2.96086 4.58579 2.58579C4.96086 2.21071 4.53043 2 6 2H12L18 8V22Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <?php _e('Export Current Data', 'bp-resume-csv'); ?>
                </button>
            </div>
        </div>

        <!-- Upload Card -->
        <div class="csv-card upload-card">
            <div class="card-header">
                <div class="card-icon upload-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M21 15V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V15" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M17 8L12 3L7 8" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 3V15" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="card-content">
                    <h3><?php _e('Import Data', 'bp-resume-csv'); ?></h3>
                    <p><?php _e('Upload a CSV file to update your resume information. Maximum file size: 5MB.', 'bp-resume-csv'); ?></p>
                </div>
            </div>
            <div class="card-upload-area">
                <form id="bprm-csv-upload-form" enctype="multipart/form-data">
                    <div class="file-upload-zone" id="file-drop-zone">
                        <input type="file" name="csv_file" id="bprm-csv-file" accept=".csv" required>
                        <div class="file-upload-content">
                            <div class="upload-icon-large">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
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
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M14 2H6C5.46957 2 4.96086 2.21071 4.58579 2.58579C4.21071 2.96086 4 3.46957 4 4V20C4 20.5304 4.21071 21.0391 4.58579 21.4142C4.96086 21.7893 5.46957 22 6 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V8L14 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M14 2V8H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <div class="file-details">
                                    <span class="file-name"></span>
                                    <span class="file-size"></span>
                                </div>
                                <button type="button" class="file-remove">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success btn-block upload-btn">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M21 15V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M17 8L12 3L7 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M12 3V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <?php _e('Import CSV Data', 'bp-resume-csv'); ?>
                    </button>
                </form>
                
                <div class="import-warning">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 9V13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 17H12.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span><?php _e('Warning: Uploading CSV data will replace your existing resume information. Make sure to export your current data first if you want to keep a backup.', 'bp-resume-csv'); ?></span>
                </div>
            </div>
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
    
    <!-- Help Section -->
    <div class="csv-help-section">
        <div class="help-header">
            <div class="help-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                    <path d="M9.09 9A3 3 0 0 1 12 6C13.11 6 14.08 6.59 14.64 7.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M12 17V13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <div class="help-text">
                <h3><?php _e('How to use CSV Import/Export', 'bp-resume-csv'); ?></h3>
                <p><?php _e('Follow these simple steps to manage your resume data efficiently', 'bp-resume-csv'); ?></p>
            </div>
        </div>
        
        <div class="help-steps-grid">
            <div class="help-step">
                <div class="step-number">1</div>
                <div class="step-content">
                    <h4><?php _e('Download Template', 'bp-resume-csv'); ?></h4>
                    <p><?php _e('Get the CSV template with proper structure and sample data to understand the format.', 'bp-resume-csv'); ?></p>
                </div>
            </div>
            
            <div class="help-step">
                <div class="step-number">2</div>
                <div class="step-content">
                    <h4><?php _e('Edit Data', 'bp-resume-csv'); ?></h4>
                    <p><?php _e('Open in Excel, Google Sheets, or any spreadsheet application. Fill in your information following the examples.', 'bp-resume-csv'); ?></p>
                </div>
            </div>
            
            <div class="help-step">
                <div class="step-number">3</div>
                <div class="step-content">
                    <h4><?php _e('Import File', 'bp-resume-csv'); ?></h4>
                    <p><?php _e('Save as CSV format and upload using the import form above. Your resume will be updated automatically.', 'bp-resume-csv'); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Tips Grid -->
        <div class="tips-section">
            <h4><?php _e('Important Guidelines', 'bp-resume-csv'); ?></h4>
            <div class="tips-grid">
                <div class="tip-card">
                    <div class="tip-icon">📋</div>
                    <h5><?php _e('Column Headers', 'bp-resume-csv'); ?></h5>
                    <p><?php _e('Never modify the column headers in the CSV file', 'bp-resume-csv'); ?></p>
                </div>
                <div class="tip-card">
                    <div class="tip-icon">📅</div>
                    <h5><?php _e('Date Format', 'bp-resume-csv'); ?></h5>
                    <p><?php _e('Use YYYY-MM-DD format for all dates (e.g., 2024-12-25)', 'bp-resume-csv'); ?></p>
                </div>
                <div class="tip-card">
                    <div class="tip-icon">📧</div>
                    <h5><?php _e('Email Validation', 'bp-resume-csv'); ?></h5>
                    <p><?php _e('Ensure email addresses are in valid format (user@domain.com)', 'bp-resume-csv'); ?></p>
                </div>
                <div class="tip-card">
                    <div class="tip-icon">💾</div>
                    <h5><?php _e('File Size', 'bp-resume-csv'); ?></h5>
                    <p><?php _e('Maximum file size is 5MB. Compress large files if needed', 'bp-resume-csv'); ?></p>
                </div>
            </div>
        </div>
    </div>

</div>

<style>
/* Professional CSV Interface Styles */
.bprm-csv-interface {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    background: #f8fafc;
    min-height: 100vh;
}

/* Header Section */
.csv-header {
    background: #f8fafc;
    color: #1e293b;
    border: 1px solid #e2e8f0;
    padding: 40px 32px;
    margin: -20px -20px 32px -20px;
}

.csv-header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 32px;
    max-width: 1200px;
    margin: 0 auto;
}

.csv-header h2 {
    margin: 0 0 8px 0;
    font-size: 32px;
    font-weight: 700;
    line-height: 1.2;
}

.csv-subtitle {
    margin: 0;
    font-size: 16px;
    opacity: 0.9;
    line-height: 1.5;
}

/* Completion Card */
.completion-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 24px;
    text-align: center;
}

.completion-circle {
    position: relative;
    display: inline-block;
    margin-bottom: 16px;
}

.progress-ring {
    transform: rotate(-90deg);
}

.progress-ring-bg {
    opacity: 0.3;
}

.progress-ring-fill {
    transition: stroke-dashoffset 0.5s ease-in-out;
    stroke: #64748b;
}

.completion-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
}

.completion-percentage {
    display: block;
    font-size: 20px;
    font-weight: 700;
    line-height: 1;
    color: #1e293b;
}

.completion-label {
    display: block;
    font-size: 12px;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.completion-details {
    font-size: 14px;
    color: #64748b;
}

/* Cards Container */
.csv-cards-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 32px;
    margin: 0 32px 48px 32px;
}

/* CSV Cards */
.csv-card {
    background: white;
    border-radius: 16px;
    padding: 32px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.csv-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: #64748b;
}

.csv-card:hover {
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
    transform: translateY(-4px);
}

.card-header {
    display: flex;
    gap: 20px;
    margin-bottom: 24px;
    align-items: flex-start;
}

.card-icon {
    width: 64px;
    height: 64px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.download-icon {
    background: #f1f5f9;
    color: #475569;
}

.upload-icon {
    background: #f0f9ff;
    color: #0369a1;
}

.card-content h3 {
    margin: 0 0 8px 0;
    font-size: 20px;
    font-weight: 600;
    color: #111827;
    line-height: 1.3;
}

.card-content p {
    margin: 0;
    font-size: 14px;
    color: #6b7280;
    line-height: 1.5;
}

/* Button Styles */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 14px 24px;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s ease;
    line-height: 1;
    min-height: 48px;
}

.btn-block {
    width: 100%;
}

.btn-primary {
    background: #64748b;
    color: white;
    box-shadow: 0 2px 4px rgba(100, 116, 139, 0.2);
}

.btn-primary:hover {
    background: #475569;
    box-shadow: 0 4px 6px rgba(100, 116, 139, 0.3);
}

.btn-secondary {
    background: #94a3b8;
    color: white;
    box-shadow: 0 2px 4px rgba(148, 163, 184, 0.2);
}

.btn-secondary:hover {
    background: #64748b;
}

.btn-success {
    background: #0369a1;
    color: white;
    box-shadow: 0 2px 4px rgba(3, 105, 161, 0.2);
}

.btn-success:hover {
    background: #0284c7;
}

.card-actions {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

/* File Upload Area */
.card-upload-area {
    margin-top: 8px;
}

.file-upload-zone {
    border: 2px dashed #d1d5db;
    border-radius: 12px;
    padding: 40px 24px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    margin-bottom: 20px;
    background: #fafbfc;
}

.file-upload-zone:hover {
    border-color: #64748b;
    background: #f8fafc;
}

.file-upload-zone.dragover {
    border-color: #64748b;
    background: #f1f5f9;
    transform: scale(1.02);
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
    color: #9ca3af;
    margin-bottom: 16px;
}

.file-upload-content h4 {
    margin: 0 0 8px 0;
    font-size: 18px;
    font-weight: 600;
    color: #374151;
}

.file-upload-content p {
    margin: 0;
    font-size: 14px;
    color: #6b7280;
}

/* File Selected Info */
.file-selected-info {
    padding: 16px;
    background: #f0fdf4;
    border: 2px solid #bbf7d0;
    border-radius: 12px;
    margin-bottom: 20px;
}

.file-preview {
    display: flex;
    align-items: center;
    gap: 12px;
}

.file-icon {
    color: #059669;
    flex-shrink: 0;
}

.file-details {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.file-name {
    font-weight: 600;
    color: #065f46;
}

.file-size {
    font-size: 12px;
    color: #047857;
}

.file-remove {
    background: none;
    border: none;
    color: #dc2626;
    cursor: pointer;
    padding: 8px;
    border-radius: 6px;
    transition: background-color 0.2s ease;
}

.file-remove:hover {
    background: #fee2e2;
}

/* Import Warning */
.import-warning {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 16px;
    background: #fffbeb;
    border: 1px solid #fbbf24;
    border-radius: 10px;
    font-size: 13px;
    color: #92400e;
    margin-top: 16px;
}

.import-warning svg {
    color: #f59e0b;
    flex-shrink: 0;
    margin-top: 1px;
}

/* Progress Container */
.csv-progress-container {
    margin: 24px 32px;
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.progress-wrapper {
    max-width: 400px;
    margin: 0 auto;
}

.progress-bar {
    width: 100%;
    height: 12px;
    background: #f3f4f6;
    border-radius: 6px;
    overflow: hidden;
    margin-bottom: 12px;
}

.progress-fill {
    height: 100%;
    background: #64748b;
    border-radius: 6px;
    transition: width 0.3s ease;
}

.progress-info {
    display: flex;
    justify-content: space-between;
    font-size: 14px;
    color: #6b7280;
}

/* Messages */
.csv-messages {
    margin: 24px 32px;
}

.csv-message {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 16px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.csv-message.success {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    color: #065f46;
}

.csv-message.error {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #991b1b;
}

.csv-message.info {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    color: #1e40af;
}

.message-dismiss {
    background: none;
    border: none;
    color: currentColor;
    cursor: pointer;
    padding: 4px;
    border-radius: 4px;
    opacity: 0.6;
    margin-left: auto;
    flex-shrink: 0;
}

.message-dismiss:hover {
    opacity: 1;
    background: rgba(0, 0, 0, 0.1);
}

/* Help Section */
.csv-help-section {
    background: white;
    border-radius: 16px;
    padding: 40px;
    margin: 32px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.help-header {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 32px;
    text-align: left;
}

.help-icon {
    width: 48px;
    height: 48px;
    background: #f1f5f9;
    color: #475569;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.help-text h3 {
    margin: 0 0 4px 0;
    font-size: 24px;
    font-weight: 600;
    color: #111827;
}

.help-text p {
    margin: 0;
    font-size: 16px;
    color: #6b7280;
}

/* Help Steps */
.help-steps-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 24px;
    margin-bottom: 40px;
}

.help-step {
    display: flex;
    gap: 16px;
    padding: 24px;
    background: #f8fafc;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
}

.step-number {
    width: 40px;
    height: 40px;
    background: #64748b;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 18px;
    flex-shrink: 0;
}

.step-content h4 {
    margin: 0 0 8px 0;
    font-size: 16px;
    font-weight: 600;
    color: #111827;
}

.step-content p {
    margin: 0;
    font-size: 14px;
    color: #6b7280;
    line-height: 1.5;
}

/* Tips Section */
.tips-section h4 {
    margin: 0 0 24px 0;
    font-size: 20px;
    font-weight: 600;
    color: #111827;
}

.tips-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.tip-card {
    background: #fafbfc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    transition: all 0.2s ease;
}

.tip-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.tip-icon {
    font-size: 32px;
    margin-bottom: 12px;
}

.tip-card h5 {
    margin: 0 0 8px 0;
    font-size: 16px;
    font-weight: 600;
    color: #111827;
}

.tip-card p {
    margin: 0;
    font-size: 13px;
    color: #6b7280;
    line-height: 1.4;
}

/* Loading States */
.btn.loading {
    opacity: 0.8;
    pointer-events: none;
    position: relative;
}

.btn.loading::after {
    content: '';
    position: absolute;
    width: 16px;
    height: 16px;
    margin: auto;
    border: 2px solid transparent;
    border-top: 2px solid currentColor;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Responsive Design */
@media (max-width: 768px) {
    .bprm-csv-interface {
        padding: 0 16px;
    }
    
    .csv-header {
        padding: 24px 16px;
        margin: -20px -16px 24px -16px;
    }
    
    .csv-header-content {
        flex-direction: column;
        text-align: center;
        gap: 24px;
    }
    
    .csv-cards-container {
        grid-template-columns: 1fr;
        gap: 24px;
        margin: 0 0 32px 0;
    }
    
    .csv-card {
        padding: 24px;
    }
    
    .help-steps-grid {
        grid-template-columns: 1fr;
    }
    
    .tips-grid {
        grid-template-columns: 1fr;
    }
    
    .csv-help-section {
        padding: 24px;
        margin: 24px 0;
    }
    
    .csv-header h2 {
        font-size: 24px;
    }
}

@media (max-width: 480px) {
    .card-header {
        flex-direction: column;
        text-align: center;
        gap: 16px;
    }
    
    .help-header {
        flex-direction: column;
        text-align: center;
    }
    
    .file-upload-zone {
        padding: 24px 16px;
    }
}
</style>