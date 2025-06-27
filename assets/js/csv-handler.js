// assets/js/csv-handler.js
(function($) {
    'use strict';

    // Wait for DOM to be ready
    $(document).ready(function() {
        initializeCsvHandler();
    });

    function initializeCsvHandler() {
        // Download template CSV
        $('#bprm-download-template').on('click', function(e) {
            e.preventDefault();
            downloadCsv('template');
        });

        // Export current data
        $('#bprm-export-data').on('click', function(e) {
            e.preventDefault();
            downloadCsv('export');
        });

        // File input change handler
        $('#bprm-csv-file').on('change', function() {
            handleFileSelection(this);
        });

        // Upload CSV form handler
        $('#bprm-csv-upload-form').on('submit', function(e) {
            e.preventDefault();
            handleCsvUpload();
        });

        // Drag and drop functionality
        initializeDragAndDrop();

        // Initialize tooltips if available
        if (typeof $.fn.tooltip === 'function') {
            $('.bprm-csv-interface [title]').tooltip();
        }
    }

    /**
     * Download CSV (template or export)
     */
    function downloadCsv(type) {
        const action = type === 'export' ? 'bprm_export_current_data' : 'bprm_download_sample_csv';
        const button = type === 'export' ? $('#bprm-export-data') : $('#bprm-download-template');
        
        // Set loading state
        setButtonLoading(button, true);

        // Create and submit form
        const form = $('<form>', {
            'method': 'POST',
            'action': bprm_csv_ajax.ajax_url,
            'style': 'display: none;'
        });

        form.append($('<input>', {
            'type': 'hidden',
            'name': 'action',
            'value': action
        }));

        form.append($('<input>', {
            'type': 'hidden',
            'name': 'nonce',
            'value': bprm_csv_ajax.nonce
        }));

        $('body').append(form);
        
        // Submit form and clean up
        form.submit();
        
        setTimeout(function() {
            form.remove();
            setButtonLoading(button, false);
            
            const message = type === 'export' 
                ? bprm_csv_ajax.messages.export_success 
                : 'Template downloaded successfully!';
            showMessage(message, 'success');
        }, 1000);
    }

    /**
     * Handle file selection
     */
    function handleFileSelection(input) {
        const fileNameSpan = $('.file-name');
        const wrapper = $('.bprm-file-input-wrapper');
        
        if (input.files && input.files.length > 0) {
            const fileName = input.files[0].name;
            const fileSize = formatFileSize(input.files[0].size);
            fileNameSpan.text(`${fileName} (${fileSize})`);
            wrapper.addClass('has-file');
            
            // Validate file type
            if (!fileName.toLowerCase().endsWith('.csv')) {
                showMessage('Please select a CSV file.', 'error');
                clearFileSelection();
                return;
            }
            
            // Validate file size (max 5MB)
            if (input.files[0].size > 5 * 1024 * 1024) {
                showMessage('File size must be less than 5MB.', 'error');
                clearFileSelection();
                return;
            }
            
        } else {
            clearFileSelection();
        }
    }

    /**
     * Clear file selection
     */
    function clearFileSelection() {
        $('#bprm-csv-file').val('');
        $('.file-name').text('');
        $('.bprm-file-input-wrapper').removeClass('has-file');
    }

    /**
     * Handle CSV upload
     */
    function handleCsvUpload() {
        const fileInput = $('#bprm-csv-file')[0];
        const submitButton = $('#bprm-csv-upload-form button[type="submit"]');
        
        if (!fileInput.files.length) {
            showMessage(bprm_csv_ajax.messages.file_required, 'error');
            return;
        }

        // Validate file
        const file = fileInput.files[0];
        if (!file.name.toLowerCase().endsWith('.csv')) {
            showMessage('Please select a CSV file.', 'error');
            return;
        }

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
                setButtonLoading(submitButton, false);
                hideProgress();
                
                if (response.success) {
                    showMessage(response.data.message, 'success');
                    clearFileSelection();
                    
                    // Optionally reload page after successful import
                    if (confirm('Data imported successfully! Would you like to reload the page to see the changes?')) {
                        window.location.reload();
                    }
                } else {
                    showMessage(response.data.message, 'error');
                }
            },
            error: function(xhr, status, error) {
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
        const dropZone = $('.bprm-csv-section').has('#bprm-csv-upload-form');
        
        if (!dropZone.length) return;

        // Prevent default drag behaviors
        $(document).on('dragenter dragover drop', function(e) {
            e.preventDefault();
        });

        // Drop zone events
        dropZone.on('dragenter dragover', function(e) {
            e.preventDefault();
            $(this).addClass('drag-over');
        });

        dropZone.on('dragleave', function(e) {
            // Only remove class if we're leaving the drop zone entirely
            if (!$.contains(this, e.relatedTarget)) {
                $(this).removeClass('drag-over');
            }
        });

        dropZone.on('drop', function(e) {
            e.preventDefault();
            $(this).removeClass('drag-over');
            
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
            button.addClass('processing')
                  .prop('disabled', true)
                  .data('original-text', button.text())
                  .text(bprm_csv_ajax.messages.processing);
        } else {
            button.removeClass('processing')
                  .prop('disabled', false)
                  .text(button.data('original-text') || button.text().replace(bprm_csv_ajax.messages.processing, '').trim());
        }
    }

    /**
     * Show progress bar
     */
    function showProgress(percent) {
        let progressContainer = $('.bprm-progress');
        
        if (!progressContainer.length) {
            progressContainer = $('<div class="bprm-progress"><div class="bprm-progress-bar"></div></div>');
            $('#bprm-csv-upload-form').after(progressContainer);
        }
        
        progressContainer.find('.bprm-progress-bar').css('width', percent + '%');
        progressContainer.show();
    }

    /**
     * Hide progress bar
     */
    function hideProgress() {
        $('.bprm-progress').fadeOut(300, function() {
            $(this).remove();
        });
    }

    /**
     * Show message to user
     */
    function showMessage(message, type) {
        const messageClass = type === 'success' ? 'notice-success' : 'notice-error';
        const icon = type === 'success' ? 'yes-alt' : 'warning';
        
        const messageHtml = `
            <div class="notice ${messageClass}">
                <p>
                    <span class="dashicons dashicons-${icon}"></span>
                    ${message}
                </p>
            </div>
        `;
        
        const messagesContainer = $('#bprm-csv-messages');
        messagesContainer.html(messageHtml).show();
        
        // Auto-hide success messages
        if (type === 'success') {
            setTimeout(function() {
                messagesContainer.fadeOut();
            }, 5000);
        }
        
        // Scroll to message
        $('html, body').animate({
            scrollTop: messagesContainer.offset().top - 50
        }, 300);
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
     * Validate CSV structure (basic client-side validation)
     */
    function validateCsvStructure(file) {
        return new Promise(function(resolve, reject) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                const text = e.target.result;
                const lines = text.split('\n');
                
                // Check if file has content
                if (lines.length < 2) {
                    reject('CSV file appears to be empty or has no data rows.');
                    return;
                }
                
                // Look for required headers
                const headerLine = lines.find(line => !line.startsWith('#') && line.trim() !== '');
                if (!headerLine) {
                    reject('Could not find header row in CSV file.');
                    return;
                }
                
                const headers = headerLine.split(',').map(h => h.trim().toLowerCase());
                const requiredHeaders = ['group_key', 'field_key', 'field_value'];
                
                const missingHeaders = requiredHeaders.filter(header => 
                    !headers.some(h => h.includes(header))
                );
                
                if (missingHeaders.length > 0) {
                    reject(`Missing required headers: ${missingHeaders.join(', ')}`);
                    return;
                }
                
                resolve(true);
            };
            
            reader.onerror = function() {
                reject('Error reading file.');
            };
            
            // Read only first 1KB for validation
            reader.readAsText(file.slice(0, 1024));
        });
    }

    /**
     * Enhanced file validation
     */
    function validateFile(file) {
        return new Promise(function(resolve, reject) {
            // Check file type
            if (!file.name.toLowerCase().endsWith('.csv')) {
                reject('Please select a CSV file.');
                return;
            }
            
            // Check file size (max 5MB)
            if (file.size > 5 * 1024 * 1024) {
                reject('File size must be less than 5MB.');
                return;
            }
            
            // Check if file is empty
            if (file.size === 0) {
                reject('The selected file is empty.');
                return;
            }
            
            // Validate CSV structure
            validateCsvStructure(file)
                .then(resolve)
                .catch(reject);
        });
    }

    // Enhanced upload with validation
    function handleCsvUploadEnhanced() {
        const fileInput = $('#bprm-csv-file')[0];
        const submitButton = $('#bprm-csv-upload-form button[type="submit"]');
        
        if (!fileInput.files.length) {
            showMessage(bprm_csv_ajax.messages.file_required, 'error');
            return;
        }

        const file = fileInput.files[0];
        
        // Show validation progress
        showMessage('Validating file...', 'info');
        
        validateFile(file)
            .then(function() {
                // File is valid, proceed with upload
                handleCsvUpload();
            })
            .catch(function(error) {
                showMessage(error, 'error');
            });
    }

    // Replace the original upload handler
    $('#bprm-csv-upload-form').off('submit').on('submit', function(e) {
        e.preventDefault();
        handleCsvUploadEnhanced();
    });

    /**
     * Show info message
     */
    function showInfoMessage(message) {
        showMessage(message, 'info');
    }

    // Add CSS for drag and drop styling
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .bprm-csv-section.drag-over {
                border: 2px dashed #0073aa;
                background-color: #f0f6fc;
                transform: scale(1.02);
                transition: all 0.2s ease;
            }
            .notice.notice-info {
                border-left-color: #0073aa;
                background: #f0f6fc;
            }
            .bprm-csv-section {
                transition: all 0.2s ease;
            }
        `)
        .appendTo('head');

    // Keyboard accessibility
    $(document).on('keydown', function(e) {
        // ESC key to cancel operations
        if (e.keyCode === 27) {
            // Cancel any ongoing uploads
            if ($('.bprm-csv-interface .processing').length) {
                if (confirm('Cancel the current operation?')) {
                    window.location.reload();
                }
            }
        }
    });

    // Auto-save form state
    function saveFormState() {
        const fileInput = $('#bprm-csv-file')[0];
        if (fileInput.files.length > 0) {
            sessionStorage.setItem('bprm_csv_file_name', fileInput.files[0].name);
        }
    }

    function restoreFormState() {
        const savedFileName = sessionStorage.getItem('bprm_csv_file_name');
        if (savedFileName) {
            $('.file-name').text(`Previously selected: ${savedFileName}`);
            sessionStorage.removeItem('bprm_csv_file_name');
        }
    }

    // Initialize form state
    restoreFormState();

    // Save state on file change
    $('#bprm-csv-file').on('change', saveFormState);

    // Add confirmation for data replacement
    function confirmDataReplacement() {
        return confirm(
            'Importing CSV data will replace your existing resume information. ' +
            'Make sure you have exported your current data if you want to keep a backup. ' +
            'Do you want to continue?'
        );
    }

    // Enhanced upload with confirmation
    function handleCsvUploadWithConfirmation() {
        if (!confirmDataReplacement()) {
            return;
        }
        handleCsvUploadEnhanced();
    }

    // Update the form handler to include confirmation
    $('#bprm-csv-upload-form').off('submit').on('submit', function(e) {
        e.preventDefault();
        handleCsvUploadWithConfirmation();
    });

    // Add preview functionality
    function previewCsvData(file) {
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
                
                resolve(preview);
            };
            
            reader.onerror = function() {
                reject('Error reading file for preview.');
            };
            
            reader.readAsText(file.slice(0, 2048)); // Read first 2KB
        });
    }

    // Show preview when file is selected
    $('#bprm-csv-file').on('change', function() {
        handleFileSelection(this);
        
        if (this.files && this.files.length > 0) {
            const file = this.files[0];
            
            previewCsvData(file)
                .then(function(preview) {
                    let previewContainer = $('.csv-preview');
                    if (!previewContainer.length) {
                        previewContainer = $('<div class="csv-preview"><h5>File Preview:</h5><pre></pre></div>');
                        $('#bprm-csv-upload-form').after(previewContainer);
                    }
                    previewContainer.find('pre').text(preview);
                    previewContainer.show();
                })
                .catch(function(error) {
                    console.warn('Could not generate preview:', error);
                });
        } else {
            $('.csv-preview').hide();
        }
    });

    // Add styles for preview
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .csv-preview {
                margin: 15px 0;
                padding: 15px;
                background: #f8f9fa;
                border: 1px solid #dee2e6;
                border-radius: 4px;
            }
            .csv-preview h5 {
                margin: 0 0 10px 0;
                color: #495057;
            }
            .csv-preview pre {
                margin: 0;
                background: #fff;
                padding: 10px;
                border: 1px solid #e9ecef;
                border-radius: 3px;
                font-size: 12px;
                line-height: 1.4;
                max-height: 150px;
                overflow-y: auto;
            }
        `)
        .appendTo('head');

    // Analytics/tracking (if needed)
    function trackEvent(action, label) {
        if (typeof gtag !== 'undefined') {
            gtag('event', action, {
                'event_category': 'BP Resume CSV',
                'event_label': label
            });
        }
    }

    // Track user actions
    $('#bprm-download-template').on('click', function() {
        trackEvent('download', 'template');
    });

    $('#bprm-export-data').on('click', function() {
        trackEvent('download', 'export');
    });

    $('#bprm-csv-upload-form').on('submit', function() {
        trackEvent('upload', 'csv_data');
    });

    // Error reporting
    window.addEventListener('error', function(e) {
        if (e.filename && e.filename.includes('csv-handler.js')) {
            console.error('CSV Handler Error:', e.error);
            showMessage('An unexpected error occurred. Please refresh the page and try again.', 'error');
        }
    });

    // Expose public methods for external use
    window.BPResumeCSV = {
        downloadTemplate: function() { downloadCsv('template'); },
        exportData: function() { downloadCsv('export'); },
        showMessage: showMessage,
        validateFile: validateFile
    };

})(jQuery);