jQuery(document).ready(function($) {
    'use strict';
    
    // Current diploma configuration
    let currentConfig = {
        diploma_style: 'classic',
        paper_color: 'white',
        emblem_type: 'generic',
        emblem_value: 'graduation_cap',
        school_name: '',
        student_name: '',
        graduation_date: '',
        city: '',
        state: ''
    };
    
    // Initialize the diploma builder
    function init() {
        bindEvents();
        initializeForm();
        updatePreview();
        // Hide loading overlay on initialization
        hideLoading();
    }
    
    // Initialize form to show first step only
    function initializeForm() {
        // Hide all form sections except the first one
        $('.form-section').hide();
        $('.form-section[data-step="1"]').show();
        
        // Set initial button states
        $('#prev-step').prop('disabled', true);
        $('#next-step').show();
        $('.form-actions').hide();
        
        // Initialize progress
        updateProgressBar();
    }
    
    // Bind all event handlers
    function bindEvents() {
        // Enhanced diploma style selection (radio buttons)
        $('input[name="diploma_style"]').on('change', function() {
            currentConfig.diploma_style = $(this).val();
            updatePreview();
            updateReviewSummary();
        });
        
        // Template filter buttons
        $('.filter-btn').on('click', function() {
            const filter = $(this).data('filter');
            $('.filter-btn').removeClass('active');
            $(this).addClass('active');
            filterTemplates(filter);
        });
        
        // Paper color selection
        $('input[name="paper_color"]').on('change', function() {
            currentConfig.paper_color = $(this).val();
            updatePreview();
            updateReviewSummary();
        });
        
        // Enhanced emblem selection
        $('.emblem-tab-btn').on('click', function() {
            const tab = $(this).data('tab');
            currentConfig.emblem_type = tab;
            toggleEmblemTabs(tab);
            updateEmblemValue();
            updatePreview();
        });
        
        // Generic emblem selection
        $('input[name="emblem_value"][data-type="generic"]').on('change', function() {
            currentConfig.emblem_value = $(this).val();
            updatePreview();
        });
        
        // State emblem selection
        $('#state-emblem-select').on('change', function() {
            const stateCode = $(this).val();
            currentConfig.emblem_value = stateCode;
            updateStateEmblemPreview(stateCode);
            updatePreview();
        });
        
        // Text field changes with real-time validation
        $('#student_name, #school_name, #city, #graduation_date').on('input', function() {
            const fieldName = $(this).attr('name');
            currentConfig[fieldName] = $(this).val();
            validateField($(this));
            updatePreview();
            updateReviewSummary();
        });
        
        // State dropdown
        $('#state').on('change', function() {
            currentConfig.state = $(this).val();
            validateField($(this));
            updatePreview();
            updateReviewSummary();
        });
        
        // Export buttons
        $('#export-pdf').on('click', function() {
            exportDiploma('pdf');
        });
        
        $('#export-png').on('click', function() {
            exportDiploma('png');
        });
        
        $('#export-jpg').on('click', function() {
            exportDiploma('jpg');
        });
        
        // Share buttons
        $('#share-facebook').on('click', function() {
            shareOnSocialMedia('facebook');
        });
        
        $('#share-twitter').on('click', function() {
            shareOnSocialMedia('twitter');
        });
        
        $('#share-linkedin').on('click', function() {
            shareOnSocialMedia('linkedin');
        });
        
        $('#copy-link').on('click', function() {
            copyDiplomaLink();
        });
        
        // Navigation buttons
        $('#prev-step').on('click', function() {
            navigateSteps('prev');
        });
        
        $('#next-step').on('click', function() {
            navigateSteps('next');
        });
        
        // Action buttons
        $('#save-diploma').on('click', saveDiploma);
        $('#download-diploma').on('click', downloadDiploma);
        
        // Form actions
        $('#create-another').on('click', function() {
            $('#success-modal').hide();
            resetForm();
        });
        
        $('#view-gallery').on('click', function() {
            window.location.href = '/diploma-gallery';
        });
        
        // Modal close
        $('.modal-close').on('click', function() {
            $('#success-modal').hide();
        });
        
        // Zoom controls
        $('#zoom-in').on('click', function() {
            zoomPreview(0.1);
        });
        
        $('#zoom-out').on('click', function() {
            zoomPreview(-0.1);
        });
        
        $('#toggle-fullscreen').on('click', function() {
            toggleFullscreen();
        });
    }
    
    // Enhanced template filtering
    function filterTemplates(filter) {
        $('.template-card').show();
        
        if (filter !== 'all') {
            $('.template-card').each(function() {
                const templateType = $(this).find('input').val();
                if (templateType !== filter && !templateType.includes(filter)) {
                    $(this).hide();
                }
            });
        }
    }
    
    // Toggle emblem tabs
    function toggleEmblemTabs(activeTab) {
        $('.emblem-tab-btn').removeClass('active');
        $('.emblem-tab-content').removeClass('active');
        
        $(`.emblem-tab-btn[data-tab="${activeTab}"]`).addClass('active');
        $(`#${activeTab}-emblems`).addClass('active');
    }
    
    // Update emblem value when type changes
    function updateEmblemValue() {
        if (currentConfig.emblem_type === 'generic') {
            currentConfig.emblem_value = $('input[name="emblem_value"][data-type="generic"]:checked').val() || 'graduation_cap';
        } else {
            currentConfig.emblem_value = $('#state-emblem-select').val() || '';
        }
    }
    
    // Update state emblem preview
    function updateStateEmblemPreview(stateCode) {
        if (stateCode) {
            const stateName = $(`#state-emblem-select option[value="${stateCode}"]`).text();
            const emblemUrl = `${diploma_ajax.plugin_url}assets/emblems/states/${stateCode}.svg`;
            
            $('#state-emblem-img').attr('src', emblemUrl).attr('alt', stateName);
            $('#state-emblem-name').text(stateName);
            $('#state-emblem-preview').show();
        } else {
            $('#state-emblem-preview').hide();
        }
    }
    
    // Real-time form validation
    function validateField($field) {
        const value = $field.val().trim();
        const isRequired = $field.prop('required');
        
        // Remove existing validation classes
        $field.removeClass('field-valid field-invalid');
        
        if (isRequired && !value) {
            $field.addClass('field-invalid');
            showFieldError($field, 'This field is required');
        } else if (value) {
            $field.addClass('field-valid');
            hideFieldError($field);
            
            // Specific validation rules
            const fieldName = $field.attr('name');
            if (fieldName === 'student_name' && value.length < 2) {
                $field.removeClass('field-valid').addClass('field-invalid');
                showFieldError($field, 'Name must be at least 2 characters');
            } else if (fieldName === 'school_name' && value.length < 3) {
                $field.removeClass('field-valid').addClass('field-invalid');
                showFieldError($field, 'School name must be at least 3 characters');
            }
        } else {
            hideFieldError($field);
        }
    }
    
    // Show field error
    function showFieldError($field, message) {
        const $fieldGroup = $field.closest('.field-group');
        let $errorMsg = $fieldGroup.find('.field-error');
        
        if ($errorMsg.length === 0) {
            $errorMsg = $('<div class="field-error"></div>');
            $fieldGroup.append($errorMsg);
        }
        
        $errorMsg.text(message).show();
    }
    
    // Hide field error
    function hideFieldError($field) {
        const $fieldGroup = $field.closest('.field-group');
        $fieldGroup.find('.field-error').hide();
    }
    
    // Enhanced review summary update
    function updateReviewSummary() {
        $('#review-student-name').text(currentConfig.student_name || '[Student Name]');
        $('#review-school-name').text(currentConfig.school_name || '[School Name]');
        $('#review-graduation-date').text(currentConfig.graduation_date || '[Graduation Date]');
        
        const city = currentConfig.city || '[City]';
        const state = currentConfig.state || '[State]';
        $('#review-location').text(`${city}, ${state}`);
        
        // Update style and paper info
        const styleName = $(`input[name="diploma_style"]:checked`).closest('.template-card').find('h5').text() || '[Style]';
        const paperName = $(`input[name="paper_color"]:checked`).closest('.color-option').find('.color-name').text() || '[Paper]';
        
        $('#review-diploma-style').text(styleName);
        $('#review-paper-color').text(paperName);
    }
    
    // Export diploma in different formats
    function exportDiploma(format) {
        if (!validateForm()) {
            return;
        }
        
        showLoading();
        
        // Use html2canvas to capture the diploma
        const options = {
            scale: format === 'pdf' ? 4 : 3,
            backgroundColor: null,
            width: 1275, // 8.5" * 150 DPI
            height: 1650, // 11" * 150 DPI
            useCORS: true,
            allowTaint: false
        };
        
        html2canvas(document.getElementById('diploma-canvas'), options).then(function(canvas) {
            const link = document.createElement('a');
            const timestamp = Date.now();
            const schoolName = currentConfig.school_name.replace(/[^a-z0-9]/gi, '_');
            
            if (format === 'pdf') {
                // For PDF, we'd need a PDF library like jsPDF
                const imgData = canvas.toDataURL('image/png', 1.0);
                link.download = `diploma_${schoolName}_${timestamp}.png`;
                link.href = imgData;
            } else if (format === 'png') {
                link.download = `diploma_${schoolName}_${timestamp}.png`;
                link.href = canvas.toDataURL('image/png', 1.0);
            } else if (format === 'jpg') {
                link.download = `diploma_${schoolName}_${timestamp}.jpg`;
                link.href = canvas.toDataURL('image/jpeg', 0.9);
            }
            
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            hideLoading();
            showMessage(`Diploma exported as ${format.toUpperCase()} successfully!`, 'success');
            
            // Track export
            trackEvent('diploma_export', { format: format });
            
        }).catch(function(error) {
            hideLoading();
            console.error('Error exporting diploma:', error);
            showMessage('Error exporting diploma. Please try again.', 'error');
        });
    }
    
    // Share on social media
    function shareOnSocialMedia(platform) {
        const diplomaUrl = window.location.href;
        const text = `Check out my custom diploma from ${currentConfig.school_name}!`;
        let shareUrl = '';
        
        switch (platform) {
            case 'facebook':
                shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(diplomaUrl)}`;
                break;
            case 'twitter':
                shareUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(diplomaUrl)}`;
                break;
            case 'linkedin':
                shareUrl = `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(diplomaUrl)}`;
                break;
        }
        
        if (shareUrl) {
            window.open(shareUrl, '_blank', 'width=600,height=400');
            trackEvent('diploma_share', { platform: platform });
        }
    }
    
    // Copy diploma link
    function copyDiplomaLink() {
        const diplomaUrl = window.location.href;
        
        if (navigator.clipboard) {
            navigator.clipboard.writeText(diplomaUrl).then(function() {
                showMessage('Diploma link copied to clipboard!', 'success');
                trackEvent('diploma_link_copy');
            }).catch(function() {
                fallbackCopyTextToClipboard(diplomaUrl);
            });
        } else {
            fallbackCopyTextToClipboard(diplomaUrl);
        }
    }
    
    // Fallback copy function
    function fallbackCopyTextToClipboard(text) {
        const textArea = document.createElement("textarea");
        textArea.value = text;
        textArea.style.top = "0";
        textArea.style.left = "0";
        textArea.style.position = "fixed";
        
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            document.execCommand('copy');
            showMessage('Diploma link copied to clipboard!', 'success');
            trackEvent('diploma_link_copy');
        } catch (err) {
            showMessage('Unable to copy link. Please copy manually.', 'error');
        }
        
        document.body.removeChild(textArea);
    }
    
    // Track events for analytics
    function trackEvent(eventName, properties = {}) {
        // This would integrate with your analytics service
        console.log('Event tracked:', eventName, properties);
        
        // Example: Google Analytics 4
        if (typeof gtag !== 'undefined') {
            gtag('event', eventName, properties);
        }
    }
    
    // Update the live preview
    function updatePreview() {
        const paperColors = {
            white: '#ffffff',
            ivory: '#f5f5dc',
            light_blue: '#e6f3ff',
            light_gray: '#f0f0f0'
        };
        
        const paperColor = paperColors[currentConfig.paper_color] || '#ffffff';
        
        let diplomaHTML = generateDiplomaHTML(paperColor);
        $('#diploma-canvas').html(diplomaHTML);
    }
    
    // Generate diploma HTML
    function generateDiplomaHTML(paperColor) {
        const schoolName = currentConfig.school_name || '[School Name]';
        const studentName = currentConfig.student_name || '[Student Name]';
        const graduationDate = currentConfig.graduation_date || '[Date of Graduation]';
        const city = currentConfig.city || '[City]';
        const state = currentConfig.state || '[State]';
        
        // Get emblem info
        const emblemInfo = getEmblemInfo();
        
        return `
            <div class="diploma-template ${currentConfig.diploma_style}" style="background-color: ${paperColor};">
                ${emblemInfo.html}
                <div class="diploma-header">
                    <div class="diploma-title">High School Diploma</div>
                    <div class="diploma-subtitle">This certifies that</div>
                </div>
                <div class="diploma-body">
                    <div class="diploma-text">
                        <strong>${studentName}</strong>
                    </div>
                    <div class="diploma-text">
                        has satisfactorily completed the prescribed course of study at
                    </div>
                    <div class="school-name">${schoolName}</div>
                    <div class="diploma-text">
                        and is therefore entitled to this diploma
                    </div>
                    <div class="graduation-date">Dated this ${graduationDate}</div>
                    <div class="location">${city}, ${state}</div>
                </div>
            </div>
        `;
    }
    
    // Get emblem information
    function getEmblemInfo() {
        const diplomaStyles = {
            classic: { emblems: 1 },
            modern: { emblems: 2 },
            formal: { emblems: 1 },
            decorative: { emblems: 2 },
            minimalist: { emblems: 1 }
        };
        
        const template = diplomaStyles[currentConfig.diploma_style] || diplomaStyles.classic;
        
        // Special handling for preview emblem
        if (currentConfig.emblem_type === 'generic' && currentConfig.emblem_value === 'school_preview') {
            // For preview emblem, we show a special placeholder
            let emblemHTML = '';
            if (template.emblems === 1) {
                emblemHTML = `
                    <div class="diploma-emblems single">
                        <div class="preview-emblem-placeholder" style="width:100px;height:100px;background:#f0f8ff;border:2px dashed #3498db;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:30px;color:#3498db;">
                            <div>👁️</div>
                        </div>
                    </div>
                `;
            } else {
                emblemHTML = `
                    <div class="diploma-emblems">
                        <div class="preview-emblem-placeholder" style="width:100px;height:100px;background:#f0f8ff;border:2px dashed #3498db;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:30px;color:#3498db;">
                            <div>👁️</div>
                        </div>
                        <div class="preview-emblem-placeholder" style="width:100px;height:100px;background:#f0f8ff;border:2px dashed #3498db;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:30px;color:#3498db;">
                            <div>👁️</div>
                        </div>
                    </div>
                `;
            }
            return { html: emblemHTML, count: template.emblems };
        }
        
        if (!currentConfig.emblem_value) {
            return { html: '', count: 0 };
        }
        
        const emblemSrc = getEmblemSrc();
        let emblemHTML = '';
        
        if (template.emblems === 1) {
            emblemHTML = `
                <div class="diploma-emblems single">
                    <img src="${emblemSrc}" alt="Emblem" class="diploma-emblem" 
                         onerror="this.parentNode.innerHTML='<div class=\\'emblem-placeholder\\'><div>${currentConfig.emblem_value.substring(0, 3)}</div></div>'">
                </div>
            `;
        } else {
            emblemHTML = `
                <div class="diploma-emblems">
                    <img src="${emblemSrc}" alt="Emblem" class="diploma-emblem" 
                         onerror="this.parentNode.innerHTML='<div class=\\'emblem-placeholder\\'><div>${currentConfig.emblem_value.substring(0, 3)}</div></div>'">
                    <img src="${emblemSrc}" alt="Emblem" class="diploma-emblem" 
                         onerror="this.parentNode.innerHTML='<div class=\\'emblem-placeholder\\'><div>${currentConfig.emblem_value.substring(0, 3)}</div></div>'">
                </div>
            `;
        }
        
        return { html: emblemHTML, count: template.emblems };
    }
    
    // Get emblem source URL
    function getEmblemSrc() {
        // Special handling for preview emblem
        if (currentConfig.emblem_type === 'generic' && currentConfig.emblem_value === 'school_preview') {
            // For preview emblem, we'll show a special placeholder
            return '';
        }
        
        if (currentConfig.emblem_type === 'generic') {
            return `${diploma_ajax.plugin_url}assets/emblems/generic/${currentConfig.emblem_value}.jpg`;
        } else if (currentConfig.emblem_type === 'state' && currentConfig.emblem_value) {
            return `${diploma_ajax.plugin_url}assets/emblems/states/${currentConfig.emblem_value}.jpg`;
        }
        return `${diploma_ajax.plugin_url}assets/emblems/generic/graduation_cap.jpg`;
    }
    
    // Navigate between steps
    function navigateSteps(direction) {
        const currentStep = $('.form-section:visible').data('step');
        let nextStep;
        
        if (direction === 'next') {
            nextStep = currentStep + 1;
            if (nextStep > 4) nextStep = 4;
        } else {
            nextStep = currentStep - 1;
            if (nextStep < 1) nextStep = 1;
        }
        
        // Hide all sections and show the next one
        $('.form-section').hide();
        $(`.form-section[data-step="${nextStep}"]`).show();
        
        // Update navigation buttons
        $('#prev-step').prop('disabled', nextStep === 1);
        $('#next-step').toggle(nextStep < 4);
        $('.form-actions').toggle(nextStep === 4);
        
        // Update progress bar
        updateProgressBar();
    }
    
    // Update progress bar
    function updateProgressBar() {
        const visibleStep = $('.form-section:visible').data('step') || 1;
        const progress = (visibleStep / 4) * 100;
        $('.progress-fill').css('width', `${progress}%`);
        
        // Update step indicators
        $('.step').removeClass('active');
        for (let i = 1; i <= visibleStep; i++) {
            $(`.step[data-step="${i}"]`).addClass('active');
        }
    }
    
    // Zoom preview
    function zoomPreview(delta) {
        const canvas = $('.diploma-canvas');
        const currentZoom = parseFloat(canvas.data('zoom') || 1);
        const newZoom = Math.max(0.5, Math.min(2, currentZoom + delta));
        
        canvas.css('transform', `scale(${newZoom})`);
        canvas.data('zoom', newZoom);
        $('#zoom-level').text(`${Math.round(newZoom * 100)}%`);
    }
    
    // Toggle fullscreen
    function toggleFullscreen() {
        const container = $('#diploma-builder-container');
        const preview = $('.diploma-preview-container');
        
        container.toggleClass('fullscreen-mode');
        
        if (container.hasClass('fullscreen-mode')) {
            // Enter fullscreen mode
            container.css({
                position: 'fixed',
                top: 0,
                left: 0,
                width: '100%',
                height: '100%',
                zIndex: 9999,
                padding: '20px',
                backgroundColor: 'rgba(0,0,0,0.95)'
            });
            
            preview.css({
                flex: '0 0 100%',
                maxWidth: '100%',
                maxHeight: '100%',
                backgroundColor: '#fff',
                padding: '30px'
            });
            
            $('.diploma-builder-form').hide();
            $('#toggle-fullscreen').text('❐');
        } else {
            // Exit fullscreen mode
            container.removeAttr('style');
            preview.removeAttr('style');
            $('.diploma-builder-form').show();
            $('#toggle-fullscreen').text('⛶');
        }
    }
    
    // Save diploma configuration
    function saveDiploma() {
        if (!validateForm()) {
            return;
        }
        
        showLoading();
        
        const data = {
            action: 'save_diploma',
            nonce: diploma_ajax.nonce,
            ...currentConfig
        };
        
        $.ajax({
            url: diploma_ajax.ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
                hideLoading();
                if (response.success) {
                    showMessage('Diploma saved successfully!', 'success');
                    $('#success-message').text(response.data.message);
                    $('#success-modal').show();
                } else {
                    showMessage('Error saving diploma: ' + response.data, 'error');
                }
            },
            error: function() {
                hideLoading();
                showMessage('Error saving diploma. Please try again.', 'error');
            }
        });
    }
    
    // Download high-resolution diploma
    function downloadDiploma() {
        if (!validateForm()) {
            return;
        }
        
        showLoading();
        
        // Use html2canvas to capture the diploma
        html2canvas(document.getElementById('diploma-canvas'), {
            scale: 3, // High resolution
            backgroundColor: null,
            width: 1275, // 8.5" * 150 DPI
            height: 1650, // 11" * 150 DPI
            useCORS: true,
            allowTaint: false
        }).then(function(canvas) {
            // Create download link
            const link = document.createElement('a');
            link.download = `diploma_${currentConfig.school_name.replace(/[^a-z0-9]/gi, '_')}_${Date.now()}.png`;
            link.href = canvas.toDataURL('image/png', 1.0);
            
            // Trigger download
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            hideLoading();
            showMessage('Diploma downloaded successfully!', 'success');
            
            // Also save to server
            saveImageToServer(canvas.toDataURL('image/png', 1.0));
        }).catch(function(error) {
            hideLoading();
            console.error('Error generating diploma:', error);
            showMessage('Error generating diploma. Please try again.', 'error');
        });
    }
    
    // Save generated image to server
    function saveImageToServer(imageData) {
        const data = {
            action: 'generate_diploma_image',
            nonce: diploma_ajax.nonce,
            image_data: imageData,
            ...currentConfig
        };
        
        $.ajax({
            url: diploma_ajax.ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
                console.log('Image saved to server:', response);
            },
            error: function() {
                console.log('Error saving image to server');
            }
        });
    }
    
    // Validate form data
    function validateForm() {
        const requiredFields = ['school_name', 'graduation_date', 'city', 'state'];
        let isValid = true;
        let missingFields = [];
        
        requiredFields.forEach(function(field) {
            if (!currentConfig[field] || currentConfig[field].trim() === '') {
                isValid = false;
                missingFields.push(field.replace('_', ' '));
            }
        });
        
        if (!isValid) {
            showMessage(`Please fill in all required fields: ${missingFields.join(', ')}`, 'error');
        }
        
        return isValid;
    }
    
    // Show loading overlay
    function showLoading() {
        $('#loading-overlay').fadeIn(200);
    }
    
    // Hide loading overlay
    function hideLoading() {
        $('#loading-overlay').fadeOut(200);
    }
    
    // Show message to user
    function showMessage(message, type) {
        // Remove existing messages
        $('.diploma-message').remove();
        
        const messageClass = type === 'success' ? 'success' : 'error';
        const messageHTML = `
            <div class="diploma-message ${messageClass}">
                ${message}
            </div>
        `;
        
        $('body').append(messageHTML);
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            $('.diploma-message').fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    // Reset form
    function resetForm() {
        // Reset configuration
        currentConfig = {
            diploma_style: 'classic',
            paper_color: 'white',
            emblem_type: 'generic',
            emblem_value: 'graduation_cap',
            school_name: '',
            student_name: '',
            graduation_date: '',
            city: '',
            state: ''
        };
        
        // Reset form fields
        $('#diploma_style_select').val('classic');
        $('input[name="paper_color"][value="white"]').prop('checked', true);
        
        $('#school_name').val('');
        $('#student_name').val('');
        $('#graduation_date').val('');
        $('#city').val('');
        $('#state').val('');
        
        // Reset UI
        $('.form-section').hide();
        $('.form-section[data-step="1"]').show();
        $('#prev-step').prop('disabled', true);
        $('#next-step').show();
        $('.form-actions').hide();
        
        // Reset progress
        updateProgressBar();
        
        // Update preview and summary
        updatePreview();
        updateReviewSummary();
    }
    
    // Initialize everything when document is ready
    init();
});