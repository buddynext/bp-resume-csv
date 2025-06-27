// assets/js/csv-handler.js
(function($) {
    'use strict';

    // Debug logging
    function debugLog(message, data) {
        if (typeof console !== 'undefined') {
            console.log('[BP Resume CSV]', message, data || '');
        }
    }

    // Wait for DOM to be ready
    $(document).ready(function() {
        debugLog('Initializing CSV Handler');
        debugLog('bprm_csv_ajax object:', bprm_csv_ajax);
        initializeCsvHandler();
    });

    function initializeCsvHandler() {
        // Check if required variables exist
        if (typeof bprm_csv_ajax === 'undefined') {
            debugLog('ERROR: bprm_csv_ajax is not defined!');
            return;
        }

        // Download template CSV
        $('#bprm-download-template').on('click', function(e) {
            e.preventDefault();
            debugLog('Download template button clicked');
            downloadCsv('template');
        });

        // Export current data
        $('#bprm-export-data').on('click', function(e) {
            e.preventDefault();
            debugLog('Export data button clicked');
            downloadCsv('export');
        });

        // File input change handler
        $('#bprm-csv-file').on('change', function() {
            debugLog('File input changed');
            handleFileSelection(this);
        });

        // Upload CSV form handler
        $('#bprm-csv-upload-form').on('submit', function(e) {
            e.preventDefault();
            debugLog('Upload form submitted');
            handleCsvUpload();
        });

        // File remove handler
        $(document).on('click', '.file-remove', function() {
            debugLog('File remove clicked');
            clearFileSelection();
        });

        // Drag and drop functionality
        initializeDragAndDrop();

        debugLog('CSV Handler initialized successfully');
    }

    /**
     * Download CSV (template or export) - FIXED VERSION
     */
    function downloadCsv(type) {
        debugLog('Starting download for type:', type);
        
        const action = type === 'export' ? 'bprm_export_current_data' : 'bprm_download_sample_csv';
        const button = type === 'export' ? $('#bprm-export-data') : $('#bprm-download-template');
        
        debugLog('Action:', action);
        debugLog('Button found:', button.length > 0);
        
        // Set loading state
        setButtonLoading(button, true);

        // Use jQuery.ajax instead of fetch for better compatibility
        $.ajax({
            url: bprm_csv_ajax.ajax_url,
            type: 'POST',
            data: {
                action: action,
                nonce: bprm_csv_ajax.nonce
            },
            xhrFields: {
                responseType: 'blob'
            },
            success: function(data, textStatus, xhr) {
                debugLog('Download successful');
                
                // Create blob and download
                const blob = new Blob([data], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = type === 'export' ? 'resume_data_' + getCurrentTimestamp() + '.csv' : 'resume_template_' + getCurrentTimestamp() + '.csv';
                
                // Trigger download
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                window.URL.revokeObjectURL(url);
                
                const message = type === 'export' 
                    ? bprm_csv_ajax.messages.export_success 
                    : 'Template downloaded successfully!';
                showMessage(message, 'success');
            },
            error: function(xhr, textStatus, errorThrown) {
                debugLog('Download error:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    responseText: xhr.responseText,
                    textStatus: textStatus,
                    errorThrown: errorThrown
                });
                
                let errorMessage = 'Download failed. ';
                if (xhr.status === 0) {
                    errorMessage += 'Network error - please check your connection.';
                } else if (xhr.status === 403) {
                    errorMessage += 'Permission denied - please log in and try again.';
                } else if (xhr.status === 404) {
                    errorMessage += 'Download endpoint not found.';
                } else if (xhr.status === 500) {
                    errorMessage += 'Server error - please try again later.';
                } else {
                    errorMessage += 'Error code: ' + xhr.status;
                }
                
                showMessage(errorMessage, 'error');
            },
            complete: function() {
                setButtonLoading(button, false);
            }
        });
    }

    /**
     * Get current timestamp for filename
     */
    function getCurrentTimestamp() {
        const now = new Date();
        return now.getFullYear() + '-' + 
               String(now.getMonth() + 1).padStart(2, '0') + '-' + 
               String(now.getDate()).padStart(2, '0') + '_' + 
               String(now.getHours()).padStart(2, '0') + '-' + 
               String(now.getMinutes()).padStart(2, '0') + '-' + 
               String(now.getSeconds()).padStart(2, '0');
    }

    /**
     * Handle file selection
     */
    function handleFileSelection(input) {
        debugLog('Handling file selection');
        
        const fileUploadArea = $('#file-drop-zone');
        const fileSelectedInfo = $('.file-selected-info');
        const fileUploadContent = $('.file-upload-content');
        
        if (input.files && input.files.length > 0) {
            const file = input.files[0];
            const fileName = file.name;
            const fileSize = formatFileSize(file.size);
            
            debugLog('File selected:', fileName, 'Size:', fileSize);
            
            // Validate file type
            if (!fileName.toLowerCase().endsWith('.csv')) {
                showMessage('Please select a CSV file.', 'error');
                clearFileSelection();
                return;
            }
            
            // Validate file size (max 5MB)
            if (file.size > 5 * 1024 * 1024) {
                showMessage('File size must be less than 5MB.', 'error');
                clearFileSelection();
                return;
            }
            
            // Update UI
            $('.file-name').text(fileName);
            $('.file-size').text(fileSize);
            fileUploadContent.hide();
            fileSelectedInfo.show();
            fileUploadArea.addClass('has-file');
            
            // Show preview
            showFilePreview(file);
            
        } else {
            clearFileSelection();
        }
    }

    /**
     * Clear file selection
     */
    function clearFileSelection() {
        debugLog('Clearing file selection');
        $('#bprm-csv-file').val('');
        $('.file-selected-info').hide();
        $('.file-upload-content').show();
        $('#file-drop-zone').removeClass('has-file');
        $('.csv-file-preview').hide();
    }

    /**
     * Handle CSV upload
     */
    function handleCsvUpload() {
        debugLog('Starting CSV upload');
        
        const fileInput = $('#bprm-csv-file')[0];
        const submitButton = $('#bprm-csv-upload-form button[type="submit"]');
        
        if (!fileInput.files.length) {
            showMessage(bprm_csv_ajax.messages.file_required, 'error');
            return;
        }

        // Confirm import
        if (!confirm(bprm_csv_ajax.messages.confirm_import)) {
            return;
        }

        // Validate file
        const file = fileInput.files[0];
        if (!file.name.toLowerCase().endsWith('.csv')) {
            showMessage('Please select a CSV file.', 'error');
            return;
        }

        debugLog('Uploading file:', file.name);

        // Create form data
        const formData = new FormData();
        formData.append('action', 'bprm_upload_csv_data');
        formData.append('nonce', bprm_csv_ajax.nonce);
        formData.append('csv_file', file);

        // Set loading state
        setButtonLoading(submitButton, true);
        showProgress(0);

        // Perform upload
        $.ajax({
            url: bprm_csv_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                const xhr = new window.XMLHttpRequest();
                
                // Upload progress
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const percentComplete = (e.loaded / e.total) * 100;
                        showProgress(percentComplete);
                    }
                }, false);
                
                return xhr;
            },
            success: function(response) {
                debugLog('Upload response:', response);
                
                setButtonLoading(submitButton, false);
                hideProgress();
                
                if (response.success) {
                    showMessage(response.data.message, 'success');
                    clearFileSelection();
                    
                    // Optionally reload page after successful import
                    setTimeout(() => {
                        if (confirm('Data imported successfully! Would you like to reload the page to see the changes?')) {
                            window.location.reload();
                        }
                    }, 2000);
                } else {
                    showMessage(response.data.message || 'Import failed', 'error');
                }
            },
            error: function(xhr, status, error) {
                debugLog('Upload error:', xhr, status, error);
                
                setButtonLoading(submitButton, false);
                hideProgress();
                
                let errorMessage = bprm_csv_ajax.messages.upload_error;
                
                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    errorMessage = xhr.responseJSON.data.message;
                } else if (xhr.status === 413) {
                    errorMessage = 'File too large. Please try a smaller file.';
                } else if (xhr.status === 0) {
                    errorMessage = 'Network error. Please check your connection and try again.';
                }
                
                showMessage(errorMessage, 'error');
            }
        });
    }

    /**
     * Initialize drag and drop functionality
     */
    function initializeDragAndDrop() {
        const dropZone = $('#file-drop-zone');
        
        if (!dropZone.length) return;

        debugLog('Initializing drag and drop');

        // Prevent default drag behaviors
        $(document).on('dragenter dragover drop', function(e) {
            e.preventDefault();
        });

        // Drop zone events
        dropZone.on('dragenter dragover', function(e) {
            e.preventDefault();
            $(this).addClass('dragover');
        });

        dropZone.on('dragleave', function(e) {
            if (!$.contains(this, e.relatedTarget)) {
                $(this).removeClass('dragover');
            }
        });

        dropZone.on('drop', function(e) {
            e.preventDefault();
            $(this).removeClass('dragover');
            
            const files = e.originalEvent.dataTransfer.files;
            if (files.length > 0) {
                const fileInput = $('#bprm-csv-file')[0];
                fileInput.files = files;
                handleFileSelection(fileInput);
            }
        });
    }

    /**
     * Set button loading state
     */
    function setButtonLoading(button, loading) {
        if (loading) {
            button.addClass('loading')
                  .prop('disabled', true)
                  .data('original-text', button.text())
                  .text(bprm_csv_ajax.messages.processing);
        } else {
            button.removeClass('loading')
                  .prop('disabled', false)
                  .text(button.data('original-text') || button.text().replace(bprm_csv_ajax.messages.processing, '').trim());
        }
    }

    /**
     * Show progress bar
     */
    function showProgress(percent) {
        let progressContainer = $('#csv-progress');
        
        if (!progressContainer.length) {
            progressContainer = $(`
                <div id="csv-progress" class="csv-progress">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 0%"></div>
                    </div>
                    <div class="progress-text">
                        <span class="progress-label">Processing...</span>
                        <span class="progress-percentage">0%</span>
                    </div>
                </div>
            `);
            $('#bprm-csv-upload-form').after(progressContainer);
        }
        
        progressContainer.find('.progress-fill').css('width', percent + '%');
        progressContainer.find('.progress-percentage').text(Math.round(percent) + '%');
        progressContainer.show();
    }

    /**
     * Hide progress bar
     */
    function hideProgress() {
        $('#csv-progress').fadeOut(300);
    }

    /**
     * Show message to user
     */
    function showMessage(message, type) {
        debugLog('Showing message:', type, message);
        
        const messageClass = type === 'success' ? 'success' : (type === 'error' ? 'error' : 'info');
        const icon = type === 'success' ? 
            '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 12L11 14L15 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>' :
            (type === 'error' ? 
            '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 8V12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 16H12.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>' :
            '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 16V12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 8H12.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>');
        
        const messageHtml = `
            <div class="csv-message ${messageClass}">
                ${icon}
                <span>${message}</span>
                <button type="button" class="message-dismiss" onclick="$(this).parent().fadeOut()">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>
        `;
        
        const messagesContainer = $('#bprm-csv-messages');
        messagesContainer.html(messageHtml).show();
        
        // Auto-hide success messages
        if (type === 'success') {
            setTimeout(function() {
                messagesContainer.find('.csv-message').fadeOut();
            }, 5000);
        }
        
        // Scroll to message
        if (messagesContainer.length && messagesContainer.offset()) {
            $('html, body').animate({
                scrollTop: messagesContainer.offset().top - 100
            }, 300);
        }
    }

    /**
     * Format file size for display
     */
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    /**
     * Show file preview
     */
    function showFilePreview(file) {
        return new Promise(function(resolve, reject) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                const text = e.target.result;
                const lines = text.split('\n').slice(0, 10); // First 10 lines
                
                const preview = lines
                    .filter(line => line.trim() && !line.startsWith('#'))
                    .slice(0, 5) // Show max 5 data rows
                    .map(line => {
                        const cols = line.split(',');
                        return cols.slice(0, 4).join(' | '); // Show first 4 columns
                    })
                    .join('\n');
                
                // Show preview in UI
                let previewContainer = $('.csv-file-preview');
                if (!previewContainer.length) {
                    previewContainer = $(`
                        <div class="csv-file-preview" style="margin-top: 16px; padding: 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
                            <h5 style="margin: 0 0 8px 0; font-size: 14px; font-weight: 600; color: #374151;">File Preview:</h5>
                            <pre style="margin: 0; font-size: 12px; line-height: 1.4; color: #6b7280; white-space: pre-wrap; max-height: 120px; overflow-y: auto;"></pre>
                        </div>
                    `);
                    $('.file-selected-info').after(previewContainer);
                }
                previewContainer.find('pre').text(preview);
                previewContainer.show();
                
                resolve(preview);
            };
            
            reader.onerror = function() {
                reject('Error reading file for preview.');
            };
            
            reader.readAsText(file.slice(0, 2048)); // Read first 2KB
        });
    }

    // Debug: Log when buttons are found
    $(document).ready(function() {
        setTimeout(function() {
            debugLog('Download template button exists:', $('#bprm-download-template').length > 0);
            debugLog('Export data button exists:', $('#bprm-export-data').length > 0);
            debugLog('Upload form exists:', $('#bprm-csv-upload-form').length > 0);
        }, 1000);
    });

    // Error handling
    window.addEventListener('error', function(e) {
        if (e.filename && e.filename.includes('csv-handler.js')) {
            debugLog('JavaScript Error:', e.error);
        }
    });

    // Expose public methods for external use and debugging
    window.BPResumeCSV = {
        downloadTemplate: function() { downloadCsv('template'); },
        exportData: function() { downloadCsv('export'); },
        showMessage: showMessage,
        clearFileSelection: clearFileSelection,
        debugLog: debugLog
    };

    // Add message dismiss functionality
    $(document).on('click', '.message-dismiss', function(e) {
        e.preventDefault();
        $(this).parent().fadeOut(200);
    });

})(jQuery);