jQuery(document).ready(function($) {
    'use strict';
    
    // Current diploma configuration
    let currentConfig = {
        diploma_style: '',
        paper_color: '',
        emblem_type: 'generic',
        emblem_value: '',
        school_name: '',
        student_name: '',
        graduation_date: '',
        city: '',
        state: '',
        country: '',
        state_province_region: '',
        document_type: '',
        diploma_size: '',
        degree_type: '',
        major: '',
        concentration: '',
        signature_count: '1',
        signature1_name: '',
        signature2_name: ''
    };

    // Current step tracker
    let currentStep = 1;

    // Carousel state
    let currentPage = 0;
    let totalSlides = 0;
    let slidesPerPage = 4;
    let totalPages = 0;

    // Diploma size options based on document type
    const diplomaSizeOptions = {
        'GED': [
            { value: '8.5x11', label: '8.5" × 11" (Letter)' },
            { value: '7.5x9.5', label: '7.5" × 9.5"' }
        ],
        'High School': [
            { value: '8.5x11', label: '8.5" × 11" (Letter)' },
            { value: '7.5x9.5', label: '7.5" × 9.5"' }
        ],
        'College': [
            { value: '8.5x11', label: '8.5" × 11" (Letter)' },
            { value: '11x14', label: '11" × 14"' }
        ],
        'University': [
            { value: '8.5x11', label: '8.5" × 11" (Letter)' },
            { value: '11x14', label: '11" × 14"' }
        ]
    };

    // Update diploma size options based on document type
    function updateDiplomaSizeOptions(documentType) {
        const $sizeSelect = $('#diploma_size');
        const currentSize = $sizeSelect.val();
        const options = diplomaSizeOptions[documentType] || diplomaSizeOptions['High School'];

        // Clear existing options except the first placeholder
        $sizeSelect.find('option:not(:first)').remove();

        // Add new options
        options.forEach(function(option) {
            $sizeSelect.append(`<option value="${option.value}">${option.label}</option>`);
        });

        // Try to maintain the current selection if it exists in new options
        const isCurrentSizeAvailable = options.some(opt => opt.value === currentSize);
        if (isCurrentSizeAvailable) {
            $sizeSelect.val(currentSize);
        } else {
            // Select the first available option (Letter size)
            $sizeSelect.val(options[0].value);
            currentConfig.diploma_size = options[0].value;
        }
    }

    // Filter major options based on selected degree type
    function filterMajorOptions(degreeType) {
        const $majorSelect = $('#major');
        const $allOptgroups = $majorSelect.find('optgroup');
        const currentMajor = $majorSelect.val();

        if (!degreeType || degreeType === '') {
            // If no degree type selected, show all optgroups
            $allOptgroups.show();
            return;
        }

        // Hide all optgroups first
        $allOptgroups.hide();

        // Show only optgroups that match the selected degree type
        $allOptgroups.each(function() {
            const $optgroup = $(this);
            const optgroupDegreeType = $optgroup.attr('data-degree-type');

            if (optgroupDegreeType === degreeType) {
                $optgroup.show();
            }
        });

        // Check if current selection is still visible
        const $currentOption = $majorSelect.find(`option[value="${currentMajor}"]`);
        const isCurrentVisible = $currentOption.length > 0 && $currentOption.closest('optgroup').is(':visible');

        // If current selection is not visible, reset to empty
        if (!isCurrentVisible && currentMajor !== '') {
            $majorSelect.val('');
            currentConfig.major = '';
            updatePreview();
        }
    }

    // Handle signature count and show/hide fields
    function handleSignatureCount(count) {
        const $signature2Field = $('#signature2-field');
        const $signature2Input = $('#signature2_name');

        if (count === '2') {
            $signature2Field.slideDown(300);
            $signature2Input.prop('required', true);
        } else {
            $signature2Field.slideUp(300);
            $signature2Input.prop('required', false);
            $signature2Input.val('');
            currentConfig.signature2_name = '';
        }
    }

    // Handle country selection and enable/disable fields
    function handleCountrySelection(selectedCountry) {
        // Select school fields directly since subsection wrapper was removed from Step 3
        const schoolFields = $('#school_name, #city, #state').closest('.field-group, .field-row');
        const graduationFields = $('#student_name, #graduation_date').closest('.field-group');
        const styleSubsection = $('.subsection').has('#diploma_style');
        const paperSubsection = $('.subsection').has('#paper_color');
        const emblemFields = $('.emblem-type-tabs').closest('.emblem-selection-section');

        // Filter state_province_region dropdown based on selected country
        const $regionSelect = $('#state_province_region');
        const $allOptions = $regionSelect.find('option:not(:first)');

        // Reset selection
        $regionSelect.val('');

        // Hide all options first
        $allOptions.hide().prop('disabled', true);

        // Show only options for the selected country
        if (selectedCountry) {
            $allOptions.filter(`[data-country="${selectedCountry}"]`).show().prop('disabled', false);
        }

        if (selectedCountry === 'USA') {
            // Enable all fields for USA
            schoolFields.removeClass('disabled-subsection');
            $('#school_name, #city, #state').prop('disabled', false);
            graduationFields.removeClass('disabled-subsection');
            $('#student_name, #graduation_date').prop('disabled', false);
            styleSubsection.removeClass('disabled-subsection').find('input, select').prop('disabled', false);
            paperSubsection.removeClass('disabled-subsection').find('input, select').prop('disabled', false);
            emblemFields.removeClass('disabled-subsection').find('input, select, button').prop('disabled', false);
        } else {
            // Enable all fields for other countries too
            schoolFields.removeClass('disabled-subsection');
            $('#school_name, #city, #state').prop('disabled', false);
            graduationFields.removeClass('disabled-subsection');
            $('#student_name, #graduation_date').prop('disabled', false);
            styleSubsection.removeClass('disabled-subsection').find('input, select').prop('disabled', false);
            paperSubsection.removeClass('disabled-subsection').find('input, select').prop('disabled', false);
            emblemFields.removeClass('disabled-subsection').find('input, select, button').prop('disabled', false);
        }
    }
    
    // Get slides per page based on screen width
    function getSlidesPerPage() {
        const width = $(window).width();
        if (width <= 768) {
            return 1; // Mobile: 1 item
        } else if (width <= 992) {
            return 2; // Tablet: 2 items
        } else {
            return 4; // Desktop: 4 items
        }
    }

    // Initialize carousel
    function initCarousel() {
        totalSlides = $('.carousel-slide').length;
        slidesPerPage = getSlidesPerPage();
        totalPages = Math.ceil(totalSlides / slidesPerPage);
        currentPage = 0;
        updateCarousel();
        updateCarouselIndicators();
    }

    // Reinitialize carousel on window resize
    let resizeTimer;
    $(window).on('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            const newSlidesPerPage = getSlidesPerPage();
            if (newSlidesPerPage !== slidesPerPage) {
                slidesPerPage = newSlidesPerPage;
                totalPages = Math.ceil(totalSlides / slidesPerPage);
                currentPage = 0;
                updateCarousel();
                updateCarouselIndicators();
            }
        }, 250);
    });

    // Update carousel position
    function updateCarousel() {
        const $track = $('#carousel-track');
        const $slides = $('.carousel-slide');

        // Update slide visibility - mark slides on current page as active
        $slides.removeClass('active');
        const startIndex = currentPage * slidesPerPage;
        const endIndex = Math.min(startIndex + slidesPerPage, totalSlides);

        for (let i = startIndex; i < endIndex; i++) {
            $slides.eq(i).addClass('active');
        }

        // Update navigation button states
        $('#carousel-prev').prop('disabled', currentPage === 0);
        $('#carousel-next').prop('disabled', currentPage === totalPages - 1);

        // Transform track - calculate based on slide width percentage
        const slideWidthPercent = 100 / slidesPerPage;
        const offset = -(currentPage * slidesPerPage * slideWidthPercent);
        $track.css('transform', `translateX(${offset}%)`);
    }

    // Update carousel indicators to show pages instead of individual slides
    function updateCarouselIndicators() {
        const $indicatorsContainer = $('#carousel-indicators');
        $indicatorsContainer.empty();

        for (let i = 0; i < totalPages; i++) {
            const isActive = i === currentPage ? 'active' : '';
            $indicatorsContainer.append(
                `<button type="button" class="carousel-indicator ${isActive}" data-page="${i}"></button>`
            );
        }
    }

    // Navigate carousel by page
    function navigateCarousel(direction) {
        if (direction === 'next' && currentPage < totalPages - 1) {
            currentPage++;
        } else if (direction === 'prev' && currentPage > 0) {
            currentPage--;
        }
        updateCarousel();
        updatePageIndicators();
    }

    // Update page indicators
    function updatePageIndicators() {
        $('.carousel-indicator').removeClass('active');
        $(`.carousel-indicator[data-page="${currentPage}"]`).addClass('active');
    }

    // Go to specific page
    function goToPage(pageIndex) {
        if (pageIndex >= 0 && pageIndex < totalPages) {
            currentPage = pageIndex;
            updateCarousel();
            updatePageIndicators();
        }
    }

    // Auto-select style when clicking on slide
    function selectStyleFromSlide(slideIndex) {
        const $slide = $('.carousel-slide').eq(slideIndex);
        const styleValue = $slide.data('style');
        $(`input[name="diploma_style"][value="${styleValue}"]`).prop('checked', true).trigger('change');
    }

    // Initialize the diploma builder
    function init() {
        bindEvents();
        initializeForm();
        initCarousel();
        // Hide loading overlay on initialization
        hideLoading();
        // Prevent right-click and dragging on diploma preview
        preventDiplomaDownload();
    }

    // Prevent right-click download and dragging of diploma preview
    function preventDiplomaDownload() {
        const $preview = $('#diploma-preview');
        const $previewWrapper = $('#diploma-preview-wrapper');

        // Disable context menu (right-click)
        $preview.on('contextmenu', function(e) {
            e.preventDefault();
            return false;
        });

        $previewWrapper.on('contextmenu', function(e) {
            e.preventDefault();
            return false;
        });

        // Disable dragging
        $preview.on('dragstart', function(e) {
            e.preventDefault();
            return false;
        });

        // Disable selection
        $preview.css({
            '-webkit-user-select': 'none',
            '-moz-user-select': 'none',
            '-ms-user-select': 'none',
            'user-select': 'none'
        });

        // Prevent all images inside preview from being downloaded
        $preview.on('contextmenu', 'img', function(e) {
            e.preventDefault();
            return false;
        });

        $preview.on('dragstart', 'img', function(e) {
            e.preventDefault();
            return false;
        });
    }
    
    // Initialize form to show first step only
    function initializeForm() {
        // Show only Step 1 initially
        $('.step-1-card').show();
        $('.step-2-card, .step-3-card, .step-4-card').hide();

        // Sync currentConfig with default values from form fields
        syncConfigFromForm();

        // Initialize country selection to show appropriate regions
        const initialCountry = $('#country').val();
        if (initialCountry) {
            handleCountrySelection(initialCountry);
        }

        // Initialize the was-checked state for diploma style radio buttons
        $('input[name="diploma_style"]:checked').data('was-checked', true);

        // Check if Step 1 is already complete (page refresh or default values)
        if (validateStep1()) {
            markStepComplete(1);
            showStepCard(2);

            // Check if Step 2 (diploma style) is also complete
            const selectedStyle = $('input[name="diploma_style"]:checked').val();
            if (selectedStyle) {
                currentConfig.diploma_style = selectedStyle;
                markStepComplete(2);
                showStepCard(3);
                showStepCard(4);
                showStepCard(5);
                updatePreview();
            }
        }

        // Filter major options based on any pre-selected degree type
        const initialDegreeType = $('#degree_type').val();
        if (initialDegreeType) {
            filterMajorOptions(initialDegreeType);
        }


    }

    // Sync currentConfig with form field values
    function syncConfigFromForm() {
        currentConfig.country = $('#country').val() || '';
        currentConfig.state_province_region = $('#state_province_region').val() || '';
        currentConfig.document_type = $('#document_type').val() || '';
        currentConfig.diploma_size = $('#diploma_size').val() || '';
        currentConfig.paper_color = $('input[name="paper_color"]:checked').val() || '';
        currentConfig.diploma_style = $('input[name="diploma_style"]:checked').val() || '';
        currentConfig.emblem_value = $('input[name="emblem_value"]:checked').val() || '';
        currentConfig.school_name = $('#school_name').val() || '';
        currentConfig.student_name = $('#student_name').val() || '';
        currentConfig.graduation_date = $('#graduation_date').val() || '';
        currentConfig.city = $('#city').val() || '';
        currentConfig.state = $('#state').val() || '';
        currentConfig.degree_type = $('#degree_type').val() || '';
        currentConfig.major = $('#major').val() || '';
        currentConfig.concentration = $('#concentration').val() || '';
        currentConfig.signature_count = $('#signature_count').val() || '1';
        currentConfig.signature1_name = $('#signature1_name').val() || '';
        currentConfig.signature2_name = $('#signature2_name').val() || '';
    }
    
    // Validate Step 1 fields
    function validateStep1() {
        const country = $('#country').val();
        const documentType = $('#document_type').val();
        const diplomaSize = $('#diploma_size').val();
        const paperColor = $('input[name="paper_color"]:checked').val();

        return country && documentType && diplomaSize && paperColor;
    }

    // Check and enable/disable Step 1 continue button
    function checkStep1Completion() {
        const isValid = validateStep1();
        $('#step1-continue').prop('disabled', !isValid);
    }

    // Show step card function
    function showStepCard(stepNumber) {
        const $stepCard = $(`.step-card[data-step="${stepNumber}"]`);

        if ($stepCard.length && !$stepCard.is(':visible')) {
            // Prefill Step 3 location fields from Step 1 when Step 3 opens
            if (stepNumber === 3) {
                prefillStep3LocationFromStep1();
            }

            $stepCard.slideDown(400, function() {
                // Scroll to the newly revealed step
                $('html, body').animate({
                    scrollTop: $stepCard.offset().top - 100
                }, 300);
            });
        }
    }

    // Prefill Step 3 Country & Region from Step 1 (callable before bindEvents sets up the inline version)
    function prefillStep3LocationFromStep1() {
        const isEditable = diploma_ajax.allow_edit_location && diploma_ajax.allow_edit_location == '1';

        const countryVal = $('#country').val() || '';
        const regionSelect = $('#state_province_region');
        const regionText = regionSelect.find('option:selected').text() || '';
        const regionVal = regionSelect.val() || '';

        if (!isEditable) {
            $('#step3_country').val(countryVal);
            $('#step3_region').val(regionVal ? regionText : '');
        } else {
            if (!$('#step3_country').val()) $('#step3_country').val(countryVal);
            if (!$('#step3_region').val()) $('#step3_region').val(regionVal ? regionText : '');
        }
    }

    // Hide step card function
    function hideStepCard(stepNumber) {
        const $stepCard = $(`.step-card[data-step="${stepNumber}"]`);

        if ($stepCard.length && $stepCard.is(':visible')) {
            $stepCard.slideUp(400);
        }
    }

    // Mark step as complete
    function markStepComplete(stepNumber) {
        const $stepCard = $(`.step-card[data-step="${stepNumber}"]`);
        $stepCard.addClass('completed');
        $stepCard.find('.status-icon.incomplete').hide();
        $stepCard.find('.status-icon.complete').show();
    }

    // Mark step as incomplete
    function markStepIncomplete(stepNumber) {
        const $stepCard = $(`.step-card[data-step="${stepNumber}"]`);
        $stepCard.removeClass('completed');
        $stepCard.find('.status-icon.incomplete').show();
        $stepCard.find('.status-icon.complete').hide();
    }

    // Bind all event handlers
    function bindEvents() {
        // Step 1: Field change listeners
        $('#country, #document_type, #diploma_size').on('change', function() {
            checkStep1Completion();

            // Update config
            const fieldName = $(this).attr('name');
            currentConfig[fieldName] = $(this).val();

            // Auto-reveal Step 2 when Step 1 is complete
            if (validateStep1()) {
                markStepComplete(1);
                showStepCard(2);
            } else {
                markStepIncomplete(1);
                // Hide Step 2, Step 3, Step 4, and Step 5 when Step 1 is incomplete
                hideStepCard(2);
                hideStepCard(3);
                hideStepCard(4);
                hideStepCard(5);
                markStepIncomplete(2);
            }
        });

        $('input[name="paper_color"]').on('change', function() {
            checkStep1Completion();
            currentConfig.paper_color = $(this).val();
            updatePreview();

            // Auto-reveal Step 2 when Step 1 is complete
            if (validateStep1()) {
                markStepComplete(1);
                showStepCard(2);
            } else {
                markStepIncomplete(1);
                // Hide Step 2, Step 3, Step 4, and Step 5 when Step 1 is incomplete
                hideStepCard(2);
                hideStepCard(3);
                hideStepCard(4);
                hideStepCard(5);
                markStepIncomplete(2);
            }
        });

        // Carousel navigation
        $('#carousel-prev').on('click', function() {
            navigateCarousel('prev');
        });

        $('#carousel-next').on('click', function() {
            navigateCarousel('next');
        });

        // Carousel indicators
        $(document).on('click', '.carousel-indicator', function() {
            const pageIndex = $(this).data('page');
            goToPage(pageIndex);
        });

        // Carousel slide click - select or deselect style
        $(document).on('click', '.carousel-slide', function(e) {
            // Prevent default if clicking on the radio button directly
            if ($(e.target).is('input[type="radio"]')) {
                return;
            }

            const slideIndex = $(this).index();
            const $radio = $(this).find('input[type="radio"]');
            const wasChecked = $radio.prop('checked');

            if (wasChecked) {
                // Deselect if already selected
                $radio.prop('checked', false);
                currentConfig.diploma_style = '';
                markStepIncomplete(2);
                hideStepCard(3);
                hideStepCard(4);
                hideStepCard(5);
            } else {
                // Select if not selected
                selectStyleFromSlide(slideIndex);
            }
        });

        // Allow deselecting radio button by clicking on it when already selected
        $('input[name="diploma_style"]').on('click', function(e) {
            const $this = $(this);
            const wasChecked = $this.data('was-checked') === true;

            if (wasChecked) {
                // Deselect
                $this.prop('checked', false);
                $this.data('was-checked', false);
                currentConfig.diploma_style = '';
                markStepIncomplete(2);
                hideStepCard(3);
                hideStepCard(4);
                hideStepCard(5);
            } else {
                // Mark as checked
                $('input[name="diploma_style"]').data('was-checked', false);
                $this.data('was-checked', true);
            }
        });

        // Step 2: Style selection
        $('input[name="diploma_style"]').on('change', function() {
            const selectedValue = $(this).val();

            if (selectedValue) {
                currentConfig.diploma_style = selectedValue;

                // Update carousel to match selected style
                const $selectedSlide = $(`.carousel-slide[data-style="${selectedValue}"]`);
                const slideIndex = $selectedSlide.index();
                if (slideIndex >= 0) {
                    const pageIndex = Math.floor(slideIndex / slidesPerPage);
                    goToPage(pageIndex);
                }

                // Auto-reveal Step 3, Step 4, Step 5, and preview when style is selected
                markStepComplete(2);
                showStepCard(3);
                showStepCard(4);
                showStepCard(5);
                updatePreview();
            }
        });

        // Keyboard navigation for carousel
        $(document).on('keydown', function(e) {
            if ($('.step-2-card').is(':visible')) {
                if (e.key === 'ArrowLeft') {
                    navigateCarousel('prev');
                } else if (e.key === 'ArrowRight') {
                    navigateCarousel('next');
                }
            }
        });

        // Diploma style selection (dropdown) - legacy support
        $('#diploma_style').on('change', function() {
            currentConfig.diploma_style = $(this).val();
            updatePreview();
            updateReviewSummary();
        });
        
        // Remove template filter functionality as we now use dropdown
        
        // Paper color selection (dropdown)
        $('#paper_color').on('change', function() {
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

        // Emblem Carousel Functionality - Multi-Item
        let currentEmblemPage = 0;
        const $emblemTrack = $('#emblem-carousel-track');
        const $emblemSlides = $('.emblem-carousel-slide');
        const totalEmblemSlides = $emblemSlides.length;

        // Determine items per page based on screen width
        function getItemsPerPage() {
            const width = $(window).width();
            if (width <= 480) return 1;
            if (width <= 768) return 2;
            return 4;
        }

        function getTotalPages() {
            return Math.ceil(totalEmblemSlides / getItemsPerPage());
        }

        function updateEmblemCarousel() {
            const itemsPerPage = getItemsPerPage();
            const slideWidth = 100 / itemsPerPage;
            const offset = -(currentEmblemPage * slideWidth * itemsPerPage);

            // Apply transform to slide the carousel
            $emblemTrack.css('transform', `translateX(${offset}%)`);

            // Update navigation button states
            const totalPages = getTotalPages();
            $('#emblem-prev').prop('disabled', currentEmblemPage === 0);
            $('#emblem-next').prop('disabled', currentEmblemPage >= totalPages - 1);

            // Update indicators
            updateEmblemIndicators();
        }

        function updateEmblemIndicators() {
            const $indicatorsContainer = $('#emblem-carousel-indicators');
            $indicatorsContainer.empty();

            const totalPages = getTotalPages();
            for (let i = 0; i < totalPages; i++) {
                const isActive = i === currentEmblemPage ? 'active' : '';
                $indicatorsContainer.append(
                    `<button type="button" class="emblem-indicator ${isActive}" data-page="${i}"></button>`
                );
            }
        }

        function navigateEmblemCarousel(direction) {
            const totalPages = getTotalPages();
            if (direction === 'next' && currentEmblemPage < totalPages - 1) {
                currentEmblemPage++;
            } else if (direction === 'prev' && currentEmblemPage > 0) {
                currentEmblemPage--;
            }
            updateEmblemCarousel();
        }

        // Emblem carousel navigation
        $('#emblem-prev').on('click', function() {
            navigateEmblemCarousel('prev');
        });

        $('#emblem-next').on('click', function() {
            navigateEmblemCarousel('next');
        });

        // Emblem carousel indicators
        $(document).on('click', '.emblem-indicator', function() {
            currentEmblemPage = $(this).data('page');
            updateEmblemCarousel();
        });

        // Handle window resize
        let resizeTimer;
        $(window).on('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                // Reset to first page on resize to avoid issues
                currentEmblemPage = 0;
                updateEmblemCarousel();
            }, 250);
        });

        // Initialize emblem carousel
        if (totalEmblemSlides > 0) {
            updateEmblemCarousel();
        }
        
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

        // Country dropdown
        $('#country').on('change', function() {
            const selectedCountry = $(this).val();
            currentConfig.country = selectedCountry;
            validateField($(this));
            handleCountrySelection(selectedCountry);
            updatePreview();
            updateReviewSummary();
        });

        // State/Province/Region dropdown
        $('#state_province_region').on('change', function() {
            currentConfig.state_province_region = $(this).val();
            validateField($(this));
            prefillStep3Location();
            updatePreview();
            updateReviewSummary();
        });

        // Step 3: Prefill Country & Region from Step 1
        // Admin controls editability via Settings > Allow Editing Country & Region
        const locationEditable = diploma_ajax.allow_edit_location && diploma_ajax.allow_edit_location == '1';

        function prefillStep3Location() {
            const countryVal = $('#country').val() || '';
            const regionSelect = $('#state_province_region');
            const regionText = regionSelect.find('option:selected').text() || '';
            const regionVal = regionSelect.val() || '';

            // Always sync from Step 1 when fields are readonly; only sync editable fields if they're still empty
            if (!locationEditable) {
                $('#step3_country').val(countryVal);
                $('#step3_region').val(regionVal ? regionText : '');
            } else {
                if (!$('#step3_country').val()) $('#step3_country').val(countryVal);
                if (!$('#step3_region').val()) $('#step3_region').val(regionVal ? regionText : '');
            }
        }

        // Re-prefill whenever Step 1 country or region changes
        $('#country').on('change', function() {
            prefillStep3Location();
        });

        // Update preview when Step 3 location fields are edited (only possible when admin allows)
        $('#step3_country, #step3_region').on('input', function() {
            updatePreview();
            updateReviewSummary();
        });

        // Initial prefill
        prefillStep3Location();

        // Document type dropdown
        $('#document_type').on('change', function() {
            const documentType = $(this).val();
            currentConfig.document_type = documentType;
            validateField($(this));
            updateDiplomaSizeOptions(documentType);
            updatePreview();
            updateReviewSummary();
        });

        // Diploma size dropdown
        $('#diploma_size').on('change', function() {
            currentConfig.diploma_size = $(this).val();
            validateField($(this));
            updatePreview();
            updateReviewSummary();
        });

        // Degree type dropdown
        $('#degree_type').on('change', function() {
            const degreeType = $(this).val();
            currentConfig.degree_type = degreeType;
            validateField($(this));
            filterMajorOptions(degreeType);
            updatePreview();
            updateReviewSummary();
        });

        // Major dropdown
        $('#major').on('change', function() {
            currentConfig.major = $(this).val();
            validateField($(this));
            updatePreview();
            updateReviewSummary();
        });

        // Concentration text field
        $('#concentration').on('input', function() {
            currentConfig.concentration = $(this).val();
            validateField($(this));
            updatePreview();
            updateReviewSummary();
        });

        // Signature count dropdown
        $('#signature_count').on('change', function() {
            const count = $(this).val();
            currentConfig.signature_count = count;
            handleSignatureCount(count);
            validateField($(this));
            updatePreview();
            updateReviewSummary();
        });

        // Signature 1 name
        $('#signature1_name').on('input', function() {
            currentConfig.signature1_name = $(this).val();
            validateField($(this));
            updatePreview();
            updateReviewSummary();
        });

        // Signature 2 name
        $('#signature2_name').on('input', function() {
            currentConfig.signature2_name = $(this).val();
            validateField($(this));
            updatePreview();
            updateReviewSummary();
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

        $('#reset-zoom').on('click', function() {
            resetZoom();
        });

        $('#toggle-fullscreen').on('click', function() {
            toggleFullscreen();
        });
    }
    
    // Template filtering removed - now using dropdown selection
    
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
            currentConfig.emblem_value = $('input[name="emblem_value"][data-type="generic"]:checked').val() || '';
        } else {
            currentConfig.emblem_value = $('#state-emblem-select').val() || '';
        }
    }
    
    // Update state emblem preview
    function updateStateEmblemPreview(stateCode) {
        if (stateCode) {
            const stateName = $(`#state-emblem-select option[value="${stateCode}"]`).text();
            const emblemUrl = `${diploma_ajax.plugin_url}assets/emblems/states/${stateCode}.png`;
            
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
        const country = currentConfig.country || 'USA';
        $('#review-location').text(`${city}, ${state}, ${country}`);
        
        // Update style and paper info
        const styleName = $('#diploma_style option:selected').text() || '[Style]';
        const paperName = $('input[name="paper_color"]:checked').closest('.paper-color-option').find('.paper-color-name').text() || '[Paper]';

        $('#review-diploma-style').text(styleName);
        $('#review-paper-color').text(paperName);
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
        const paperImages = {
            parchment:  diploma_ajax.papers_url + 'parchment.jpg',
            ivory:      diploma_ajax.papers_url + 'ivory.jpg',
            light_blue: diploma_ajax.papers_url + 'light-blue.jpg',
            white:      diploma_ajax.papers_url + 'white.jpg'
        };

        const paperColors = {
            parchment:  '#e8d5b7',
            ivory:      '#f5f5dc',
            light_blue: '#dce8ef',
            white:      '#f5f5f0'
        };

        const paperColor = paperColors[currentConfig.paper_color] || '#f5f5f0';
        const paperImage = paperImages[currentConfig.paper_color] || '';

        // Update the background of the diploma preview
        $('#diploma-preview').css({
            'background-color': paperColor,
            'background-image': paperImage ? 'url(' + paperImage + ')' : 'none',
            'background-size': 'cover',
            'background-position': 'center'
        });

        // Paper color gradients for .classic-diploma background
        const paperGradients = {
            parchment:  { borderBg: 'rgba(232, 213, 183, 0.95)' },
            ivory:      { borderBg: 'rgba(244, 241, 232, 0.95)' },
            light_blue: { borderBg: 'rgba(220, 232, 239, 0.95)' },
            white:      { borderBg: 'rgba(245, 245, 240, 0.95)' }
        };
        const currentGradient = paperGradients[currentConfig.paper_color] || paperGradients.parchment;

        // Update diploma size class based on selected size
        const diplomaSize = currentConfig.diploma_size || '8.5x11';
        const sizeClass = 'size-' + diplomaSize.replace(/\./g, '-').replace('x', 'x');

        // Remove all existing size classes
        $('#diploma-preview').removeClass('size-8-5x11 size-7-5x9-5 size-11x14');

        // Add the current size class
        $('#diploma-preview').addClass(sizeClass);

        let diplomaHTML = generateDiplomaHTML();
        $('#diploma-preview').html(diplomaHTML);

        // Apply paper texture to the diploma elements
        $('#diploma-preview .diploma, #diploma-preview .classic-diploma, #diploma-preview .style2-diploma, #diploma-preview .style3-diploma, #diploma-preview .style4-diploma').css({
            'background-color': paperColor,
            'background-image': paperImage ? 'url(' + paperImage + ')' : 'none',
            'background-size': 'cover',
            'background-position': 'center'
        });

        // Apply paper color to .classic-diploma border via CSS custom properties
        var classicDiploma = $('#diploma-preview .classic-diploma');
        if (classicDiploma.length) {
            classicDiploma[0].style.setProperty('--paper-border-bg', currentGradient.borderBg);
        }

        // Apply paper color to .style2-diploma border via CSS custom properties
        var style2Diploma = $('#diploma-preview .style2-diploma');
        if (style2Diploma.length) {
            style2Diploma[0].style.setProperty('--paper-border-bg', currentGradient.borderBg);
        }

        // Also apply to non-classic diploma border
        var diplomaEl = $('#diploma-preview .diploma');
        if (diplomaEl.length) {
            diplomaEl[0].style.setProperty('--paper-border-bg', currentGradient.borderBg);
        }

        // Apply paper color to .style3-diploma border via CSS custom properties
        var style3Diploma = $('#diploma-preview .style3-diploma');
        if (style3Diploma.length) {
            style3Diploma[0].style.setProperty('--paper-border-bg', currentGradient.borderBg);
        }
    }

    // NEW: Function to split text for two-line arc header
    function splitSchoolNameForArc(schoolName, maxLineLength = 25) {
        if (!schoolName || schoolName.length <= maxLineLength) {
            return { line1: schoolName || '[School Name]', line2: '', isTwoLine: false };
        }
        
        // Split at a natural break point (space, comma, etc.)
        const words = schoolName.split(' ');
        let line1 = '';
        let line2 = '';
        let currentLength = 0;
        
        for (let i = 0; i < words.length; i++) {
            const word = words[i];
            const newLength = currentLength + word.length + (line1 ? 1 : 0); // +1 for space
            
            if (newLength <= maxLineLength || !line1) {
                line1 += (line1 ? ' ' : '') + word;
                currentLength = newLength;
            } else {
                line2 = words.slice(i).join(' ');
                break;
            }
        }
        
        // If line2 is too long, we might need to adjust the split
        if (line2.length > maxLineLength) {
            // Find a better split point
            const totalLength = schoolName.length;
            const midPoint = Math.floor(totalLength / 2);
            let splitIndex = schoolName.lastIndexOf(' ', midPoint);
            
            if (splitIndex === -1) {
                splitIndex = midPoint;
            }
            
            line1 = schoolName.substring(0, splitIndex).trim();
            line2 = schoolName.substring(splitIndex).trim();
        }
        
        return { 
            line1: line1 || '[School Name]', 
            line2: line2, 
            isTwoLine: Boolean(line2) 
        };
    }

    // Check if user is a customer (has purchased a diploma product)
    function isUserCustomer() {
        // Check if the is_customer property exists and is true
        return typeof diploma_ajax.is_customer !== 'undefined' && diploma_ajax.is_customer == 1;
    }
    
    // Generate signature HTML based on count and names
    function generateSignatureHTML(count, signature1Name, signature2Name) {
        if (!signature1Name && count == '1') {
            return ''; // Don't show signature section if no name provided for single signature
        }

        let signatureHTML = '<div class="signatures-container">';

        if (count == '1') {
            // Single signature - centered
            signatureHTML += `
                <div class="signature-single">
                    <div class="signature-line-wrapper">
                        <div class="signature-name">${signature1Name}</div>
                        <div class="signature-line"></div>
                        <div class="signature-title">Director</div>
                    </div>
                </div>
            `;
        } else {
            // Two signatures - side by side
            signatureHTML += '<div class="signatures-dual">';

            if (signature1Name) {
                signatureHTML += `
                    <div class="signature-line-wrapper">
                        <div class="signature-name">${signature1Name}</div>
                        <div class="signature-line"></div>
                        <div class="signature-title">Director</div>
                    </div>
                `;
            }

            if (signature2Name) {
                signatureHTML += `
                    <div class="signature-line-wrapper">
                        <div class="signature-name">${signature2Name}</div>
                        <div class="signature-line"></div>
                        <div class="signature-title">Principal</div>
                    </div>
                `;
            }

            signatureHTML += '</div>';
        }

        signatureHTML += '</div>';
        return signatureHTML;
    }

    // Format date for display
    function formatGraduationDate(dateStr) {
        if (!dateStr || dateStr === '[Date of Graduation]') return '[Graduation Date]';
        const date = new Date(dateStr);
        if (isNaN(date.getTime())) return dateStr;
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        return date.toLocaleDateString('en-US', options);
    }

    // Get the display text of the selected major option (e.g. "Paralegal Studies" instead of "paralegal_studies")
    function getMajorDisplayName() {
        const $selected = $('#major option:selected');
        if ($selected.length && $selected.val()) {
            return $selected.text().trim();
        }
        return '';
    }

    // Generate classic style diploma HTML (Style 01 - College/University International)
    function generateClassicDiplomaHTML() {
        const schoolName = currentConfig.school_name || '[Your School Name Here]';
        const studentName = currentConfig.student_name || '[Your Name Here]';
        const graduationDate = currentConfig.graduation_date || '';
        const city = currentConfig.city || '[City]';
        const state = currentConfig.state || '[Region]';
        const degreeType = currentConfig.degree_type || '[Your Degree]';
        const major = getMajorDisplayName() || '[Your Major]';
        const signature1Name = currentConfig.signature1_name || '';
        const signature2Name = currentConfig.signature2_name || '';

        // Get emblem info
        const emblemInfo = getEmblemInfo();

        // Watermark
        const isUserLoggedIn = diploma_ajax.is_user_logged_in && diploma_ajax.is_user_logged_in != '0';
        const isCustomer = diploma_ajax.is_customer && diploma_ajax.is_customer == '1';
        const isAdmin = diploma_ajax.is_admin && diploma_ajax.is_admin == '1';
        const watermarkHTML = (!isUserLoggedIn && !isCustomer && !isAdmin) ?
            '<div class="diploma-preview-watermark">PREVIEW</div>' : '';

        const formattedDate = formatGraduationDate(graduationDate);

        return `<div class="diploma-container classic-template">
            <div class="diploma classic-diploma">
                ${watermarkHTML}

                <!-- School Name Header -->
                <div class="classic-header">
                    <div class="school-name-flat">${schoolName}</div>
                </div>

                <!-- Authority Text -->
                <div class="classic-authority-text">
                    <p><em>By the authority vested in this institution<br>
                    and in recognition of the successful completion of the prescribed course of<br>
                    study, has conferred upon</em></p>
                </div>

                <!-- Student Name -->
                <div class="classic-student-name">
                    <h3>${studentName}</h3>
                </div>

                <!-- Degree Section -->
                <div class="classic-degree-section">
                    <p class="classic-degree-label"><em>the degree</em></p>
                    <h4 class="classic-degree-name">${degreeType}</h4>
                    <p class="classic-major-name">${major}</p>
                </div>

                <!-- Rights & Privileges Text -->
                <div class="classic-rights-text">
                    <p><em>Together with all rights, privileges, and honors customarily pertaining thereto.<br>
                    In testimony whereof, this credential is conferred at and upon this date</em></p>
                </div>

                <!-- City/Region and Graduation Date -->
                <div class="classic-date-location">
                    <div class="classic-city-label">
                        <span class="classic-location-value">${city}, ${state}</span>
                    </div>
                    <div class="classic-date-label">
                        <span class="classic-date-value">${formattedDate}</span>
                    </div>
                </div>

                <!-- Bottom Section: Signatures + Seal -->
                <div class="classic-bottom-section">
                    <!-- Left Signatures -->
                    <div class="classic-signatures-col">
                        <div class="classic-sig-block">
                            <div class="classic-sig-name">${signature1Name || ''}</div>
                            <div class="classic-sig-line"></div>
                            <div class="classic-sig-title"><em>President</em></div>
                        </div>
                        <div class="classic-sig-block">
                            <div class="classic-sig-name">${signature2Name || ''}</div>
                            <div class="classic-sig-line"></div>
                            <div class="classic-sig-title"><em>Chair, Governing Board</em></div>
                        </div>
                    </div>

                    <!-- Center Seal -->
                    <div class="classic-seal-center">
                        ${emblemInfo.html}
                    </div>

                    <!-- Right Signatures (static) -->
                    <div class="classic-signatures-col">
                        <div class="classic-sig-block">
                            <div class="classic-sig-name">Dr. Elaine P. Bodner, Ph.D.</div>
                            <div class="classic-sig-line"></div>
                            <div class="classic-sig-title"><em>Chief Academic Officer</em></div>
                        </div>
                        <div class="classic-sig-block">
                            <div class="classic-sig-name">Margaret L. Carson</div>
                            <div class="classic-sig-line"></div>
                            <div class="classic-sig-title"><em>Registrar</em></div>
                        </div>
                    </div>
                </div>

            </div>
        </div>`;
    }

    // Get the country emblem image URL for Style 2 (from diploma_country CPT feature images)
    function getCountryEmblemSrc() {
        const country = currentConfig.country || '';
        if (diploma_ajax.country_images) {
            // Try direct match (e.g. "Canada", "USA", "UK")
            if (diploma_ajax.country_images[country]) {
                return diploma_ajax.country_images[country];
            }
            // For International, try to match the selected state/province/region display name
            if (country === 'International') {
                const regionText = $('#state_province_region option:selected').text().trim();
                if (regionText && diploma_ajax.country_images[regionText]) {
                    return diploma_ajax.country_images[regionText];
                }
            }
        }
        return '';
    }

    // Get the state featured image URL from diploma_state CPT
    // Matches the selected state/province/region display name against diploma_state post titles
    function getStateEmblemSrc() {
        if (diploma_ajax.state_images) {
            const regionText = $('#state_province_region option:selected').text().trim();
            if (regionText && diploma_ajax.state_images[regionText]) {
                return diploma_ajax.state_images[regionText];
            }
        }
        return '';
    }

    // Generate Style 2 (Modern Elegant) diploma HTML — matches the university international format
    function generateStyle2DiplomaHTML() {
        const schoolName    = currentConfig.school_name   || 'Your School Name Here';
        const studentName   = currentConfig.student_name  || 'Your Name Here';
        const degreeType    = currentConfig.degree_type   || 'Your Degree Here';
        const major         = getMajorDisplayName()        || 'Your Major / Area of Study';
        const city          = currentConfig.city           || 'School City';
        const country       = currentConfig.country        || 'School Country';
        const signature1Name = currentConfig.signature1_name || '';
        const signature2Name = currentConfig.signature2_name || '';
        const graduationDate = currentConfig.graduation_date || '';
        const formattedDate  = formatGraduationDate(graduationDate) || 'Day day of Month, Year';

        // Watermark
        const isUserLoggedIn = diploma_ajax.is_user_logged_in && diploma_ajax.is_user_logged_in != '0';
        const isCustomer     = diploma_ajax.is_customer && diploma_ajax.is_customer == '1';
        const isAdmin        = diploma_ajax.is_admin    && diploma_ajax.is_admin    == '1';
        const watermarkHTML  = (!isUserLoggedIn && !isCustomer && !isAdmin)
            ? '<div class="diploma-preview-watermark">PREVIEW</div>' : '';

        // Top country emblem (circle, from diploma_country CPT feature image)
        const countryImgSrc = getCountryEmblemSrc();
        const topEmblemHTML = countryImgSrc
            ? `<img src="${countryImgSrc}" alt="${country}" class="s2-top-flag-img">`
            : `<div class="s2-top-flag-placeholder"></div>`;

        // Bottom seal (uses the user's chosen emblem / gold seal)
        const emblemInfo = getEmblemInfo();
        const sealHTML   = emblemInfo.html || '';

        // Certificate number (preview placeholder)
        const certYear   = new Date().getFullYear();
        const certNumber = `${certYear}-INT-${Math.floor(10000 + Math.random() * 90000)}`;

        return `<div class="diploma-container style2-template">
            <div class="diploma style2-diploma">
                ${watermarkHTML}

                <!-- Top Country Emblem -->
                <div class="s2-top-emblem">
                    ${topEmblemHTML}
                </div>

                <!-- School Name -->
                <div class="s2-school-header">
                    <h1 class="s2-school-name">${schoolName}</h1>
                    <p class="s2-school-location">${city}, ${country}</p>
                </div>

                <!-- Authority Text -->
                <div class="s2-authority-text">
                    <p>By authority of the Academic Council, this degree<br>has been duly conferred upon</p>
                </div>

                <!-- Student Name -->
                <div class="s2-student-name">
                    <h2>${studentName}</h2>
                </div>

                <!-- Certify Text -->
                <div class="s2-certify-text">
                    <p>This is to formally certify that the academic degree of</p>
                </div>

                <!-- Degree Name -->
                <div class="s2-degree-name">
                    <h3>${degreeType}</h3>
                </div>

                <!-- Body Text -->
                <div class="s2-body-text">
                    <p>upon the successful completion of an<br>approved programme of study in</p>
                </div>

                <!-- Major / Area of Study -->
                <div class="s2-major-name">
                    <h4>${major}</h4>
                </div>

                <!-- Date -->
                <div class="s2-date-text">
                    <p>Dated this ${formattedDate}</p>
                </div>

                <!-- Large Gold Seal -->
                <div class="s2-seal-section">
                    ${sealHTML}
                </div>

                <!-- Four Signatures -->
                <div class="s2-signatures-section">
                    <div class="s2-sig-col">
                        <div class="s2-sig-block">
                            <div class="s2-sig-name">${signature1Name || 'Prof. Arthur L. Davies'}</div>
                            <div class="s2-sig-line"></div>
                            <div class="s2-sig-title">Chair of the Governing Council</div>
                        </div>
                        <div class="s2-sig-block">
                            <div class="s2-sig-name">Lawrence M. Talbot, Esq.</div>
                            <div class="s2-sig-line"></div>
                            <div class="s2-sig-title">Vice-Chancellor</div>
                        </div>
                    </div>
                    <div class="s2-sig-col">
                        <div class="s2-sig-block">
                            <div class="s2-sig-name">${signature2Name || 'Ingrid Hoffmann M.E.'}</div>
                            <div class="s2-sig-line"></div>
                            <div class="s2-sig-title">Secretary of the Academic Council</div>
                        </div>
                        <div class="s2-sig-block">
                            <div class="s2-sig-name">Jack Ananya Chakraborty</div>
                            <div class="s2-sig-line"></div>
                            <div class="s2-sig-title">Registrar of the Institution</div>
                        </div>
                    </div>
                </div>

                <!-- Certificate Number -->
                <div class="s2-cert-number">
                    <p>Certificate No. ${certNumber}</p>
                </div>

            </div>
        </div>`;
    }

    // Generate Style 3 (High School Diploma / Formal) diploma HTML
    function generateStyle3DiplomaHTML() {
        const schoolName     = currentConfig.school_name    || 'Your High School Name';
        const studentName    = currentConfig.student_name   || 'Your Name Here';
        const city           = currentConfig.city           || 'City';
        const state          = currentConfig.state          || 'State';
        const documentType   = currentConfig.document_type  || 'High School';
        const signature1Name = currentConfig.signature1_name || '';
        const signature2Name = currentConfig.signature2_name || '';
        const graduationDate = currentConfig.graduation_date || '';

        // Format date into "Given this X day of Month, in the year YYYY"
        let givenDay   = '___';
        let givenMonth = '___________';
        let givenYear  = '_______';
        if (graduationDate) {
            const d = new Date(graduationDate);
            if (!isNaN(d.getTime())) {
                givenDay   = d.getDate();
                givenMonth = d.toLocaleDateString('en-US', { month: 'long' });
                givenYear  = d.getFullYear();
            }
        }

        // Get emblem info
        const emblemInfo = getEmblemInfo();

        // Watermark
        const isUserLoggedIn = diploma_ajax.is_user_logged_in && diploma_ajax.is_user_logged_in != '0';
        const isCustomer     = diploma_ajax.is_customer && diploma_ajax.is_customer == '1';
        const isAdmin        = diploma_ajax.is_admin    && diploma_ajax.is_admin    == '1';
        const watermarkHTML  = (!isUserLoggedIn && !isCustomer && !isAdmin)
            ? '<div class="diploma-preview-watermark">PREVIEW</div>' : '';

        // Arc header (same logic as generic template)
        const schoolNameSplit = splitSchoolNameForArc(schoolName);
        let fontSize      = 56;
        let line2FontSize = 48;
        if (schoolNameSplit.isTwoLine) {
            const maxLen = Math.max(schoolNameSplit.line1.length, schoolNameSplit.line2.length);
            if (maxLen > 20) {
                fontSize      = Math.max(36, 56 - (maxLen - 20) * 1.2);
                line2FontSize = Math.max(32, fontSize - 8);
            }
        } else if (schoolName.length > 20) {
            fontSize = Math.max(30, 56 - (schoolName.length - 20) * 1.5);
        }

        let arcHeaderHTML;
        if (schoolNameSplit.isTwoLine) {
            arcHeaderHTML = `
                <svg viewBox="0 0 600 160" class="arched-header two-line">
                    <defs>
                        <path id="hs3-curve1" d="M50,120 Q300,20 550,120" />
                        <path id="hs3-curve2" d="M70,140 Q300,60 530,140" />
                    </defs>
                    <text font-family="'UnifrakturMaguntia', cursive" font-size="${fontSize}" fill="#2c1810" text-anchor="middle">
                        <textPath href="#hs3-curve1" startOffset="50%">${schoolNameSplit.line1}</textPath>
                    </text>
                    <text font-family="'UnifrakturMaguntia', cursive" font-size="${line2FontSize}" fill="#2c1810" text-anchor="middle">
                        <textPath href="#hs3-curve2" startOffset="50%">${schoolNameSplit.line2}</textPath>
                    </text>
                </svg>`;
        } else {
            arcHeaderHTML = `
                <svg viewBox="0 0 600 120" class="arched-header">
                    <defs>
                        <path id="hs3-curve" d="M50,100 Q300,10 550,100" />
                    </defs>
                    <text font-family="'UnifrakturMaguntia', cursive" font-size="${fontSize}" fill="#2c1810" text-anchor="middle">
                        <textPath href="#hs3-curve" startOffset="50%">${schoolNameSplit.line1}</textPath>
                    </text>
                </svg>`;
        }

        // Diploma title based on document type
        let diplomaTitleText = 'High School Diploma';
        if (documentType === 'GED') {
            diplomaTitleText = 'General Educational Development';
        } else if (documentType === 'College') {
            diplomaTitleText = 'College Diploma';
        } else if (documentType === 'University') {
            diplomaTitleText = 'University Diploma';
        }

        return `<div class="diploma-container style3-template">
            <div class="diploma style3-diploma">
                ${watermarkHTML}

                <!-- Header: arched school name -->
                <div class="hs3-header">
                    ${arcHeaderHTML}
                </div>

                <!-- This Certifies That -->
                <div class="hs3-certifies-text">
                    <p>This Certifies That</p>
                </div>

                <!-- Student Name -->
                <div class="hs3-student-name">
                    <h3>${studentName}</h3>
                </div>

                <!-- Body text -->
                <div class="hs3-body-text">
                    <p>has satisfactorily completed the course of study prescribed by<br>
                    the Board of Education and has met all requirements for graduation<br>
                    and is hereby awarded this</p>
                </div>

                <!-- Diploma Title -->
                <div class="hs3-diploma-title">
                    <h4>${diplomaTitleText}</h4>
                </div>

                <!-- In witness / Given this -->
                <div class="hs3-witness-text">
                    <p>In witness whereof, we have hereunto set our hands<br>
                    and affixed our signatures in ${city}, ${state}.</p>
                    <p class="hs3-given-line">Given this <span class="hs3-blank">${givenDay}</span> day of <span class="hs3-blank">${givenMonth}</span>, in the year <span class="hs3-blank">${givenYear}</span>.</p>
                </div>

                <!-- Bottom: Signatures + Seal -->
                <div class="hs3-bottom-section">
                    <div class="hs3-signatures-col">
                        <div class="hs3-sig-block">
                            <div class="hs3-sig-name">${signature1Name || 'Dr. Elaine P. Brodeur, Ph.D.'}</div>
                            <div class="hs3-sig-line"></div>
                            <div class="hs3-sig-title">Principal</div>
                        </div>
                        <div class="hs3-sig-block">
                            <div class="hs3-sig-name">${signature2Name || 'Lawrence M. Talbot, Esq.'}</div>
                            <div class="hs3-sig-line"></div>
                            <div class="hs3-sig-title">Assistant Principal</div>
                        </div>
                    </div>

                    <div class="hs3-seal-center">
                        ${emblemInfo.html}
                    </div>

                    <div class="hs3-signatures-col">
                        <div class="hs3-sig-block">
                            <div class="hs3-sig-name">Bernard L. Evans, MSM</div>
                            <div class="hs3-sig-line"></div>
                            <div class="hs3-sig-title">Superintendent</div>
                        </div>
                        <div class="hs3-sig-block">
                            <div class="hs3-sig-name">Ingrid Hoffmann, M.Sc.</div>
                            <div class="hs3-sig-line"></div>
                            <div class="hs3-sig-title">Secretary of the School Board</div>
                        </div>
                    </div>
                </div>

            </div>
        </div>`;
    }

    // Generate Style 4 (Bilingual Province Secondary School Diploma / Decorative)
    function generateStyle4DiplomaHTML() {
        const schoolName     = currentConfig.school_name    || 'Your Province';
        const studentName    = currentConfig.student_name   || 'Your Name Here';
        const city           = currentConfig.city           || 'Province';
        const country        = currentConfig.country        || 'Canada';
        const signature1Name = currentConfig.signature1_name || '';
        const signature2Name = currentConfig.signature2_name || '';
        const graduationDate = currentConfig.graduation_date || '';
        const documentType   = currentConfig.document_type  || 'High School';

        // Format date
        let givenDay       = 'Day';
        let givenMonthYear = 'Month, Year';
        if (graduationDate) {
            const d = new Date(graduationDate);
            if (!isNaN(d.getTime())) {
                givenDay       = d.getDate();
                givenMonthYear = d.toLocaleDateString('en-US', { month: 'long' }) + ', ' + d.getFullYear();
            }
        }

        // Watermark
        const isUserLoggedIn = diploma_ajax.is_user_logged_in && diploma_ajax.is_user_logged_in != '0';
        const isCustomer     = diploma_ajax.is_customer && diploma_ajax.is_customer == '1';
        const isAdmin        = diploma_ajax.is_admin    && diploma_ajax.is_admin    == '1';
        const watermarkHTML  = (!isUserLoggedIn && !isCustomer && !isAdmin)
            ? '<div class="diploma-preview-watermark">PREVIEW</div>' : '';

        // Top emblem from diploma_state featured image based on selected state
        const stateImgSrc = getStateEmblemSrc();
        const fallbackImg = diploma_ajax.plugin_url + 'assets/no-data-available.svg';
        const topEmblemHTML = `<img src="${stateImgSrc || fallbackImg}" alt="${country}" class="s4-top-coat-arms">`;

        // Center seal — build directly from emblem source (single seal)
        const emblemSrc = getEmblemSrc();
        let sealHTML = '';
        if (currentConfig.emblem_value === 'school_preview') {
            sealHTML = `<div class="s4-seal-placeholder"></div>`;
        } else if (emblemSrc) {
            sealHTML = `
                <div class="seal">
                    <div class="seal-circle">
                        <div class="seal-inner">
                            <img src="${emblemSrc}" alt="Seal" class="diploma-emblem"
                                 onerror="this.parentNode.innerHTML='<div class=\\'emblem-placeholder\\'></div>'">
                        </div>
                    </div>
                </div>`;
        }

        // Bilingual diploma title
        let enTitle = `${schoolName} Secondary School Diploma`;
        let frTitle = `Diplôme d'études secondaires de ${schoolName}`;
        if (documentType === 'GED') {
            enTitle = `${schoolName} General Equivalency Diploma`;
            frTitle = `Diplôme d'équivalence général de ${schoolName}`;
        } else if (documentType === 'College') {
            enTitle = `${schoolName} College Diploma`;
            frTitle = `Diplôme collégial de ${schoolName}`;
        } else if (documentType === 'University') {
            enTitle = `${schoolName} University Degree`;
            frTitle = `Diplôme universitaire de ${schoolName}`;
        }

        return `<div class="diploma-container style4-template">
            <div class="diploma style4-diploma">
                ${watermarkHTML}

                <!-- Top Coat of Arms -->
                <div class="s4-top-emblem">
                    ${topEmblemHTML}
                </div>

                <!-- Bilingual Diploma Title -->
                <div class="s4-title">
                    <h1>${enTitle}</h1>
                    <h2>${frTitle}</h2>
                </div>

                <!-- This Diploma is granted to -->
                <div class="s4-granted-text">
                    <p>This Diploma is granted to<br><em>Ce diplôme est décerné à</em></p>
                </div>

                <!-- Student Name -->
                <div class="s4-student-name">
                    <p>${studentName.toUpperCase()}</p>
                </div>

                <!-- a student of / élève de -->
                <div class="s4-student-of">
                    <p>a student of<br><em>élève de</em></p>
                </div>

                <!-- School Name -->
                <div class="s4-school-name">
                    <p>${schoolName.toUpperCase()}</p>
                </div>

                <!-- Bilingual Body Text -->
                <div class="s4-body-text">
                    <p>who has fulfilled the requirements for the Secondary School Diploma<br>
                    in accordance with the provisions of the Ministry of Education and Training<br>
                    <em>qui a rempli les conditions prescrites pour l'obtention du diplôme d'études secondaires<br>
                    en vertu des dispositions du ministère de l'Éducation et de la Formation</em></p>
                </div>

                <!-- Bottom Section: Date | Seal | Signatures -->
                <div class="s4-bottom-section">

                    <!-- Left: Date -->
                    <div class="s4-date-col">
                        <div class="s4-dated-at-row">
                            <span class="s4-label-sm">Dated at<br><em>Délivré à</em></span>
                            <span class="s4-location-value">${city}, ${country}</span>
                        </div>
                        <div class="s4-date-row">
                            <span class="s4-the-word">the<br><em>ce</em></span>
                            <span class="s4-day-value">${givenDay}</span>
                            <span class="s4-day-of-word">day of<br><em>jour de</em></span>
                            <span class="s4-month-year-value">${givenMonthYear}</span>
                        </div>
                    </div>

                    <!-- Center: Seal -->
                    <div class="s4-seal-center">
                        ${sealHTML}
                    </div>

                    <!-- Right: Signatures -->
                    <div class="s4-signatures-col">
                        <div class="s4-sig-block">
                            <div class="s4-sig-name">${signature1Name || 'Richard Collins'}</div>
                            <div class="s4-sig-line"></div>
                            <div class="s4-sig-title">Minister of Education and Training<br><em>Ministre de l'Éducation et de la Formation</em></div>
                        </div>
                        <div class="s4-sig-block">
                            <div class="s4-sig-name">${signature2Name || 'Jane Johnson'}</div>
                            <div class="s4-sig-line"></div>
                            <div class="s4-sig-title">Principal of School<br><em>Le directeur de l'école</em></div>
                        </div>
                    </div>

                </div>

            </div>
        </div>`;
    }

    // Generate diploma HTML with improved arc header
    function generateDiplomaHTML() {
        const currentStyle = currentConfig.diploma_style || 'classic';

        // Use dedicated classic template for Style 01
        if (currentStyle === 'classic') {
            return generateClassicDiplomaHTML();
        }

        // Use Style 2 template for 'modern'
        if (currentStyle === 'modern') {
            return generateStyle2DiplomaHTML();
        }

        // Use Style 3 template for 'formal' (High School Diploma)
        if (currentStyle === 'formal') {
            return generateStyle3DiplomaHTML();
        }

        // Use Style 4 template for 'decorative' (Bilingual Province Diploma)
        if (currentStyle === 'decorative') {
            return generateStyle4DiplomaHTML();
        }

        const schoolName = currentConfig.school_name || '[School Name]';
        const studentName = currentConfig.student_name || '[Student Name]';
        const graduationDate = currentConfig.graduation_date || '[Date of Graduation]';
        const city = currentConfig.city || '[City]';
        const state = currentConfig.state || '[State]';
        const country = currentConfig.country || 'USA';
        const degreeType = currentConfig.degree_type || '';
        const major = getMajorDisplayName() || '';
        const concentration = currentConfig.concentration || '';
        const signatureCount = currentConfig.signature_count || '1';
        const signature1Name = currentConfig.signature1_name || '';
        const signature2Name = currentConfig.signature2_name || '';

        // Get emblem info
        const emblemInfo = getEmblemInfo();

        // Add watermark for non-logged-in users
        // Only show watermark if user is not logged in AND not a customer AND not an admin
        const isUserLoggedIn = diploma_ajax.is_user_logged_in && diploma_ajax.is_user_logged_in != '0';
        const isCustomer = diploma_ajax.is_customer && diploma_ajax.is_customer == '1';
        const isAdmin = diploma_ajax.is_admin && diploma_ajax.is_admin == '1';

        const watermarkHTML = (!isUserLoggedIn && !isCustomer && !isAdmin) ?
            '<div class="diploma-preview-watermark">PREVIEW</div>' : '';

        // Generate header HTML - arc for non-classic styles
        let arcHeaderHTML;

        // Split school name for arc header
        const schoolNameSplit = splitSchoolNameForArc(schoolName);

        // Dynamically adjust font size based on text length
        let fontSize = 56; // Default font size
        let line2FontSize = 48; // Slightly smaller for second line

        if (schoolNameSplit.isTwoLine) {
            // Adjust font sizes for two-line layout
            const maxLineLength = Math.max(schoolNameSplit.line1.length, schoolNameSplit.line2.length);
            if (maxLineLength > 20) {
                fontSize = Math.max(36, 56 - (maxLineLength - 20) * 1.2);
                line2FontSize = Math.max(32, fontSize - 8);
            }
        } else if (schoolName.length > 20) {
            fontSize = Math.max(30, 56 - (schoolName.length - 20) * 1.5);
        }

        // Generate the arc header SVG
        if (schoolNameSplit.isTwoLine) {
            arcHeaderHTML = `
                <svg viewBox="0 0 600 160" class="arched-header two-line">
                    <defs>
                        <path id="curve1" d="M50,120 Q300,20 550,120" />
                        <path id="curve2" d="M70,140 Q300,60 530,140" />
                    </defs>
                    <text font-family="'UnifrakturMaguntia', cursive" font-size="${fontSize}" fill="#2c1810" text-anchor="middle">
                        <textPath href="#curve1" startOffset="50%">
                            ${schoolNameSplit.line1}
                        </textPath>
                    </text>
                    <text font-family="'UnifrakturMaguntia', cursive" font-size="${line2FontSize}" fill="#2c1810" text-anchor="middle">
                        <textPath href="#curve2" startOffset="50%">
                            ${schoolNameSplit.line2}
                        </textPath>
                    </text>
                </svg>
            `;
        } else {
            arcHeaderHTML = `
                <svg viewBox="0 0 600 120" class="arched-header">
                    <defs>
                        <path id="curve" d="M50,100 Q300,10 550,100" />
                    </defs>
                    <text font-family="'UnifrakturMaguntia', cursive" font-size="${fontSize}" fill="#2c1810" text-anchor="middle">
                        <textPath href="#curve" startOffset="50%">
                            ${schoolNameSplit.line1}
                        </textPath>
                    </text>
                </svg>
            `;
        }

        let diplomaPreview = `<div class="diploma-container">
            <div class="diploma">
                ${watermarkHTML}

                <!-- Header with improved arc text -->
                <div class="header">
                    ${arcHeaderHTML}
                </div>

                <!-- Certificate Text -->
                <div class="certificate-text">
                    <h2 class="certifies">This Certifies That</h2>
                </div>

                <!-- Student Name -->
                <div class="student-name">
                    <h3>${studentName}</h3>
                </div>

                <!-- Body Text -->
                <div class="body-text">
                    <p>has satisfactorily completed the Course of Study prescribed by the State Board of Education${major ? ' with a major in <strong>' + major + '</strong>' : ''}${concentration ? ' with a concentration in <strong>' + concentration + '</strong>' : ''} and is hereby awarded this${degreeType ? ' ' + degreeType : ' High School Diploma'}.</p>
                </div>

                <!-- Diploma Title -->
                <div class="diploma-title">
                    <h4>${degreeType || 'Diploma'}</h4>
                </div>

                <!-- Date and Location -->
                <div class="date-location">
                    <p>Given at ${city}, ${state}, ${country}, this ${graduationDate}.</p>
                </div>

                <!-- Location and Seal -->
                <div class="location-seal-section">
                    <div class="location-left">${city}, ${state}</div>
                    ${emblemInfo.html}
                    <div class="location-right">${country}</div>
                </div>

                <!-- Signature Section -->
                ${generateSignatureHTML(signatureCount, signature1Name, signature2Name)}

            </div>
        </div>`;

        return diplomaPreview;
    }
    
    // Get emblem information
    function getEmblemInfo() {
        const diplomaStyles = {
            classic: { emblems: 1 },
            modern: { emblems: 1 },
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
                <div class="seal">
                    <div class="seal-circle">
                        <div class="seal-inner">
                            <img src="${emblemSrc}" alt="Emblem" class="diploma-emblem" 
                                 onerror="this.parentNode.innerHTML='<div class=\\'emblem-placeholder\\'><div>${currentConfig.emblem_value.substring(0, 3)}</div></div>'">
                        </div>
                    </div>
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
        if (!currentConfig.emblem_value) {
            return '';
        }

        // Look up from the emblem URL map passed from PHP
        if (currentConfig.emblem_type === 'generic' && diploma_ajax.emblem_urls) {
            const url = diploma_ajax.emblem_urls[currentConfig.emblem_value];
            if (url) {
                return url;
            }
        }

        if (currentConfig.emblem_type === 'state' && currentConfig.emblem_value) {
            return `${diploma_ajax.plugin_url}assets/emblems/states/${currentConfig.emblem_value}.png`;
        }

        return '';
    }
    
    // Navigate between steps
    function navigateSteps(direction) {
        const currentStep = $('.form-section:visible').data('step');
        let nextStep;
        
        if (direction === 'next') {
            nextStep = currentStep + 1;
            if (nextStep > 5) nextStep = 5;
        } else {
            nextStep = currentStep - 1;
            if (nextStep < 1) nextStep = 1;
        }
        
        // Hide all sections and show the next one
        $('.form-section').hide();
        $(`.form-section[data-step="${nextStep}"]`).show();
        
        // No special wrapper classes needed anymore
        
        // Update navigation buttons
        $('#prev-step').prop('disabled', nextStep === 1);
        $('#next-step').toggle(nextStep < 5);
        $('.form-actions').toggle(nextStep === 5);
        
        // Update progress bar
        updateProgressBar();
    }
    
    // Update progress bar
    function updateProgressBar() {
        const visibleStep = $('.form-section:visible').data('step') || 1;
        const progress = (visibleStep / 5) * 100;
        $('.progress-fill').css('width', `${progress}%`);
        
        // Update step indicators
        $('.step').removeClass('active');
        for (let i = 1; i <= visibleStep; i++) {
            $(`.step[data-step="${i}"]`).addClass('active');
        }
    }
    
    // Zoom preview
    function zoomPreview(delta) {
        const preview = $('#diploma-preview');
        const currentZoom = parseFloat(preview.data('zoom') || 1);
        const newZoom = Math.max(0.5, Math.min(2, currentZoom + delta));

        preview.css('transform', `scale(${newZoom})`);
        preview.data('zoom', newZoom);
        $('#zoom-level').text(`${Math.round(newZoom * 100)}%`);
    }

    // Reset zoom to 100%
    function resetZoom() {
        const preview = $('#diploma-preview');
        preview.css('transform', 'scale(1)');
        preview.data('zoom', 1);
        $('#zoom-level').text('100%');
    }
    
    // Toggle fullscreen
    function toggleFullscreen() {
        const container = $('#diploma-builder-container');
        const body = $('body');
        
        container.toggleClass('fullscreen-mode');
        
        if (container.hasClass('fullscreen-mode')) {
            // Enter fullscreen mode
            body.css('overflow', 'hidden'); // Prevent background scrolling
            $('#toggle-fullscreen').html('✕'); // Change icon to close
            $('#toggle-fullscreen').attr('title', 'Exit Fullscreen');
        } else {
            // Exit fullscreen mode
            body.css('overflow', 'auto'); // Restore scrolling
            $('#toggle-fullscreen').html('⛶'); // Change icon to fullscreen
            $('#toggle-fullscreen').attr('title', 'Toggle Fullscreen');
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
        
        // Temporarily add watermark for non-logged-in users
        // Only add watermark if user is not logged in AND not a customer
        let watermark = null;
        const isUserLoggedIn = diploma_ajax.is_user_logged_in && diploma_ajax.is_user_logged_in != '0';
        const isCustomer = diploma_ajax.is_customer && diploma_ajax.is_customer == '1';
        const isAdmin = typeof diploma_ajax.is_admin !== 'undefined' && diploma_ajax.is_admin == '1';
        
        if (!isUserLoggedIn && !isCustomer && !isAdmin) {
            watermark = $('<div class="diploma-preview-watermark">PREVIEW</div>');
            $('#diploma-preview').append(watermark);
        }

        // Get the actual rendered dimensions of the diploma preview
        const canvasElement = document.getElementById('diploma-preview');
        const rect = canvasElement.getBoundingClientRect();
        const width = rect.width;
        const height = rect.height;
        
        // Calculate scale factor for higher resolution (300 DPI)
        const scaleFactor = 300 / 96; // 300 DPI / 96 DPI (standard screen)
        
        // Use html2canvas to capture the diploma with exact dimensions
        html2canvas(canvasElement, {
            scale: scaleFactor,
            backgroundColor: '#f5f5f5',
            useCORS: true,
            allowTaint: false,
            logging: false,
            width: width,
            height: height,
            scrollX: 0,
            scrollY: 0
        }).then(function(canvas) {
            // Remove temporary watermark
            if (watermark) {
                watermark.remove();
            }
            
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
            // Remove temporary watermark
            if (watermark) {
                watermark.remove();
            }
            
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
        const requiredFields = ['school_name', 'graduation_date', 'city', 'state', 'country'];
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
            paper_color: 'parchment',
            emblem_type: 'generic',
            emblem_value: $('input[name="emblem_value"]').first().val() || '',
            school_name: '',
            student_name: '',
            graduation_date: '',
            city: '',
            state: '',
            country: 'USA',
            state_province_region: '',
            document_type: 'High School',
            diploma_size: '8.5x11',
            degree_type: '',
            major: '',
            concentration: '',
            signature_count: '1',
            signature1_name: '',
            signature2_name: ''
        };

        // Reset form fields
        $('#diploma_style').val('classic');
        $('#paper_color').val('parchment');

        $('#school_name').val('');
        $('#student_name').val('');
        $('#graduation_date').val('');
        $('#city').val('');
        $('#state').val('');
        $('#country').val('USA');
        $('#state_province_region').val('');
        handleCountrySelection('USA'); // Reset region options for USA
        $('#document_type').val('High School');
        updateDiplomaSizeOptions('High School');
        $('#diploma_size').val('8.5x11');
        $('#degree_type').val('');
        $('#major').val('');
        $('#concentration').val('');
        $('#signature_count').val('1');
        $('#signature1_name').val('');
        $('#signature2_name').val('');
        handleSignatureCount('1');
        
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