// Costume Enquiry System Admin JavaScript

jQuery(document).ready(function($) {
    
    // Initialize admin functionality
    initializeAdmin();
    
    function initializeAdmin() {
        // Media uploader
        setupMediaUploader();
        
        // Color picker
        setupColorPicker();
        
        // Bulk actions
        setupBulkActions();
        
        // Template selector
        setupTemplateSelector();
        
        // Form validation
        setupFormValidation();
        
        // Delete confirmations
        setupDeleteConfirmations();
    }
    
    function setupMediaUploader() {
        $('#upload_image_button').on('click', function(e) {
            e.preventDefault();
            
            var mediaUploader = wp.media({
                title: 'Select Image',
                button: {
                    text: 'Use This Image'
                },
                multiple: false
            });
            
            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#template_image').val(attachment.url);
            });
            
            mediaUploader.open();
        });
    }
    
    function setupColorPicker() {
        if ($.fn.wpColorPicker) {
            $('.color-picker').wpColorPicker();
        }
    }
    
    function setupBulkActions() {
        $('#cb-select-all').on('change', function() {
            var isChecked = $(this).is(':checked');
            $('input[name="selected_enquiries[]"]').prop('checked', isChecked);
        });
        
        // Update select all checkbox when individual checkboxes change
        $(document).on('change', 'input[name="selected_enquiries[]"]', function() {
            var totalCheckboxes = $('input[name="selected_enquiries[]"]').length;
            var checkedCheckboxes = $('input[name="selected_enquiries[]"]:checked').length;
            
            $('#cb-select-all').prop('checked', totalCheckboxes === checkedCheckboxes);
        });
        
        // Bulk action form submission
        $('.tablenav .action').on('click', function(e) {
            var selectedAction = $('select[name="bulk_action"]').val();
            var selectedItems = $('input[name="selected_enquiries[]"]:checked').length;
            
            if (!selectedAction) {
                e.preventDefault();
                alert('Please select an action.');
                return;
            }
            
            if (selectedItems === 0) {
                e.preventDefault();
                alert('Please select at least one enquiry.');
                return;
            }
            
            if (selectedAction === 'delete') {
                if (!confirm('Are you sure you want to delete the selected enquiries? This action cannot be undone.')) {
                    e.preventDefault();
                    return;
                }
            }
        });
    }
    
    function setupTemplateSelector() {
        // Auto-submit template selector changes
        $('#template_selector').on('change', function() {
            var url = new URL(window.location);
            url.searchParams.set('template_id', $(this).val());
            window.location.href = url.toString();
        });
    }
    
    function setupFormValidation() {
        // Component form validation
        $('form').on('submit', function(e) {
            var form = $(this);
            var isValid = true;
            var errorMessage = '';
            
            // Check required fields
            form.find('[required]').each(function() {
                if (!$(this).val().trim()) {
                    isValid = false;
                    $(this).addClass('error');
                } else {
                    $(this).removeClass('error');
                }
            });
            
            // Validate email fields
            form.find('input[type="email"]').each(function() {
                var email = $(this).val();
                if (email && !isValidEmail(email)) {
                    isValid = false;
                    $(this).addClass('error');
                    errorMessage = 'Please enter a valid email address.';
                }
            });
            
            // Validate URL fields
            form.find('input[type="url"]').each(function() {
                var url = $(this).val();
                if (url && !isValidUrl(url)) {
                    isValid = false;
                    $(this).addClass('error');
                    errorMessage = 'Please enter a valid URL.';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                if (errorMessage) {
                    alert(errorMessage);
                } else {
                    alert('Please fill in all required fields.');
                }
            }
        });
        
        // Remove error class on input
        $('input, textarea, select').on('input change', function() {
            $(this).removeClass('error');
        });
    }
    
    function setupDeleteConfirmations() {
        $('.button-link-delete').on('click', function(e) {
            if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    }
    
    // Utility functions
    function isValidEmail(email) {
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    function isValidUrl(url) {
        try {
            new URL(url);
            return true;
        } catch (e) {
            return false;
        }
    }
    
    // Add loading states
    function showLoading(show) {
        if (show) {
            $('.ces-loading').addClass('active');
        } else {
            $('.ces-loading').removeClass('active');
        }
    }
    
    // Auto-save functionality for long forms
    var autoSaveTimer;
    function setupAutoSave() {
        $('form textarea, form input[type="text"]').on('input', function() {
            clearTimeout(autoSaveTimer);
            autoSaveTimer = setTimeout(function() {
                // Auto-save logic here if needed
                console.log('Auto-saving...');
            }, 2000);
        });
    }
    
    // Setup notifications
    function showNotification(message, type) {
        var notificationClass = type === 'error' ? 'notice-error' : 'notice-success';
        var notification = $('<div class="notice ' + notificationClass + ' is-dismissible"><p>' + message + '</p></div>');
        
        $('.wrap h1').after(notification);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            notification.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    // Enhanced table sorting
    $('.wp-list-table th.sortable').on('click', function() {
        var column = $(this).data('column');
        var currentOrder = $(this).data('order') || 'asc';
        var newOrder = currentOrder === 'asc' ? 'desc' : 'asc';
        
        var url = new URL(window.location);
        url.searchParams.set('orderby', column);
        url.searchParams.set('order', newOrder);
        
        window.location.href = url.toString();
    });
    
    // Quick edit functionality
    $('.quick-edit').on('click', function(e) {
        e.preventDefault();
        var row = $(this).closest('tr');
        var editRow = row.next('.quick-edit-row');
        
        if (editRow.length) {
            editRow.toggle();
        } else {
            // Create quick edit row
            createQuickEditRow(row);
        }
    });
    
    function createQuickEditRow(row) {
        // Implementation for quick edit functionality
        // This would create an inline edit form
    }
    
    // Export functionality
    $('.export-btn').on('click', function(e) {
        e.preventDefault();
        showLoading(true);
        
        // Get current filters
        var params = new URLSearchParams(window.location.search);
        params.set('action', 'export');
        
        // Create temporary form for download
        var form = $('<form>', {
            method: 'POST',
            action: window.location.pathname + '?' + params.toString()
        });
        
        $('body').append(form);
        form.submit();
        form.remove();
        
        showLoading(false);
    });
});