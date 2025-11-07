<?php
/**
 * Frontend functionality for Diploma Builder
 */

if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

class DiplomaBuilder_Frontend {
    
    public function __construct() {
        add_shortcode('diploma_builder', array($this, 'diploma_builder_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_head', array($this, 'add_meta_tags'));
        add_filter( 'woocommerce_add_to_cart_validation', array($this, 'limit_one_product_in_cart'), 10, 3 );
    }
    
     public function limit_one_product_in_cart( $passed, $product_id, $quantity ) {
            if ( WC()->cart->get_cart_contents_count() > 0 ) {
                // Clear existing cart
                WC()->cart->empty_cart();
                wc_add_notice( 'Only one product can be purchased at a time. We removed the previous item.', 'notice' );
            }
            return $passed;
        }
    
    public function enqueue_scripts() {
        if ($this->should_load_assets()) {
            wp_enqueue_script(
                'html2canvas',
                'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js',
                array(),
                '1.4.1',
                true
            );
        }
    }
    
    private function should_load_assets() {
        global $post;
        return is_a($post, 'WP_Post') && (
            has_shortcode($post->post_content, 'diploma_builder') ||
            has_shortcode($post->post_content, 'diploma_gallery')
        );
    }
    
    public function add_meta_tags() {
        if ($this->should_load_assets()) {
            echo '<meta name="diploma-builder-nonce" content="' . wp_create_nonce('diploma_builder_nonce') . '">' . "\n";
        }
    }
    
    public function diploma_builder_shortcode($atts) {
        $atts = shortcode_atts(array(
            'style' => 'default',
            'show_gallery' => 'false',
            'max_width' => '1400px'
        ), $atts, 'diploma_builder');
        
        // Check if user can create diplomas
        $user_id = get_current_user_id();
        if (!DiplomaBuilder_Database::user_can_create_diploma($user_id)) {
            if ($user_id) {
                return '<div class="diploma-error">' . __('You have reached the maximum number of diplomas allowed.', 'diploma-builder') . '</div>';
            } else {
                return '<div class="diploma-error">' . __('Guest diploma creation is not allowed.', 'diploma-builder') . '</div>';
            }
        }
        
        ob_start();
        $this->render_diploma_builder($atts);
        return ob_get_clean();
    }
    
    private function render_diploma_builder($atts) {
        $diploma_styles = $this->get_diploma_styles();
        $paper_colors = $this->get_paper_colors();
        $generic_emblems = $this->get_generic_emblems();
        $us_states = $this->get_us_states();
        ?>
        <div id="diploma-builder-container" style="max-width: <?php //echo esc_attr($atts['max_width']); ?>" >
            <!-- Main Header -->
            <!-- <div class="diploma-builder-header">
                <div class="header-content">
                    <div class="header-icon">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3zm6.82 6L12 12.72 5.18 9 12 5.28 18.82 9zM17 15.99l-5 2.73-5-2.73v-3.72L12 15l5-2.73v3.72z"/>
                        </svg>
                    </div>
                    <div class="header-text">
                        <h1><?php //_e('High School Diploma Builder', 'diploma-builder'); ?></h1>
                        <p><?php //_e('Create custom high school diplomas for display, gifts, or film props', 'diploma-builder'); ?></p>
                    </div>
                </div>
            </div> -->

            <div class="diploma-builder-wrapper" id="diploma-builder-wrapper">
                <div class="diploma-builder-form" id="diploma-builder-form">
                    <div class="form-header">
                        <div class="form-header-icon">🏆</div>
                        <h2 id="form-step-title"><?php _e('Step 1: Choose Your Basics', 'diploma-builder'); ?></h2>
                    </div>

                    <div class="form-content">
                        <!-- STEP 1: Country, Document Type, Size, and Paper Color -->
                        <div class="form-section step-1-section" data-step="1" style="display: block;">
                            <div class="section-header">
                                <div class="section-title"><?php _e('Step 1: Choose Your Basics', 'diploma-builder'); ?></div>
                                <p class="section-description"><?php _e('Select your country, document type, size, and paper color to get started', 'diploma-builder'); ?></p>
                            </div>

                            <div class="section-content">
                                <!-- Country Selection -->
                                <div class="subsection">
                                    <div class="field-group">
                                        <label for="country"><?php _e('Select Country *', 'diploma-builder'); ?></label>
                                        <select id="country" name="country" class="form-select" required>
                                            <option value=""><?php _e('Choose a Country', 'diploma-builder'); ?></option>
                                            <option value="USA" selected><?php _e('USA', 'diploma-builder'); ?></option>
                                            <option value="UK" disabled><?php _e('UK (Coming Soon)', 'diploma-builder'); ?></option>
                                            <option value="Canada" disabled><?php _e('Canada (Coming Soon)', 'diploma-builder'); ?></option>
                                            <option value="International" disabled><?php _e('International (Coming Soon)', 'diploma-builder'); ?></option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Document Type Selection -->
                                <div class="subsection">
                                    <div class="field-group">
                                        <label for="document_type"><?php _e('Select Document Type *', 'diploma-builder'); ?></label>
                                        <select id="document_type" name="document_type" class="form-select" required>
                                            <option value=""><?php _e('Choose a Document Type', 'diploma-builder'); ?></option>
                                            <option value="GED"><?php _e('GED', 'diploma-builder'); ?></option>
                                            <option value="High School" selected><?php _e('High School', 'diploma-builder'); ?></option>
                                            <option value="College"><?php _e('College', 'diploma-builder'); ?></option>
                                            <option value="University"><?php _e('University', 'diploma-builder'); ?></option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Diploma Size Selection -->
                                <div class="subsection">
                                    <div class="field-group">
                                        <label for="diploma_size"><?php _e('Select Size *', 'diploma-builder'); ?></label>
                                        <select id="diploma_size" name="diploma_size" class="form-select" required>
                                            <option value=""><?php _e('Choose a Size', 'diploma-builder'); ?></option>
                                            <option value="8.5x11" selected><?php _e('8.5" × 11" (Letter)', 'diploma-builder'); ?></option>
                                            <option value="7.5x9.5"><?php _e('7.5" × 9.5"', 'diploma-builder'); ?></option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Paper Color Selection -->
                                <div class="subsection">
                                    <div class="field-group">
                                        <label for="paper_color"><?php _e('Select Paper Color *', 'diploma-builder'); ?></label>
                                        <div class="paper-color-grid">
                                            <?php foreach ($paper_colors as $key => $color): ?>
                                                <label class="paper-color-option" for="paper_color_<?php echo $key; ?>">
                                                    <input type="radio" name="paper_color" value="<?php echo esc_attr($key); ?>" id="paper_color_<?php echo $key; ?>" <?php echo $key === 'white' ? 'checked' : ''; ?>>
                                                    <div class="paper-color-preview" style="background-color: <?php echo esc_attr($color['hex']); ?>"></div>
                                                    <span class="paper-color-name"><?php echo esc_html($color['name']); ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <p class="form-note"><?php _e('Tip: You can change paper color later without losing your entries.', 'diploma-builder'); ?></p>
                                </div>

                                <!-- Continue Button -->
                                <div class="step-actions">
                                    <button type="button" id="step1-continue" class="btn btn-primary btn-large" disabled>
                                        <span class="btn-text"><?php _e('Continue to Choose Style', 'diploma-builder'); ?></span>
                                        <span class="btn-icon">→</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- STEP 2: Diploma Style Selection -->
                        <div class="form-section step-2-section" data-step="2" style="display: none;">
                            <div class="section-header">
                                <div class="section-title"><?php _e('Step 2: Choose Your Diploma Style', 'diploma-builder'); ?></div>
                                <p class="section-description"><?php _e('Select a style that best represents your achievement', 'diploma-builder'); ?></p>
                            </div>

                            <div class="section-content">
                                <!-- Diploma Style Carousel -->
                                <div class="diploma-style-carousel-wrapper">
                                    <button type="button" class="carousel-nav-btn carousel-prev" id="carousel-prev">
                                        <span>‹</span>
                                    </button>

                                    <div class="diploma-style-carousel">
                                        <div class="carousel-track" id="carousel-track">
                                            <?php
                                            $style_index = 0;
                                            foreach ($diploma_styles as $key => $style):
                                            ?>
                                                <div class="carousel-slide <?php echo $style_index === 0 ? 'active' : ''; ?>" data-style="<?php echo esc_attr($key); ?>">
                                                    <label class="diploma-style-card" for="diploma_style_<?php echo $key; ?>">
                                                        <input type="radio" name="diploma_style" value="<?php echo esc_attr($key); ?>" id="diploma_style_<?php echo $key; ?>" <?php echo $key === 'classic' ? 'checked' : ''; ?>>
                                                        <div class="style-card-content">
                                                            <div class="style-preview-image">
                                                                <img src="<?php echo DIPLOMA_BUILDER_URL . 'assets/previews/' . $key . '.png'; ?>" alt="<?php echo esc_attr($style['name']); ?>" loading="lazy">
                                                            </div>
                                                            <div class="style-info">
                                                                <h5><?php echo esc_html($style['name']); ?></h5>
                                                                <p><?php echo esc_html($style['description']); ?></p>
                                                            </div>
                                                        </div>
                                                        <div class="style-checkmark">✓</div>
                                                    </label>
                                                </div>
                                            <?php
                                            $style_index++;
                                            endforeach;
                                            ?>
                                        </div>
                                    </div>

                                    <button type="button" class="carousel-nav-btn carousel-next" id="carousel-next">
                                        <span>›</span>
                                    </button>
                                </div>

                                <!-- Carousel Indicators (dynamically generated by JS) -->
                                <div class="carousel-indicators" id="carousel-indicators"></div>

                                <!-- Step Actions -->
                                <div class="step-actions">
                                    <button type="button" id="step2-back" class="btn btn-secondary">
                                        <span class="btn-icon">←</span>
                                        <span class="btn-text"><?php _e('Back', 'diploma-builder'); ?></span>
                                    </button>
                                    <button type="button" id="step2-continue" class="btn btn-primary btn-large">
                                        <span class="btn-text"><?php _e('Continue to Customize', 'diploma-builder'); ?></span>
                                        <span class="btn-icon">→</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- STEP 3: Customization and Preview -->
                        <div class="form-section step-3-section" data-step="3" style="display: none;">
                            <div class="section-header">
                                <div class="section-title"><?php _e('Step 3: Customize Your Diploma', 'diploma-builder'); ?></div>
                                <p class="section-description"><?php _e('Enter recipient details and personalize your diploma', 'diploma-builder'); ?></p>
                            </div>

                            <div class="section-content">
                                <!-- Degree Type Selection -->
                                <div class="subsection">
                                    <div class="field-group">
                                        <label for="degree_type"><?php _e('Degree Type (Optional)', 'diploma-builder'); ?></label>
                                        <select id="degree_type" name="degree_type" class="form-select">
                                            <option value=""><?php _e('Select degree type (optional)', 'diploma-builder'); ?></option>
                                            <option value="High School Diploma"><?php _e('High School Diploma', 'diploma-builder'); ?></option>
                                            <option value="GED"><?php _e('GED', 'diploma-builder'); ?></option>
                                            <option value="Associate of Arts"><?php _e('Associate of Arts (A.A.)', 'diploma-builder'); ?></option>
                                            <option value="Associate of Science"><?php _e('Associate of Science (A.S.)', 'diploma-builder'); ?></option>
                                            <option value="Associate of Applied Science"><?php _e('Associate of Applied Science (A.A.S.)', 'diploma-builder'); ?></option>
                                            <option value="Bachelor of Arts"><?php _e('Bachelor of Arts (B.A.)', 'diploma-builder'); ?></option>
                                            <option value="Bachelor of Science"><?php _e('Bachelor of Science (B.S.)', 'diploma-builder'); ?></option>
                                            <option value="Bachelor of Fine Arts"><?php _e('Bachelor of Fine Arts (B.F.A.)', 'diploma-builder'); ?></option>
                                            <option value="Bachelor of Business Administration"><?php _e('Bachelor of Business Administration (B.B.A.)', 'diploma-builder'); ?></option>
                                            <option value="Bachelor of Engineering"><?php _e('Bachelor of Engineering (B.Eng.)', 'diploma-builder'); ?></option>
                                            <option value="Master of Arts"><?php _e('Master of Arts (M.A.)', 'diploma-builder'); ?></option>
                                            <option value="Master of Science"><?php _e('Master of Science (M.S.)', 'diploma-builder'); ?></option>
                                            <option value="Master of Business Administration"><?php _e('Master of Business Administration (M.B.A.)', 'diploma-builder'); ?></option>
                                            <option value="Master of Education"><?php _e('Master of Education (M.Ed.)', 'diploma-builder'); ?></option>
                                            <option value="Master of Engineering"><?php _e('Master of Engineering (M.Eng.)', 'diploma-builder'); ?></option>
                                            <option value="Doctor of Philosophy"><?php _e('Doctor of Philosophy (Ph.D.)', 'diploma-builder'); ?></option>
                                            <option value="Doctor of Education"><?php _e('Doctor of Education (Ed.D.)', 'diploma-builder'); ?></option>
                                            <option value="Juris Doctor"><?php _e('Juris Doctor (J.D.)', 'diploma-builder'); ?></option>
                                            <option value="Doctor of Medicine"><?php _e('Doctor of Medicine (M.D.)', 'diploma-builder'); ?></option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Major and Concentration -->
                                <div class="subsection">
                                    <div class="field-group">
                                        <label for="major"><?php _e('Select Major', 'diploma-builder'); ?></label>
                                        <select id="major" name="major" class="form-select">
                                            <option value=""><?php _e('Select major (optional)', 'diploma-builder'); ?></option>
                                            <optgroup label="<?php _e('Business & Economics', 'diploma-builder'); ?>">
                                                <option value="Accounting"><?php _e('Accounting', 'diploma-builder'); ?></option>
                                                <option value="Business Administration"><?php _e('Business Administration', 'diploma-builder'); ?></option>
                                                <option value="Economics"><?php _e('Economics', 'diploma-builder'); ?></option>
                                                <option value="Finance"><?php _e('Finance', 'diploma-builder'); ?></option>
                                                <option value="Marketing"><?php _e('Marketing', 'diploma-builder'); ?></option>
                                                <option value="Management"><?php _e('Management', 'diploma-builder'); ?></option>
                                            </optgroup>
                                            <optgroup label="<?php _e('Science & Technology', 'diploma-builder'); ?>">
                                                <option value="Biology"><?php _e('Biology', 'diploma-builder'); ?></option>
                                                <option value="Chemistry"><?php _e('Chemistry', 'diploma-builder'); ?></option>
                                                <option value="Computer Science"><?php _e('Computer Science', 'diploma-builder'); ?></option>
                                                <option value="Engineering"><?php _e('Engineering', 'diploma-builder'); ?></option>
                                                <option value="Information Technology"><?php _e('Information Technology', 'diploma-builder'); ?></option>
                                                <option value="Mathematics"><?php _e('Mathematics', 'diploma-builder'); ?></option>
                                                <option value="Physics"><?php _e('Physics', 'diploma-builder'); ?></option>
                                            </optgroup>
                                            <optgroup label="<?php _e('Arts & Humanities', 'diploma-builder'); ?>">
                                                <option value="Art"><?php _e('Art', 'diploma-builder'); ?></option>
                                                <option value="English"><?php _e('English', 'diploma-builder'); ?></option>
                                                <option value="History"><?php _e('History', 'diploma-builder'); ?></option>
                                                <option value="Music"><?php _e('Music', 'diploma-builder'); ?></option>
                                                <option value="Philosophy"><?php _e('Philosophy', 'diploma-builder'); ?></option>
                                                <option value="Theatre"><?php _e('Theatre', 'diploma-builder'); ?></option>
                                            </optgroup>
                                            <optgroup label="<?php _e('Social Sciences', 'diploma-builder'); ?>">
                                                <option value="Anthropology"><?php _e('Anthropology', 'diploma-builder'); ?></option>
                                                <option value="Political Science"><?php _e('Political Science', 'diploma-builder'); ?></option>
                                                <option value="Psychology"><?php _e('Psychology', 'diploma-builder'); ?></option>
                                                <option value="Sociology"><?php _e('Sociology', 'diploma-builder'); ?></option>
                                            </optgroup>
                                            <optgroup label="<?php _e('Health & Medicine', 'diploma-builder'); ?>">
                                                <option value="Nursing"><?php _e('Nursing', 'diploma-builder'); ?></option>
                                                <option value="Medicine"><?php _e('Medicine', 'diploma-builder'); ?></option>
                                                <option value="Public Health"><?php _e('Public Health', 'diploma-builder'); ?></option>
                                            </optgroup>
                                            <optgroup label="<?php _e('Education', 'diploma-builder'); ?>">
                                                <option value="Education"><?php _e('Education', 'diploma-builder'); ?></option>
                                                <option value="Elementary Education"><?php _e('Elementary Education', 'diploma-builder'); ?></option>
                                                <option value="Secondary Education"><?php _e('Secondary Education', 'diploma-builder'); ?></option>
                                            </optgroup>
                                            <optgroup label="<?php _e('Other', 'diploma-builder'); ?>">
                                                <option value="Communications"><?php _e('Communications', 'diploma-builder'); ?></option>
                                                <option value="Criminal Justice"><?php _e('Criminal Justice', 'diploma-builder'); ?></option>
                                                <option value="Environmental Science"><?php _e('Environmental Science', 'diploma-builder'); ?></option>
                                                <option value="Law"><?php _e('Law', 'diploma-builder'); ?></option>
                                            </optgroup>
                                        </select>
                                    </div>
                                    <div class="field-group">
                                        <label for="concentration"><?php _e('Concentration (Optional)', 'diploma-builder'); ?></label>
                                        <input type="text" id="concentration" name="concentration" class="form-input" placeholder="<?php _e('e.g., Software Engineering, Digital Marketing', 'diploma-builder'); ?>" maxlength="100">
                                    </div>
                                </div>

                                <!-- School Information -->
                                <div class="subsection" id="school-subsection">
                                    <div class="field-group">
                                        <label for="school_name"><?php _e('High School Name *', 'diploma-builder'); ?></label>
                                        <input type="text" id="school_name" name="school_name" placeholder="<?php _e('e.g., Lincoln High School', 'diploma-builder'); ?>" maxlength="100" required>
                                    </div>
                                    <div class="field-row">
                                        <div class="field-group">
                                            <label for="city"><?php _e('City *', 'diploma-builder'); ?></label>
                                            <input type="text" id="city" name="city" placeholder="<?php _e('e.g., San Francisco', 'diploma-builder'); ?>" maxlength="50" required>
                                        </div>
                                        <div class="field-group">
                                            <label for="state"><?php _e('State *', 'diploma-builder'); ?></label>
                                            <select id="state" name="state" class="form-select" required>
                                                <option value=""><?php _e('Select state', 'diploma-builder'); ?></option>
                                                <?php foreach ($us_states as $code => $name): ?>
                                                    <option value="<?php echo esc_attr($code); ?>"><?php echo esc_html($name); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Graduation Information -->
                                <div class="subsection">
                                    <div class="field-group">
                                        <label for="student_name"><?php _e('Student Name *', 'diploma-builder'); ?></label>
                                        <input type="text" id="student_name" name="student_name" placeholder="<?php _e('Enter student\'s full name', 'diploma-builder'); ?>" maxlength="100" required>
                                    </div>
                                    <div class="field-group">
                                        <label for="graduation_date"><?php _e('Graduation Date *', 'diploma-builder'); ?></label>
                                        <input type="date" id="graduation_date" name="graduation_date" class="form-input" required>
                                    </div>
                                </div>

                                <!-- Signatures & Layout -->
                                <div class="subsection">
                                    <div class="field-group">
                                        <label for="signature_count"><?php _e('Number of Signatures *', 'diploma-builder'); ?></label>
                                        <select id="signature_count" name="signature_count" class="form-select" required>
                                            <option value="1" selected><?php _e('1 Signature', 'diploma-builder'); ?></option>
                                            <option value="2"><?php _e('2 Signatures', 'diploma-builder'); ?></option>
                                        </select>
                                    </div>
                                    <div id="signature-fields-container">
                                        <div class="field-group" id="signature1-field">
                                            <label for="signature1_name"><?php _e('Signature 1 Name *', 'diploma-builder'); ?></label>
                                            <input type="text" id="signature1_name" name="signature1_name" class="form-input" placeholder="<?php _e('e.g., Principal\'s Name', 'diploma-builder'); ?>" maxlength="100" required>
                                        </div>
                                        <div class="field-group" id="signature2-field" style="display: none;">
                                            <label for="signature2_name"><?php _e('Signature 2 Name', 'diploma-builder'); ?></label>
                                            <input type="text" id="signature2_name" name="signature2_name" class="form-input" placeholder="<?php _e('e.g., Dean\'s Name', 'diploma-builder'); ?>" maxlength="100">
                                        </div>
                                    </div>
                                </div>

                                <!-- Emblem Selection -->
                                <div class="subsection">
                                    <div class="emblem-type-tabs">
                                        <button type="button" class="emblem-tab-btn active" data-tab="generic">
                                            <span class="tab-icon">🎓</span>
                                            <?php _e('Generic Emblems', 'diploma-builder'); ?>
                                        </button>
                                        <button type="button" class="emblem-tab-btn" data-tab="state">
                                            <span class="tab-icon">🏛️</span>
                                            <?php _e('State Emblems', 'diploma-builder'); ?>
                                        </button>
                                    </div>

                                    <!-- Generic Emblems -->
                                    <div class="emblem-tab-content active" id="generic-emblems">
                                        <div class="emblem-grid">
                                            <?php foreach ($generic_emblems as $key => $emblem): ?>
                                                <label class="emblem-option" for="emblem_<?php echo $key; ?>">
                                                    <input type="radio" name="emblem_value" value="<?php echo $key; ?>" id="emblem_<?php echo $key; ?>" data-type="generic" <?php echo $key === 'graduation_cap' ? 'checked' : ''; ?>>
                                                    <div class="emblem-preview">
                                                        <img src="<?php echo DIPLOMA_BUILDER_URL . 'assets/emblems/generic/' . $key . '.png'; ?>" alt="<?php echo esc_attr($emblem['name']); ?>" loading="lazy">
                                                    </div>
                                                    <div class="emblem-info">
                                                        <h6><?php echo esc_html($emblem['name']); ?></h6>
                                                    </div>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <!-- State Emblems -->
                                    <div class="emblem-tab-content" id="state-emblems">
                                        <div class="state-selector">
                                            <label for="state-emblem-select"><?php _e('Select State:', 'diploma-builder'); ?></label>
                                            <select id="state-emblem-select" class="form-select">
                                                <option value=""><?php _e('Choose a state...', 'diploma-builder'); ?></option>
                                                <?php foreach ($us_states as $code => $name): ?>
                                                    <option value="<?php echo esc_attr($code); ?>"><?php echo esc_html($name); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="state-emblem-preview" id="state-emblem-preview" style="display: none;">
                                            <div class="state-emblem-image">
                                                <img id="state-emblem-img" src="" alt="" loading="lazy">
                                            </div>
                                            <div class="state-emblem-info">
                                                <h6 id="state-emblem-name"></h6>
                                                <p><?php _e('Official state emblem', 'diploma-builder'); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Purchase Options -->
                                <?php echo $this->get_purchase_option(); ?>

                                <div class="form-actions">
                                    <?php if (current_user_can('manage_options') ): ?>
                                        <button type="button" id="download-diploma" class="btn btn-success">
                                            <span class="btn-icon">📥</span>
                                            <?php _e('Download Diploma', 'diploma-builder'); ?>
                                        </button>
                                    <?php endif; ?>
                                </div>

                                <?php if (!is_user_logged_in() ): ?>
                                <div class="preview-notice">
                                    <p><?php _e('This is a preview only. Purchase a diploma to remove the watermark and unlock full features.', 'diploma-builder'); ?></p>
                                </div>
                                <?php endif; ?>

                                <div class="required-note">
                                    <p><?php _e('* Required fields', 'diploma-builder'); ?></p>
                                </div>

                                <!-- Step Actions -->
                                <div class="step-actions">
                                    <button type="button" id="step3-back" class="btn btn-secondary">
                                        <span class="btn-icon">←</span>
                                        <span class="btn-text"><?php _e('Back to Styles', 'diploma-builder'); ?></span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Review Section (hidden, kept for compatibility) -->
                        <div class="form-section" data-step="5" style="display: none;">
                            <div class="section-header">
                                <h3><?php _e('Step 5 of 5', 'diploma-builder'); ?></h3>
                                <div class="section-title"><?php _e('Review & Download', 'diploma-builder'); ?></div>
                                <p class="section-description"><?php _e('Review your diploma and download', 'diploma-builder'); ?></p>
                            </div>
                            
                            <div class="section-content">
                                <div class="section-icon">✅</div>
                                <h4><?php _e('Review Your Diploma', 'diploma-builder'); ?></h4>
                                <p class="form-note"><?php _e('Please review your diploma in the preview panel. When you\'re satisfied, you can save or download it.', 'diploma-builder'); ?></p>
                                
                                <div class="review-summary">
                                    <div class="summary-item">
                                        <strong><?php _e('Student:', 'diploma-builder'); ?></strong>
                                        <span id="review-student-name">[Student Name]</span>
                                    </div>
                                    <div class="summary-item">
                                        <strong><?php _e('School:', 'diploma-builder'); ?></strong>
                                        <span id="review-school-name">Your High School Name</span>
                                    </div>
                                    <div class="summary-item">
                                        <strong><?php _e('Date:', 'diploma-builder'); ?></strong>
                                        <span id="review-graduation-date">[Graduation Date]</span>
                                    </div>
                                    <div class="summary-item">
                                        <strong><?php _e('Location:', 'diploma-builder'); ?></strong>
                                        <span id="review-location">[City, State]</span>
                                    </div>
                                    <div class="summary-item">
                                        <strong><?php _e('Style:', 'diploma-builder'); ?></strong>
                                        <span id="review-diploma-style">[Diploma Style]</span>
                                    </div>
                                    <div class="summary-item">
                                        <strong><?php _e('Paper:', 'diploma-builder'); ?></strong>
                                        <span id="review-paper-color">[Paper Color]</span>
                                    </div>
                                </div>
                                
                                <!-- Purchase Options -->
                                 <?php  echo $this->get_purchase_option();  ?>

                                <div class="form-actions" style="display: none;">
                                    <?php if (current_user_can('manage_options') ): ?>
                                        <button type="button" id="download-diploma" class="btn btn-success">
                                            <span class="btn-icon">📥</span>
                                            <?php _e('Download Diploma', 'diploma-builder'); ?>
                                        </button>
                                    <?php endif; ?>
                                    <!-- <button type="button" id="save-diploma" class="btn btn-primary">
                                        <span class="btn-icon">💾</span>
                                        <?php // _e('Save Diploma', 'diploma-builder'); ?>
                                    </button> -->
                                </div>                              
                               
                                <?php if (!is_user_logged_in() ): ?>
                                <div class="preview-notice">
                                    <p><?php _e('This is a preview only. Purchase a diploma to remove the watermark and unlock full features.', 'diploma-builder'); ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="diploma-preview-container" id="diploma-preview-container" style="display: none;">
                    <div class="preview-header">
                        <div class="preview-title">
                            <span class="preview-icon">✨</span>
                            <h3><?php _e('Live Preview', 'diploma-builder'); ?></h3>
                        </div>
                        <div class="preview-controls">
                            <button type="button" id="zoom-out" class="btn-icon-small" title="<?php _e('Zoom Out', 'diploma-builder'); ?>">−</button>
                            <span id="zoom-level">100%</span>
                            <button type="button" id="zoom-in" class="btn-icon-small" title="<?php _e('Zoom In', 'diploma-builder'); ?>">+</button>
                            <button type="button" id="toggle-fullscreen" class="btn-icon-small" title="<?php _e('Toggle Fullscreen', 'diploma-builder'); ?>">⛶</button>
                        </div>
                    </div>
                    <div class="diploma-preview">
                        <div id="diploma-canvas" class="diploma-canvas">
                            <!-- Dynamic diploma content will be inserted here -->
                        </div>
                    </div>
                </div>
                
            </div>
            
        </div>
        
        <!-- Loading Overlay -->
        <div id="loading-overlay" class="loading-overlay">
            <div class="loading-content">
                <div class="loading-spinner"></div>
                <div class="loading-text"><?php _e('Processing...', 'diploma-builder'); ?></div>
                <div class="loading-progress">
                    <div class="progress-bar">
                        <div class="progress-fill"></div>
                    </div>
                    <div class="progress-text">0%</div>
                </div>
            </div>
        </div>
        
        <!-- Success Modal -->
        <div id="success-modal" class="modal" style="display: none;">
            <div class="modal-content success-modal">
                <div class="modal-header">
                    <h3><?php _e('Success!', 'diploma-builder'); ?></h3>
                    <button type="button" class="modal-close">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="success-icon">✓</div>
                    <p id="success-message"></p>
                    <div class="success-actions">
                        <button type="button" id="create-another" class="btn btn-secondary"><?php _e('Create Another', 'diploma-builder'); ?></button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private function get_purchase_option() {
        ob_start();
        ?>
        <div class="purchase-options">
            <h5><?php _e('Purchase Options', 'diploma-builder'); ?></h5>
            <div class="purchase-grid">
                <div class="purchase-option">
                    <div class="purchase-header">
                        <div class="purchase-icon">💰</div>
                        <h6><?php _e('Digital Download', 'diploma-builder'); ?></h6>
                    </div>
                    <div class="purchase-price">
                        <span class="price-amount">
                            <?php 
                            $product_id = get_option('diploma_single_product_id', 0);
                            $product    = wc_get_product( $product_id );
                            $checkout_url = wc_get_checkout_url() . '?add-to-cart=' . $product_id . '&quantity=1';
                            if($product) {
                                echo $product->get_price();
                            }
                            ?>
                        </span>
                        <span class="price-description"><?php _e('Instant download', 'diploma-builder'); ?></span>
                    </div>
                    <button type="button" class="btn btn-primary purchase-btn" data-product-id="digital">
                        <span class="btn-icon">📥</span>
                        <a href="<?php echo esc_url($checkout_url); ?>"><?php _e('Buy Now', 'diploma-builder'); ?></a>
                    </button>
                </div>                                       

            </div>
            
            <div class="purchase-note">
                <p><?php _e('All purchases include high-resolution files and are processed through our secure checkout.', 'diploma-builder'); ?></p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    private function get_diploma_styles() {
        return array(
            'classic' => array(
                'name' => __('Classic Traditional', 'diploma-builder'),
                'description' => __('Timeless design with elegant typography', 'diploma-builder'),
                'emblems' => 1,
                'template' => 'classic'
            ),
            'modern' => array(
                'name' => __('Modern Elegant', 'diploma-builder'),
                'description' => __('Contemporary style with clean lines', 'diploma-builder'),
                'emblems' => 1,
                'template' => 'modern'
            ),
            'formal' => array(
                'name' => __('Formal Certificate', 'diploma-builder'),
                'description' => __('Professional and authoritative design', 'diploma-builder'),
                'emblems' => 1,
                'template' => 'formal'
            ),
            'decorative' => array(
                'name' => __('Decorative Border', 'diploma-builder'),
                'description' => __('Ornate borders with artistic flourishes', 'diploma-builder'),
                'emblems' => 2,
                'template' => 'decorative'
            ),
            'minimalist' => array(
                'name' => __('Minimalist Clean', 'diploma-builder'),
                'description' => __('Simple and sophisticated layout', 'diploma-builder'),
                'emblems' => 1,
                'template' => 'minimalist'
            )
        );
    }
    
    private function get_paper_colors() {
        return array(
            'white' => array('name' => __('Classic White', 'diploma-builder'), 'hex' => '#ffffff'),
            'ivory' => array('name' => __('Ivory Cream', 'diploma-builder'), 'hex' => '#f5f5dc'),
            'light_blue' => array('name' => __('Light Blue', 'diploma-builder'), 'hex' => '#e6f3ff'),
            'light_gray' => array('name' => __('Light Gray', 'diploma-builder'), 'hex' => '#f0f0f0')
        );
    }
    
    private function get_generic_emblems() {
        return array(
            'graduation_cap' => array(
                'name' => __('Graduation Cap', 'diploma-builder'),
                'description' => __('Traditional academic cap symbol', 'diploma-builder')
            ),
            'diploma_seal' => array(
                'name' => __('Diploma Seal', 'diploma-builder'),
                'description' => __('Official diploma seal emblem', 'diploma-builder')
            ),
            'academic_torch' => array(
                'name' => __('Academic Torch', 'diploma-builder'),
                'description' => __('Torch of knowledge and learning', 'diploma-builder')
            ),
            'school_crest' => array(
                'name' => __('Preview', 'diploma-builder'),
                'description' => __('Live preview of your diploma', 'diploma-builder')
            ),
            'laurel_wreath' => array(
                'name' => __('Preview', 'diploma-builder'),
                'description' => __('Live preview of your diploma', 'diploma-builder')
            )
        );
    }
    
    private function get_us_states() {
        return array(
            'AL' => __('Alabama', 'diploma-builder'), 'AK' => __('Alaska', 'diploma-builder'), 
            'AZ' => __('Arizona', 'diploma-builder'), 'AR' => __('Arkansas', 'diploma-builder'),
            'CA' => __('California', 'diploma-builder'), 'CO' => __('Colorado', 'diploma-builder'), 
            'CT' => __('Connecticut', 'diploma-builder'), 'DE' => __('Delaware', 'diploma-builder'),
            'FL' => __('Florida', 'diploma-builder'), 'GA' => __('Georgia', 'diploma-builder'), 
            'HI' => __('Hawaii', 'diploma-builder'), 'ID' => __('Idaho', 'diploma-builder'),
            'IL' => __('Illinois', 'diploma-builder'), 'IN' => __('Indiana', 'diploma-builder'), 
            'IA' => __('Iowa', 'diploma-builder'), 'KS' => __('Kansas', 'diploma-builder'),
            'KY' => __('Kentucky', 'diploma-builder'), 'LA' => __('Louisiana', 'diploma-builder'), 
            'ME' => __('Maine', 'diploma-builder'), 'MD' => __('Maryland', 'diploma-builder'),
            'MA' => __('Massachusetts', 'diploma-builder'), 'MI' => __('Michigan', 'diploma-builder'), 
            'MN' => __('Minnesota', 'diploma-builder'), 'MS' => __('Mississippi', 'diploma-builder'),
            'MO' => __('Missouri', 'diploma-builder'), 'MT' => __('Montana', 'diploma-builder'), 
            'NE' => __('Nebraska', 'diploma-builder'), 'NV' => __('Nevada', 'diploma-builder'),
            'NH' => __('New Hampshire', 'diploma-builder'), 'NJ' => __('New Jersey', 'diploma-builder'), 
            'NM' => __('New Mexico', 'diploma-builder'), 'NY' => __('New York', 'diploma-builder'),
            'NC' => __('North Carolina', 'diploma-builder'), 'ND' => __('North Dakota', 'diploma-builder'), 
            'OH' => __('Ohio', 'diploma-builder'), 'OK' => __('Oklahoma', 'diploma-builder'),
            'OR' => __('Oregon', 'diploma-builder'), 'PA' => __('Pennsylvania', 'diploma-builder'), 
            'RI' => __('Rhode Island', 'diploma-builder'), 'SC' => __('South Carolina', 'diploma-builder'),
            'SD' => __('South Dakota', 'diploma-builder'), 'TN' => __('Tennessee', 'diploma-builder'), 
            'TX' => __('Texas', 'diploma-builder'), 'UT' => __('Utah', 'diploma-builder'),
            'VT' => __('Vermont', 'diploma-builder'), 'VA' => __('Virginia', 'diploma-builder'), 
            'WA' => __('Washington', 'diploma-builder'), 'WV' => __('West Virginia', 'diploma-builder'),
            'WI' => __('Wisconsin', 'diploma-builder'), 'WY' => __('Wyoming', 'diploma-builder')
        );
    }
    
}

?>