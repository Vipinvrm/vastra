// Costume Enquiry System Frontend JavaScript

jQuery(document).ready(function($) {
    var cesData = {
        selectedTemplate: null,
        selectedComponents: {},
        currentStep: 1
    };
    
    // Initialize the system
    initializeCES();
    
    function initializeCES() {
        // Step navigation
        setupStepNavigation();
        
        // Costume selection
        setupCostumeSelection();
        
        // Component toggles and options
        setupComponentHandlers();
        
        // Form submission
        setupFormSubmission();
        
        // Color picker initialization
        setupColorPickers();
    }
    
    function setupStepNavigation() {
        // Back to step 1
        $('#ces-back-to-step1').on('click', function() {
            showStep(1);
        });
        
        // Back to step 2
        $('#ces-back-to-step2').on('click', function() {
            showStep(2);
        });
        
        // Proceed to step 3
        $('#ces-proceed-to-step3').on('click', function() {
            if (validateStep2()) {
                updateCartSummary();
                showStep(3);
            }
        });
    }
    
    function setupCostumeSelection() {
        $('.ces-select-costume').on('click', function() {
            var templateId = $(this).data('template-id');
            cesData.selectedTemplate = templateId;
            
            // Update UI
            $('.ces-costume-card').removeClass('selected');
            $(this).closest('.ces-costume-card').addClass('selected');
            
            // Load components for this template
            loadComponents(templateId);
            
            // Move to step 2
            setTimeout(function() {
                showStep(2);
            }, 500);
        });
    }
    
    function setupComponentHandlers() {
        // Component toggle handlers (delegated)
        $(document).on('change', '.ces-component-toggle', function() {
            var componentId = $(this).data('component-id');
            var isChecked = $(this).is(':checked');
            var optionsContainer = $(this).closest('.ces-component-section').find('.ces-component-options');
            
            if (isChecked) {
                optionsContainer.slideDown();
                if (!cesData.selectedComponents[componentId]) {
                    cesData.selectedComponents[componentId] = {};
                }
            } else {
                optionsContainer.slideUp();
                delete cesData.selectedComponents[componentId];
            }
        });
        
        // Option change handlers (delegated)
        $(document).on('change', '.ces-color-picker, .ces-fabric-select, .ces-size-select, .ces-custom-select', function() {
            var componentId = $(this).data('component-id');
            var option = $(this).data('option');
            var value = $(this).val();
            
            if (!cesData.selectedComponents[componentId]) {
                cesData.selectedComponents[componentId] = {};
            }
            
            cesData.selectedComponents[componentId][option] = value;
            
            // Update color value display
            if ($(this).hasClass('ces-color-picker')) {
                $(this).siblings('.ces-color-value').text(value);
            }
        });
        
        // Custom input handlers (delegated)
        $(document).on('input', '.ces-custom-input', function() {
            var componentId = $(this).data('component-id');
            var option = $(this).data('option');
            var value = $(this).val();
            
            if (!cesData.selectedComponents[componentId]) {
                cesData.selectedComponents[componentId] = {};
            }
            
            if (!cesData.selectedComponents[componentId].custom_attributes) {
                cesData.selectedComponents[componentId].custom_attributes = {};
            }
            
            cesData.selectedComponents[componentId].custom_attributes[option] = value;
        });
    }
    
    function setupColorPickers() {
        // Initialize WordPress color picker when components are loaded
        $(document).on('focus', '.ces-color-picker', function() {
            if (!$(this).hasClass('wp-color-picker-initialized')) {
                $(this).addClass('wp-color-picker-initialized');
                $(this).wpColorPicker({
                    change: function(event, ui) {
                        var componentId = $(this).data('component-id');
                        var color = ui.color.toString();
                        
                        if (!cesData.selectedComponents[componentId]) {
                            cesData.selectedComponents[componentId] = {};
                        }
                        
                        cesData.selectedComponents[componentId].color = color;
                        $(this).siblings('.ces-color-value').text(color);
                    }
                });
            }
        });
    }
    
    function setupFormSubmission() {
        $('#ces-enquiry-form').on('submit', function(e) {
            e.preventDefault();
            
            if (!validateStep3()) {
                return;
            }
            
            showLoading(true);
            
            var formData = new FormData(this);
            formData.append('action', 'ces_submit_enquiry');
            formData.append('nonce', ces_ajax.nonce);
            formData.append('selected_items', JSON.stringify(getSelectedItemsArray()));
            
            $.ajax({
                url: ces_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    showLoading(false);
                    
                    if (response.success) {
                        showMessage('success', response.data.message);
                        resetForm();
                    } else {
                        showMessage('error', response.data || 'An error occurred. Please try again.');
                    }
                },
                error: function() {
                    showLoading(false);
                    showMessage('error', 'Network error. Please check your connection and try again.');
                }
            });
        });
    }
    
    function loadComponents(templateId) {
        showLoading(true);
        
        $.ajax({
            url: ces_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ces_load_components',
                template_id: templateId,
                nonce: ces_ajax.nonce
            },
            success: function(response) {
                showLoading(false);
                
                if (response.success) {
                    $('#ces-components-container').html(response.data);
                    $('#ces-selected-template').val(templateId);
                    
                    // Initialize required components
                    $('.ces-component-toggle:checked').each(function() {
                        var componentId = $(this).data('component-id');
                        cesData.selectedComponents[componentId] = {};
                    });
                } else {
                    showMessage('error', 'Failed to load components. Please try again.');
                }
            },
            error: function() {
                showLoading(false);
                showMessage('error', 'Failed to load components. Please check your connection.');
            }
        });
    }
    
    function showStep(stepNumber) {
        cesData.currentStep = stepNumber;
        
        // Update progress bar
        $('.ces-step').removeClass('active completed');
        $('.ces-step[data-step="' + stepNumber + '"]').addClass('active');
        $('.ces-step[data-step]').each(function() {
            var step = parseInt($(this).data('step'));
            if (step < stepNumber) {
                $(this).addClass('completed');
            }
        });
        
        // Show/hide step content
        $('.ces-step-content').removeClass('active');
        $('#ces-step-' + stepNumber).addClass('active');
        
        // Scroll to top
        $('html, body').animate({
            scrollTop: $('#costume-enquiry-system').offset().top - 50
        }, 300);
    }
    
    function validateStep2() {
        var hasSelection = Object.keys(cesData.selectedComponents).length > 0;
        
        if (!hasSelection) {
            showMessage('error', 'Please select at least one component to customize.');
            return false;
        }
        
        // Check if required components are selected
        var missingRequired = [];
        $('.ces-component-toggle[disabled]:not(:checked)').each(function() {
            var componentName = $(this).closest('.ces-component-section').find('h4').text();
            missingRequired.push(componentName);
        });
        
        if (missingRequired.length > 0) {
            showMessage('error', 'Please complete the required components: ' + missingRequired.join(', '));
            return false;
        }
        
        return true;
    }
    
    function validateStep3() {
        var isValid = true;
        var errorMessage = '';
        
        // Check required fields
        $('#ces-enquiry-form [required]').each(function() {
            if (!$(this).val().trim()) {
                isValid = false;
                $(this).addClass('error');
            } else {
                $(this).removeClass('error');
            }
        });
        
        // Validate email
        var email = $('#customer_email').val();
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (email && !emailRegex.test(email)) {
            isValid = false;
            $('#customer_email').addClass('error');
            errorMessage = 'Please enter a valid email address.';
        }
        
        if (!isValid && !errorMessage) {
            errorMessage = 'Please fill in all required fields.';
        }
        
        if (!isValid) {
            showMessage('error', errorMessage);
        }
        
        return isValid;
    }
    
    function updateCartSummary() {
        var summary = $('#ces-cart-summary');
        summary.empty();
        
        if (Object.keys(cesData.selectedComponents).length === 0) {
            summary.html('<p>No items selected.</p>');
            return;
        }
        
        Object.keys(cesData.selectedComponents).forEach(function(componentId) {
            var component = cesData.selectedComponents[componentId];
            var componentName = $('.ces-component-section[data-component-id="' + componentId + '"] h4').text();
            
            var itemHtml = '<div class="ces-cart-item">';
            itemHtml += '<strong>' + componentName + '</strong><br>';
            
            if (component.color) {
                itemHtml += 'Color: <span style="background: ' + component.color + '; padding: 2px 8px; color: white; border-radius: 3px;">' + component.color + '</span><br>';
            }
            
            if (component.fabric) {
                itemHtml += 'Fabric: ' + component.fabric + '<br>';
            }
            
            if (component.size) {
                itemHtml += 'Size: ' + component.size + '<br>';
            }
            
            if (component.custom_attributes) {
                Object.keys(component.custom_attributes).forEach(function(attr) {
                    itemHtml += attr + ': ' + component.custom_attributes[attr] + '<br>';
                });
            }
            
            itemHtml += '</div>';
            summary.append(itemHtml);
        });
    }
    
    function getSelectedItemsArray() {
        var items = [];
        
        Object.keys(cesData.selectedComponents).forEach(function(componentId) {
            var component = cesData.selectedComponents[componentId];
            items.push({
                component_id: componentId,
                color: component.color || '',
                fabric: component.fabric || '',
                size: component.size || '',
                custom_attributes: component.custom_attributes || {}
            });
        });
        
        return items;
    }
    
    function showLoading(show) {
        if (show) {
            $('#ces-loading').show();
        } else {
            $('#ces-loading').hide();
        }
    }
    
    function showMessage(type, message) {
        var messageHtml = '<div class="ces-message ' + type + '">' + message + '</div>';
        $('.ces-container').prepend(messageHtml);
        
        // Auto-remove after 5 seconds
        setTimeout(function() {
            $('.ces-message').fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
        
        // Scroll to message
        $('html, body').animate({
            scrollTop: $('.ces-message').offset().top - 50
        }, 300);
    }
    
    function resetForm() {
        cesData.selectedTemplate = null;
        cesData.selectedComponents = {};
        $('#ces-enquiry-form')[0].reset();
        showStep(1);
    }
});