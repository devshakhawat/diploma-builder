jQuery(document).ready(function($) {
    'use strict';
    
    // Current diploma configuration
    let currentConfig = {
        diploma_style: 'classic',
        paper_color: 'white',
        emblem_type: 'generic',
        emblem_value: 'graduation_cap',
        school_name: '',
        graduation_date: '',
        city: '',
        state: ''
    };
    
    // Diploma style templates
    const diplomaTemplates = {
        classic: {
            name: 'Classic Traditional',
            emblems: 1,
            borderStyle: '15px solid #8B4513'
        },
        modern: {
            name: 'Modern Elegant',
            emblems: 2,
            borderStyle: '3px solid #007cba'
        },
        formal: {
            name: 'Formal Certificate',
            emblems: 1,
            borderStyle: '8px double #333'
        },
        decorative: {
            name: 'Decorative Border',
            emblems: 2,
            borderStyle: '20px solid #DAA520'
        },
        minimalist: {
            name: 'Minimalist Clean',
            emblems: 1,
            borderStyle: '1px solid #ccc'
        }
    };
    
    // Paper colors
    const paperColors = {
        white: '#ffffff',
        ivory: '#f5f5dc',
        light_blue: '#e6f3ff',
        light_gray: '#f0f0f0'
    };
    
    // Generic emblems
    const genericEmblems = {
        graduation_cap: 'Graduation Cap',
        diploma_seal: 'Diploma Seal',
        academic_torch: 'Academic Torch',
        laurel_wreath: 'Laurel Wreath',
        school_crest: 'School Crest'
    };
    
    // Initialize the diploma builder
    function init() {
        bindEvents();
        updatePreview();
    }
    
    // Bind all event handlers
    function bindEvents() {
        // Diploma style selection
        $('input[name="diploma_style"]').on('change', function() {
            currentConfig.diploma_style = $(this).val();
            updatePreview();
        });
        
        // Paper color selection
        $('input[name="paper_color"]').on('change', function() {
            currentConfig.paper_color = $(this).val();
            updatePreview();
        });
        
        // Emblem type selection
        $('input[name="emblem_type"]').on('change', function() {
            currentConfig.emblem_type = $(this).val();
            toggleEmblemOptions();
            updateEmblemValue();
            updatePreview();
        });
        
        // Generic emblem selection
        $('input[name="generic_emblem"]').on('change', function() {
            currentConfig.emblem_value = $(this).val();
            updatePreview();
        });
        
        // State emblem selection
        $('#state-emblem-select').on('change', function() {
            currentConfig.emblem_value = $(this).val();
            updatePreview();
        });
        
        // Text field changes
        $('.text-fields input').on('input', function() {
            const fieldName = $(this).attr('name');
            currentConfig[fieldName] = $(this).val();
            updatePreview();
        });
        
        // Save diploma
        $('#save-diploma').on('click', saveDiploma);
        
        // Download high-res diploma
        $('#download-diploma').on('click', downloadDiploma);
    }
    
    // Toggle emblem options based on type
    function toggleEmblemOptions() {
        if (currentConfig.emblem_type === 'generic') {
            $('#generic-emblems').show();
            $('#state-emblems').hide();
        } else {
            $('#generic-emblems').hide();
            $('#state-emblems').show();
        }
    }
    
    // Update emblem value when type changes
    function updateEmblemValue() {
        if (currentConfig.emblem_type === 'generic') {
            currentConfig.emblem_value = $('input[name="generic_emblem"]:checked').val() || 'graduation_cap';
        } else {
            currentConfig.emblem_value = $('#state-emblem-select').val() || '';
        }
    }
    
    // Update the live preview
    function updatePreview() {
        const template = diplomaTemplates[currentConfig.diploma_style];
        const paperColor = paperColors[currentConfig.paper_color];
        
        let diplomaHTML = generateDiplomaHTML(template, paperColor);
        $('#diploma-canvas').html(diplomaHTML);
    }
    
    // Generate diploma HTML
    function generateDiplomaHTML(template, paperColor) {
        const schoolName = currentConfig.school_name || '[School Name]';
        const graduationDate = currentConfig.graduation_date || '[Date of Graduation]';
        const city = currentConfig.city || '[City]';
        const state = currentConfig.state || '[State]';
        
        let emblemHTML = '';
        if (currentConfig.emblem_value) {
            const emblemSrc = getEmblemSrc();
            if (template.emblems === 1) {
                emblemHTML = `<div class="diploma-emblems single"><img src="${emblemSrc}" alt="Emblem" class="diploma-emblem"></div>`;
            } else {
                emblemHTML = `<div class="diploma-emblems"><img src="${emblemSrc}" alt="Emblem" class="diploma-emblem"><img src="${emblemSrc}" alt="Emblem" class="diploma-emblem"></div>`;
            }
        }
        
        return `
            <div class="diploma-template ${currentConfig.diploma_style}" style="background-color: ${paperColor};">
                ${emblemHTML}
                <div class="diploma-header">
                    <div class="diploma-title">High School Diploma</div>
                    <div class="diploma-subtitle">This certifies that</div>
                </div>
                <div class="diploma-body">
                    <div class="diploma-text">
                        <strong>[Student Name]</strong>
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
    
    // Get emblem source URL
    function getEmblemSrc() {
        if (currentConfig.emblem_type === 'generic') {
            return `${diploma_ajax.plugin_url}assets/emblems/generic/${currentConfig.emblem_value}.png`;
        } else if (currentConfig.emblem_type === 'state' && currentConfig.emblem_value) {
            return `${diploma_ajax.plugin_url}assets/emblems/states/${currentConfig.emblem_value}.png`;
        }
        return `${diploma_ajax.plugin_url}assets/emblems/generic/graduation_cap.png`;
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
            <div class="diploma-message ${messageClass}" style="
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 5px;
                color: white;
                font-weight: 600;
                z-index: 10000;
                max-width: 300px;
                background: ${type === 'success' ? '#28a745' : '#dc3545'};
                box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            ">
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
    
    // Add click-to-dismiss functionality for messages
    $(document).on('click', '.diploma-message', function() {
        $(this).fadeOut(300, function() {
            $(this).remove();
        });
    });
    
    // Handle state emblem loading
    $('#state-emblem-select').on('change', function() {
        const selectedState = $(this).val();
        if (selectedState) {
            // In a real implementation, you would have actual state emblems
            // For demo purposes, we'll use placeholder logic
            currentConfig.emblem_value = selectedState;
            updatePreview();
        }
    });
    
    // Add keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Ctrl+S to save
        if (e.ctrlKey && e.which === 83) {
            e.preventDefault();
            saveDiploma();
        }
        
        // Ctrl+D to download
        if (e.ctrlKey && e.which === 68) {
            e.preventDefault();
            downloadDiploma();
        }
    });
    
    // Auto-save functionality (optional)
    let autoSaveTimeout;
    function setupAutoSave() {
        $('.text-fields input').on('input', function() {
            clearTimeout(autoSaveTimeout);
            autoSaveTimeout = setTimeout(function() {
                // Auto-save configuration to localStorage
                if (typeof(Storage) !== "undefined") {
                    localStorage.setItem('diploma_config', JSON.stringify(currentConfig));
                }
            }, 2000); // Save 2 seconds after user stops typing
        });
    }
    
    // Load saved configuration
    function loadSavedConfig() {
        if (typeof(Storage) !== "undefined") {
            const saved = localStorage.getItem('diploma_config');
            if (saved) {
                try {
                    const savedConfig = JSON.parse(saved);
                    // Merge saved config with current config
                    currentConfig = { ...currentConfig, ...savedConfig };
                    
                    // Update form fields
                    updateFormFromConfig();
                    updatePreview();
                } catch (e) {
                    console.log('Error loading saved configuration:', e);
                }
            }
        }
    }
    
    // Update form fields from configuration
    function updateFormFromConfig() {
        $(`input[name="diploma_style"][value="${currentConfig.diploma_style}"]`).prop('checked', true);
        $(`input[name="paper_color"][value="${currentConfig.paper_color}"]`).prop('checked', true);
        $(`input[name="emblem_type"][value="${currentConfig.emblem_type}"]`).prop('checked', true);
        
        if (currentConfig.emblem_type === 'generic') {
            $(`input[name="generic_emblem"][value="${currentConfig.emblem_value}"]`).prop('checked', true);
        } else {
            $('#state-emblem-select').val(currentConfig.emblem_value);
        }
        
        $('#school_name').val(currentConfig.school_name);
        $('#graduation_date').val(currentConfig.graduation_date);
        $('#city').val(currentConfig.city);
        $('#state').val(currentConfig.state);
        
        toggleEmblemOptions();
    }
    
    // Initialize everything when document is ready
    init();
    setupAutoSave();
    loadSavedConfig();
});