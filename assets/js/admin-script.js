// assets/js/admin-script.js
(function($) {
    'use strict';

    $(document).ready(function() {
        initializeAdminFunctionality();
    });

    function initializeAdminFunctionality() {
        // Load statistics
        $('#load-stats').on('click', loadUsageStatistics);
        
        // Test functionality
        $('#test-functionality').on('click', testPluginFunctionality);
        
        // Reset settings
        $('#reset-settings').on('click', resetPluginSettings);
        
        // Auto-refresh stats every 30 seconds if visible
        setInterval(function() {
            if ($('#usage-stats-container .stats-grid').is(':visible')) {
                loadUsageStatistics(true); // Silent refresh
            }
        }, 30000);
    }

    /**
     * Load usage statistics
     */
    function loadUsageStatistics(silent = false) {
        const button = $('#load-stats');
        const container = $('#usage-stats-container');
        
        if (!silent) {
            setButtonLoading(button, true);
            container.addClass('loading-overlay');
        }

        $.ajax({
            url: bprm_csv_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'bprm_csv_admin_action',
                action_type: 'get_stats',
                nonce: bprm_csv_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    renderStatistics(response.data);
                    if (!silent) {
                        showAdminMessage('Statistics loaded successfully', 'success');
                    }
                } else {
                    showAdminMessage('Failed to load statistics: ' + response.data, 'error');
                }
            },
            error: function() {
                showAdminMessage('Network error while loading statistics', 'error');
            },
            complete: function() {
                if (!silent) {
                    setButtonLoading(button, false);
                    container.removeClass('loading-overlay');
                }
            }
        });
    }

    /**
     * Render statistics
     */
    function renderStatistics(data) {
        const container = $('#usage-stats-container');
        
        const statsHtml = `
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-number">${data.users_with_resume}</span>
                    <span class="stat-label">Users with Resume Data</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">${data.total_resume_fields}</span>
                    <span class="stat-label">Total Resume Fields</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">${data.csv_imports_count}</span>
                    <span class="stat-label">Total CSV Imports</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">${data.recent_imports.length}</span>
                    <span class="stat-label">Recent Imports</span>
                </div>
            </div>
            
            ${data.recent_imports.length > 0 ? `
                <h4>Recent Import Activity</h4>
                <div class="recent-imports">
                    ${data.recent_imports.map(importItem => `
                        <div class="import-item">
                            <div class="import-details">
                                <div class="import-user">User ID: ${importItem.user_id}</div>
                                <div class="import-action">${importItem.action}: ${importItem.details}</div>
                            </div>
                            <div class="import-timestamp">${formatTimestamp(importItem.timestamp)}</div>
                        </div>
                    `).join('')}
                </div>
            ` : '<p>No recent import activity</p>'}
        `;
        
        container.html(statsHtml);
    }

    /**
     * Test plugin functionality
     */
    function testPluginFunctionality() {
        const button = $('#test-functionality');
        
        setButtonLoading(button, true);

        $.ajax({
            url: bprm_csv_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'bprm_csv_admin_action',
                action_type: 'test_functionality',
                nonce: bprm_csv_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    renderTestResults(response.data);
                    showAdminMessage('Functionality test completed', 'success');
                } else {
                    showAdminMessage('Test failed: ' + response.data, 'error');
                }
            },
            error: function() {
                showAdminMessage('Network error during testing', 'error');
            },
            complete: function() {
                setButtonLoading(button, false);
            }
        });
    }

    /**
     * Render test results
     */
    function renderTestResults(data) {
        const resultsHtml = `
            <div class="test-results">
                <h4>Test Results</h4>
                
                <div class="test-section">
                    <h4>Classes</h4>
                    ${Object.entries(data.classes).map(([className, exists]) => `
                        <div class="test-item ${exists ? 'test-pass' : 'test-fail'}">
                            <span class="dashicons dashicons-${exists ? 'yes-alt' : 'dismiss'}"></span>
                            <span>${className}: ${exists ? 'Loaded' : 'Not Found'}</span>
                        </div>
                    `).join('')}
                </div>
                
                <div class="test-section">
                    <h4>Dependencies</h4>
                    ${Object.entries(data.dependencies).map(([dep, available]) => `
                        <div class="test-item ${available ? 'test-pass' : 'test-fail'}">
                            <span class="dashicons dashicons-${available ? 'yes-alt' : 'dismiss'}"></span>
                            <span>${dep}: ${available ? 'Available' : 'Missing'}</span>
                        </div>
                    `).join('')}
                </div>
                
                <div class="test-section">
                    <h4>AJAX Endpoints</h4>
                    ${Object.entries(data.ajax_endpoints).map(([endpoint, registered]) => `
                        <div class="test-item ${registered ? 'test-pass' : 'test-fail'}">
                            <span class="dashicons dashicons-${registered ? 'yes-alt' : 'dismiss'}"></span>
                            <span>${endpoint}: ${registered ? 'Registered' : 'Not Registered'}</span>
                        </div>
                    `).join('')}
                </div>
                
                <div class="test-section">
                    <h4>File Permissions</h4>
                    <div class="test-item ${data.file_permissions ? 'test-pass' : 'test-fail'}">
                        <span class="dashicons dashicons-${data.file_permissions ? 'yes-alt' : 'dismiss'}"></span>
                        <span>Upload Directory: ${data.file_permissions ? 'Writable' : 'Not Writable'}</span>
                    </div>
                </div>
            </div>
        `;
        
        // Find a suitable container or create one
        let container = $('.test-results-container');
        if (!container.length) {
            container = $('<div class="test-results-container"></div>');
            $('#test-functionality').after(container);
        }
        
        container.html(resultsHtml);
    }

    /**
     * Reset plugin settings
     */
    function resetPluginSettings() {
        if (!confirm(bprm_csv_admin.strings.confirm_reset)) {
            return;
        }

        const button = $('#reset-settings');
        
        setButtonLoading(button, true);

        $.ajax({
            url: bprm_csv_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'bprm_csv_admin_action',
                action_type: 'reset_settings',
                nonce: bprm_csv_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showAdminMessage('Settings reset successfully. Page will reload.', 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    showAdminMessage('Failed to reset settings: ' + response.data, 'error');
                }
            },
            error: function() {
                showAdminMessage('Network error during reset', 'error');
            },
            complete: function() {
                setButtonLoading(button, false);
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
                  .text(bprm_csv_admin.strings.processing);
        } else {
            button.removeClass('loading')
                  .prop('disabled', false)
                  .text(button.data('original-text') || button.text().replace(bprm_csv_admin.strings.processing, '').trim());
        }
    }

    /**
     * Show admin message
     */
    function showAdminMessage(message, type = 'info') {
        const messageClass = `notice notice-${type} csv-admin-notice is-dismissible`;
        const messageHtml = `
            <div class="${messageClass}">
                <p>${message}</p>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text">Dismiss this notice.</span>
                </button>
            </div>
        `;
        
        // Remove existing messages
        $('.csv-admin-notice').remove();
        
        // Add new message
        $('.wrap h1').after(messageHtml);
        
        // Auto-hide success messages
        if (type === 'success') {
            setTimeout(function() {
                $('.csv-admin-notice').fadeOut();
            }, 5000);
        }
        
        // Handle dismiss button
        $('.notice-dismiss').on('click', function() {
            $(this).parent().fadeOut();
        });
    }

    /**
     * Format timestamp for display
     */
    function formatTimestamp(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);

        if (diffMins < 1) {
            return 'Just now';
        } else if (diffMins < 60) {
            return `${diffMins} minute${diffMins !== 1 ? 's' : ''} ago`;
        } else if (diffHours < 24) {
            return `${diffHours} hour${diffHours !== 1 ? 's' : ''} ago`;
        } else if (diffDays < 7) {
            return `${diffDays} day${diffDays !== 1 ? 's' : ''} ago`;
        } else {
            return date.toLocaleDateString();
        }
    }

    /**
     * Initialize tooltips if available
     */
    function initializeTooltips() {
        if (typeof $.fn.tooltip === 'function') {
            $('.bp-resume-csv-admin-wrapper [title]').tooltip({
                position: {
                    my: "center bottom-20",
                    at: "center top",
                    using: function(position, feedback) {
                        $(this).css(position);
                        $("<div>")
                            .addClass("arrow")
                            .addClass(feedback.vertical)
                            .addClass(feedback.horizontal)
                            .appendTo(this);
                    }
                }
            });
        }
    }

    /**
     * Initialize progress tracking for long operations
     */
    function initializeProgressTracking() {
        // Track any long-running AJAX operations
        $(document).ajaxStart(function() {
            // Could add a global loading indicator here
        }).ajaxStop(function() {
            // Remove global loading indicator
        });
    }

    /**
     * Handle settings form changes
     */
    function initializeSettingsForm() {
        // Auto-save settings on change (with debouncing)
        let saveTimeout;
        $('.form-table input, .form-table select').on('change', function() {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(function() {
                // Could implement auto-save here
                console.log('Settings changed - consider implementing auto-save');
            }, 2000);
        });

        // Validate numeric inputs
        $('input[type="number"]').on('input', function() {
            const value = parseInt($(this).val());
            const min = parseInt($(this).attr('min')) || 0;
            const max = parseInt($(this).attr('max')) || 999;
            
            if (value < min) {
                $(this).val(min);
            } else if (value > max) {
                $(this).val(max);
            }
        });
    }

    /**
     * Initialize keyboard shortcuts
     */
    function initializeKeyboardShortcuts() {
        $(document).on('keydown', function(e) {
            // Ctrl/Cmd + S to save settings
            if ((e.ctrlKey || e.metaKey) && e.keyCode === 83) {
                e.preventDefault();
                if ($('.form-table').length) {
                    $('#submit').click();
                }
            }
            
            // Esc to dismiss notices
            if (e.keyCode === 27) {
                $('.notice-dismiss').click();
            }
        });
    }

    /**
     * Initialize all functionality
     */
    function initializeAll() {
        initializeTooltips();
        initializeProgressTracking();
        initializeSettingsForm();
        initializeKeyboardShortcuts();
    }

    // Call initialization
    initializeAll();

    /**
     * Export functions for external use
     */
    window.BPResumeCSVAdmin = {
        loadStats: loadUsageStatistics,
        testFunctionality: testPluginFunctionality,
        resetSettings: resetPluginSettings,
        showMessage: showAdminMessage
    };

})(jQuery);