jQuery(document).ready(function($) {
    // Enhanced form change tracking
    var formChanged = false;
    var originalFormData = {};
    var currentSubtab = '';
    
    // Function to serialize form data for comparison
    function serializeFormData($form) {
        var data = {};
        $form.find(':input').each(function() {
            var $input = $(this);
            var name = $input.attr('name');
            var type = $input.attr('type');
            
            if (name && name !== '_wpnonce' && name !== '_wp_http_referer') {
                if (type === 'checkbox' || type === 'radio') {
                    data[name] = $input.is(':checked');
                } else {
                    data[name] = $input.val();
                }
            }
        });
        return JSON.stringify(data);
    }
    
    // Function to detect form changes with enhanced field styling
    function setupFormChangeDetection() {
        // Store initial form data for each settings form
        $('.settings-content form').each(function() {
            var $form = $(this);
            var formId = $form.closest('.settings-content').find('form').index($form);
            originalFormData[formId] = serializeFormData($form);
        });
        
        // Monitor form changes
        $('.settings-content form :input').on('change input', function() {
            var $field = $(this);
            var $form = $field.closest('form');
            var formId = $form.closest('.settings-content').find('form').index($form);
            var currentData = serializeFormData($form);
            
            // Check if this specific field has changed
            var fieldName = $field.attr('name');
            var fieldType = $field.attr('type');
            var originalValue = getOriginalFieldValue($form, fieldName, fieldType);
            var currentValue = getFieldValue($field);
            
            // Apply field-specific styling
            if (originalValue !== currentValue) {
                $field.addClass('swp-field-modified').removeClass('swp-field-saved');
                // Add fallback class to table row for browsers without :has() support
                $field.closest('tr').addClass('swp-row-modified').removeClass('swp-row-saved');
            } else {
                $field.removeClass('swp-field-modified swp-field-saved');
                // Remove fallback class from table row
                $field.closest('tr').removeClass('swp-row-modified swp-row-saved');
            }
            
            // Check overall form changes
            if (originalFormData[formId] !== currentData) {
                formChanged = true;
                updateUnsavedIndicator(true);
            } else {
                formChanged = false;
                updateUnsavedIndicator(false);
                // Remove all field modifications if form is back to original state
                $form.find(':input').removeClass('swp-field-modified swp-field-saved');
                $form.find('tr').removeClass('swp-row-modified swp-row-saved');
            }
        });
    }
    
    // Helper function to get field value
    function getFieldValue($field) {
        var type = $field.attr('type');
        if (type === 'checkbox' || type === 'radio') {
            return $field.is(':checked');
        } else {
            return $field.val();
        }
    }
    
    // Helper function to get original field value from stored form data
    function getOriginalFieldValue($form, fieldName, fieldType) {
        var formId = $form.closest('.settings-content').find('form').index($form);
        var originalData = JSON.parse(originalFormData[formId] || '{}');
        return originalData[fieldName];
    }
    
    // Function to update unsaved changes indicator
    function updateUnsavedIndicator(hasChanges) {
        var $activeTab = $('.settings-nav-tab.active');
        var $notice = $('#securewp-unsaved-notice');
        var $cancelButtons = $('.securewp-cancel-button');
        
        if (hasChanges) {
            // Add indicator to tab
            if (!$activeTab.find('.unsaved-indicator').length) {
                $activeTab.append('<span class="unsaved-indicator" title="Unsaved changes">●</span>');
            }
            // Show notice
            $notice.slideDown();
            // Show cancel buttons
            $cancelButtons.show();
            // Add body class for disabled navigation styling
            $('body').addClass('swp-has-unsaved-changes');
        } else {
            // Remove indicator from all tabs
            $('.settings-nav-tab .unsaved-indicator').remove();
            // Hide notice
            $notice.slideUp();
            // Hide cancel buttons
            $cancelButtons.hide();
            // Remove body class
            $('body').removeClass('swp-has-unsaved-changes');
        }
    }
    
    // Reset form changed flag with enhanced field styling
    function resetFormChangedFlag() {
        formChanged = false;
        $('.settings-content form').each(function() {
            var $form = $(this);
            var formId = $form.closest('.settings-content').find('form').index($form);
            originalFormData[formId] = serializeFormData($form);
            
            // Apply saved styling to all fields briefly
            $form.find(':input').removeClass('swp-field-modified').addClass('swp-field-saved');
            $form.find('tr').removeClass('swp-row-modified').addClass('swp-row-saved');
            
            // Remove saved styling after animation
            setTimeout(function() {
                $form.find(':input').removeClass('swp-field-saved');
                $form.find('tr').removeClass('swp-row-saved');
            }, 2000);
        });
        updateUnsavedIndicator(false);
    }
    
    // Tab navigation with unsaved changes prevention
    function setupTabNavigationPrevention() {
        // Handle main tab navigation (Overview, Settings, Lockouts, etc.)
        $('.nav-tab').on('click', function(e) {
            if (formChanged) {
                e.preventDefault();
                showUnsavedChangesDialog($(this).attr('href'), 'main-tab');
                return false;
            }
        });
        
        // Handle settings sub-tab navigation (Security Features, Lockout, etc.)
        $('.settings-nav-tab').on('click', function(e) {
            // Don't prevent if clicking on the already active tab
            if ($(this).hasClass('active')) {
                return;
            }
            
            if (formChanged) {
                e.preventDefault();
                showUnsavedChangesDialog($(this).attr('href'), 'sub-tab');
                return false;
            }
        });
    }
    
    // Show unsaved changes dialog
    function showUnsavedChangesDialog(targetUrl, tabType) {
        var dialogTitle = tabType === 'main-tab' ? 'Leave Settings Page?' : 'Switch Settings Tab?';
        var dialogMessage = 'You have unsaved changes that will be lost if you continue.';
        
        // Create modal dialog
        var $dialog = $('<div class="swp-unsaved-dialog">').html(`
            <div class="swp-dialog-overlay">
                <div class="swp-dialog-content">
                    <div class="swp-dialog-header">
                        <h3><span class="dashicons dashicons-warning"></span> ${dialogTitle}</h3>
                    </div>
                    <div class="swp-dialog-body">
                        <p><strong>${dialogMessage}</strong></p>
                        <p>What would you like to do?</p>
                        <div class="swp-modified-fields-preview">
                            <h4>Modified Fields:</h4>
                            <ul class="swp-modified-list"></ul>
                        </div>
                    </div>
                    <div class="swp-dialog-actions">
                        <button type="button" class="button button-primary swp-save-and-continue">Save & Continue</button>
                        <button type="button" class="button swp-discard-and-continue">Discard Changes</button>
                        <button type="button" class="button swp-stay-here">Stay Here</button>
                    </div>
                </div>
            </div>
        `);
        
        // Populate modified fields list
        var $modifiedList = $dialog.find('.swp-modified-list');
        $('.swp-field-modified').each(function() {
            var $field = $(this);
            var label = $field.closest('tr').find('th label').text().replace(/[●✓]/g, '').trim();
            var fieldType = $field.attr('type') || $field.prop('tagName').toLowerCase();
            var value = getFieldDisplayValue($field);
            
            $modifiedList.append(`<li><strong>${label}</strong> (${fieldType}): ${value}</li>`);
        });
        
        // Append to body
        $('body').append($dialog);
        
        // Handle dialog actions
        $dialog.find('.swp-save-and-continue').on('click', function() {
            // Find and submit the current form
            var $currentForm = $('.settings-content form:visible');
            if ($currentForm.length) {
                // Add a hidden field to indicate this is an auto-save
                $currentForm.append('<input type="hidden" name="swp_auto_save_redirect" value="' + encodeURIComponent(targetUrl) + '">');
                $currentForm.submit();
            }
            $dialog.remove();
        });
        
        $dialog.find('.swp-discard-and-continue').on('click', function() {
            // Reset form state and navigate
            resetFormChangedFlag();
            window.location.href = targetUrl;
            $dialog.remove();
        });
        
        $dialog.find('.swp-stay-here').on('click', function() {
            $dialog.remove();
        });
        
        // Close on overlay click
        $dialog.find('.swp-dialog-overlay').on('click', function(e) {
            if (e.target === this) {
                $dialog.remove();
            }
        });
        
        // Close on Escape key
        $(document).on('keydown.swp-dialog', function(e) {
            if (e.keyCode === 27) { // Escape key
                $dialog.remove();
                $(document).off('keydown.swp-dialog');
            }
        });
    }
    
    // Helper function to get display value for different field types
    function getFieldDisplayValue($field) {
        var type = $field.attr('type');
        var tagName = $field.prop('tagName').toLowerCase();
        
        if (type === 'checkbox' || type === 'radio') {
            return $field.is(':checked') ? 'Enabled' : 'Disabled';
        } else if (tagName === 'select') {
            var selectedText = $field.find('option:selected').text();
            return selectedText || $field.val();
        } else {
            var value = $field.val();
            if (value && value.length > 50) {
                return value.substring(0, 47) + '...';
            }
            return value || '(empty)';
        }
    }
    
    // Browser navigation warning
    function setupBrowserNavigationWarning() {
        $(window).on('beforeunload', function(e) {
            if (formChanged) {
                var message = 'You have unsaved changes that will be lost if you leave this page.';
                e.returnValue = message; // For older browsers
                return message; // For modern browsers
            }
        });
    }
    
    // Handle form submission with enhanced styling and auto-redirect
    $('.settings-content form').on('submit', function(e) {
        var $form = $(this);
        
        // Check if this is an auto-save with redirect
        var redirectUrl = $form.find('input[name="swp_auto_save_redirect"]').val();
        if (redirectUrl) {
            // Store redirect URL in sessionStorage for after page reload
            sessionStorage.setItem('swp_redirect_after_save', decodeURIComponent(redirectUrl));
        }
        
        // Reset form changed flag on save
        resetFormChangedFlag();
    });
    
    // Check for auto-redirect after page load (after form submission)
    function checkAutoRedirect() {
        var redirectUrl = sessionStorage.getItem('swp_redirect_after_save');
        if (redirectUrl && window.location.href.indexOf('settings-updated=true') > -1) {
            sessionStorage.removeItem('swp_redirect_after_save');
            setTimeout(function() {
                window.location.href = redirectUrl;
            }, 1000); // Give user time to see the success message
        }
    }

    // AJAX: Generate new Cron Secret Key
    $(document).on('click', '.securewp-generate-cron-key', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var $status = $('#securewp-cron-key-status');
        var $input = $('input[name="securewp_pro_cron_secret_key"]');
        var nonce = $btn.data('nonce') || (typeof SecureWPPro !== 'undefined' ? SecureWPPro.nonce : '');
        var ajaxUrl = (typeof SecureWPPro !== 'undefined' ? SecureWPPro.ajax_url : ajaxurl);

        if (!$input.length) { return; }

        // UI feedback
        var originalText = $btn.text();
        $btn.prop('disabled', true).text((SecureWPPro && SecureWPPro.i18n && SecureWPPro.i18n.generating) || 'Generating…');
        $status.text('');

        $.post(ajaxUrl, {
            action: 'securewp_pro_generate_cron_key',
            _ajax_nonce: nonce
        }).done(function(resp) {
            if (resp && resp.success && resp.data && resp.data.key) {
                $input.val(resp.data.key);
                $status.text((SecureWPPro && SecureWPPro.i18n && SecureWPPro.i18n.generated) || 'New key generated');
                // Refresh cron evidence block if present so href contains the new key
                var $cronEvidence = $('.swp-evidence[data-feature="securewp_pro_cron_security"]');
                if ($cronEvidence.length) {
                    if (typeof loadEvidence === 'function') {
                        loadEvidence($cronEvidence);
                    }
                }
            } else {
                $status.text((SecureWPPro && SecureWPPro.i18n && SecureWPPro.i18n.error) || 'Error generating key');
            }
        }).fail(function() {
            $status.text((SecureWPPro && SecureWPPro.i18n && SecureWPPro.i18n.error) || 'Error generating key');
        }).always(function() {
            $btn.prop('disabled', false).text(originalText || (SecureWPPro && SecureWPPro.i18n && SecureWPPro.i18n.generate) || 'Generate New Key');
        });
    });

    // Toggle show/hide Cron Secret Key
    $(document).on('click', '.securewp-toggle-cron-key', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var $input = $('input[name="securewp_pro_cron_secret_key"]');
        if (!$input.length) { return; }
        var currentType = $input.attr('type');
        if (currentType === 'password') {
            $input.attr('type', 'text');
            $btn.text('Hide');
        } else {
            $input.attr('type', 'password');
            $btn.text('Show');
        }
    });

    // Copy Cron Secret Key to clipboard
    $(document).on('click', '.securewp-copy-cron-key', function(e) {
        e.preventDefault();
        var $status = $('#securewp-cron-key-status');
        var $input = $('input[name="securewp_pro_cron_secret_key"]');
        if (!$input.length) { return; }
        var value = $input.val();
        var onCopied = function(success) {
            if (success) {
                $status.text('Copied');
            } else {
                $status.text('Copy failed');
            }
        };
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(value).then(function(){ onCopied(true); }).catch(function(){ onCopied(false); });
        } else {
            // Fallback method
            var temp = $('<input type="text" style="position:absolute;left:-9999px;">').val(value).appendTo('body');
            temp[0].select();
            try {
                var ok = document.execCommand('copy');
                onCopied(!!ok);
            } catch (err) {
                onCopied(false);
            }
            temp.remove();
        }
    });

    // Evidence loader for feature toggles
    function loadEvidence($container) {
        var feature = $container.data('feature');
        if (!feature) return;
        var ajaxUrl = (typeof SecureWPPro !== 'undefined' ? SecureWPPro.ajax_url : ajaxurl);
        var nonce = (typeof SecureWPPro !== 'undefined' ? SecureWPPro.nonce : '');
        $container.addClass('swp-loading').html('<em>Loading evidence…</em>');
        $.post(ajaxUrl, {
            action: 'securewp_pro_get_evidence',
            feature: feature,
            _ajax_nonce: nonce
        }).done(function(resp) {
            if (resp && resp.success && resp.data) {
                $container.html(resp.data.html || '');
            } else {
                $container.html('');
            }
        }).fail(function(){
            $container.html('');
        }).always(function(){
            $container.removeClass('swp-loading');
        });
    }

    function bindEvidenceToggles() {
        // Map evidence container to checkbox by data-feature attribute (same as option name)
        $('.swp-evidence').each(function(){
            var $container = $(this);
            var feature = $container.data('feature');
            if (!feature) return;
            var $checkbox = $('input[name="' + feature + '"]');
            if (!$checkbox.length) return;

            // Initial load if enabled
            if ($checkbox.is(':checked')) {
                $container.show();
                loadEvidence($container);
            } else {
                $container.hide().empty();
            }

            // On change
            $checkbox.on('change', function(){
                if ($(this).is(':checked')) {
                    $container.show();
                    loadEvidence($container);
                } else {
                    $container.hide().empty();
                }
            });
        });
    }

    bindEvidenceToggles();
    
    // Cancel button functionality
    function setupCancelButtons() {
        $(document).on('click', '.securewp-cancel-button', function(e) {
            e.preventDefault();
            
            // Show confirmation dialog
            var confirmed = confirm('Are you sure you want to discard all changes? This will restore all fields to their original values.');
            if (!confirmed) {
                return;
            }
            
            // Reset all forms to original state
            $('.settings-content form').each(function() {
                var $form = $(this);
                var formId = $form.closest('.settings-content').find('form').index($form);
                var originalData = JSON.parse(originalFormData[formId] || '{}');
                
                // Restore each field to its original value
                $form.find(':input').each(function() {
                    var $field = $(this);
                    var name = $field.attr('name');
                    var type = $field.attr('type');
                    
                    if (name && name !== '_wpnonce' && name !== '_wp_http_referer' && name !== 'submit') {
                        if (originalData.hasOwnProperty(name)) {
                            if (type === 'checkbox' || type === 'radio') {
                                $field.prop('checked', originalData[name]);
                            } else {
                                $field.val(originalData[name]);
                            }
                        }
                    }
                });
                
                // Remove all visual indicators
                $form.find(':input').removeClass('swp-field-modified swp-field-saved');
                $form.find('tr').removeClass('swp-row-modified swp-row-saved');
            });
            
            // Reset change tracking
            formChanged = false;
            updateUnsavedIndicator(false);
            
            // Show brief success feedback
            var $button = $(this);
            var originalText = $button.text();
            $button.text('Changes Discarded').addClass('button-disabled');
            setTimeout(function() {
                $button.text(originalText).removeClass('button-disabled');
            }, 2000);
        });
    }
    
    // Horizontal tabs functionality for Security Features
    function setupHorizontalTabs() {
        $('.securewp-tab-button').on('click', function() {
            var tabId = $(this).data('tab');
            
            // Update active tab button
            $('.securewp-tab-button').removeClass('active');
            $(this).addClass('active');
            
            // Show the corresponding tab pane
            $('.securewp-tab-pane').removeClass('active');
            $('#' + tabId).addClass('active');
        });
    }
    
    // Initialize all functionality
    setupCancelButtons();
    setupHorizontalTabs();
    
    // Initialize form change detection and navigation prevention
    setupFormChangeDetection();
    setupTabNavigationPrevention();
    setupBrowserNavigationWarning();
    
    // Check for auto-redirect after form submission
    checkAutoRedirect();
});