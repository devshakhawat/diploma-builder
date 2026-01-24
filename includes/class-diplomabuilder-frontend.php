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
                <div class="diploma-builder-container">
                    <div id="diploma-builder-form">
                        <div class="form-content">
                        <!-- STEP 1: Country, Document Type, Size, and Paper Color -->
                        <div class="step-card step-1-card" data-step="1">
                            <div class="step-card-header">
                                <div class="step-header-content">
                                    <h3 class="step-title"><?php _e('Step 1: Select Your Country, Document Type, Size, and Paper Color', 'diploma-builder'); ?></h3>
                                </div>
                                <!-- <div class="step-status">
                                    <span class="status-icon incomplete">○</span>
                                    <span class="status-icon complete" style="display: none;">✓</span>
                                </div> -->
                            </div>

                            <div class="step-card-body">
                                <div class="step-1-two-column">
                                    <!-- Left Column: Country, Document Type, Size -->
                                    <div class="step-1-left">
                                        <!-- Country Selection -->
                                        <div class="subsection">
                                            <div class="field-group">
                                                <label for="country"><?php _e('Select Country *', 'diploma-builder'); ?></label>
                                                <select id="country" name="country" class="form-select" required>
                                                    <option value=""><?php _e('Choose a Country', 'diploma-builder'); ?></option>
                                                    <option value="USA" selected><?php _e('USA', 'diploma-builder'); ?></option>
                                                    <option value="UK"><?php _e('UK', 'diploma-builder'); ?></option>
                                                    <option value="Canada"><?php _e('Canada', 'diploma-builder'); ?></option>
                                                    <option value="International"><?php _e('International', 'diploma-builder'); ?></option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- State / Province / Region Selection -->
                                        <div class="subsection">
                                            <div class="field-group">
                                                <label for="state_province_region"><?php _e('State / Province / Region', 'diploma-builder'); ?></label>
                                                <select id="state_province_region" name="state_province_region" class="form-select">
                                                    <option value=""><?php _e('Select State / Province / Region', 'diploma-builder'); ?></option>

                                                    <!-- USA States -->
                                                    <?php
                                                    $uk_regions = $this->get_uk_regions();
                                                    $canada_provinces = $this->get_canada_provinces();
                                                    $all_countries = $this->get_all_countries();

                                                    foreach ($us_states as $code => $name): ?>
                                                        <option value="<?php echo esc_attr($code); ?>" data-country="USA"><?php echo esc_html($name); ?></option>
                                                    <?php endforeach; ?>

                                                    <!-- UK Regions -->
                                                    <?php foreach ($uk_regions as $code => $name): ?>
                                                        <option value="<?php echo esc_attr($code); ?>" data-country="UK" style="display:none;"><?php echo esc_html($name); ?></option>
                                                    <?php endforeach; ?>

                                                    <!-- Canada Provinces -->
                                                    <?php foreach ($canada_provinces as $code => $name): ?>
                                                        <option value="<?php echo esc_attr($code); ?>" data-country="Canada" style="display:none;"><?php echo esc_html($name); ?></option>
                                                    <?php endforeach; ?>

                                                    <!-- International Countries (A-Z) -->
                                                    <?php foreach ($all_countries as $code => $name): ?>
                                                        <option value="<?php echo esc_attr($code); ?>" data-country="International" style="display:none;"><?php echo esc_html($name); ?></option>
                                                    <?php endforeach; ?>
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
                                    </div>

                                    <!-- Right Column: Paper Color Selection -->
                                    <div class="step-1-right">
                                        <div class="subsection">
                                            <div class="field-group">
                                                <label for="paper_color"><?php _e('Select Paper Color *', 'diploma-builder'); ?></label>
                                                <div class="paper-color-grid">
                                                    <?php
                                                    $color_index = 0;
                                                    foreach ($paper_colors as $key => $color):
                                                    ?>
                                                        <label class="paper-color-option" for="paper_color_<?php echo $key; ?>">
                                                            <input type="radio" name="paper_color" value="<?php echo esc_attr($key); ?>" id="paper_color_<?php echo $key; ?>" <?php echo $color_index === 0 ? 'checked' : ''; ?>>
                                                            <div class="paper-color-preview" style="background-color: <?php echo esc_attr($color['hex']); ?>"></div>
                                                            <div class="paper-color-name"><?php echo esc_html($color['name']); ?></div>
                                                        </label>
                                                    <?php
                                                    $color_index++;
                                                    endforeach;
                                                    ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- STEP 2: Diploma Style Selection -->
                        <div class="step-card step-2-card" data-step="2" style="display: none;">
                            <div class="step-card-header">
                                <div class="step-header-content">
                                    <h3 class="step-title"><?php _e('Step 2: Choose Your Diploma Design', 'diploma-builder'); ?></h3>
                                </div>
                                <!-- <div class="step-status">
                                    <span class="status-icon incomplete">○</span>
                                    <span class="status-icon complete" style="display: none;">✓</span>
                                </div> -->
                            </div>

                            <div class="step-card-body">
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
                                            $style_number = $style_index + 1;
                                            ?>
                                                <div class="carousel-slide <?php echo $style_index === 0 ? 'active' : ''; ?>" data-style="<?php echo esc_attr($key); ?>">
                                                    <label class="diploma-style-card" for="diploma_style_<?php echo $key; ?>">
                                                        <input type="radio" name="diploma_style" value="<?php echo esc_attr($key); ?>" id="diploma_style_<?php echo $key; ?>" <?php echo $style_index === 0 ? 'checked' : ''; ?>>
                                                        <div class="style-card-content" data-style-label="<?php echo sprintf(__('Style %d', 'diploma-builder'), $style_number); ?>">
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
                            </div>
                        </div>

                        <!-- STEP 3: Customization and Preview -->
                        <div class="step-card step-3-card" data-step="3" style="display: none;">
                            <div class="step-card-header">
                                <div class="step-header-content">
                                    <h3 class="step-title"><?php _e('Step 3: Customize Your Diploma', 'diploma-builder'); ?></h3>
                                </div>
                                <!-- <div class="step-status">
                                    <span class="status-icon incomplete">○</span>
                                    <span class="status-icon complete" style="display: none;">✓</span>
                                </div> -->
                            </div>

                            <div class="step-card-body">
                                <div class="step-3-two-column">
                                    <!-- Left Column: Form Fields -->
                                    <div class="step-3-left">
                                <!-- Degree Type Selection -->
                                    <div class="field-group">
                                        <label for="degree_type"><?php _e('Degree Type (Optional)', 'diploma-builder'); ?></label>
                                        <select id="degree_type" name="degree_type" class="form-select">
                                            <option value=""><?php _e('Select degree type (optional)', 'diploma-builder'); ?></option>
                                            <option value="High School & Secondary Education"><?php _e('High School & Secondary Education', 'diploma-builder'); ?></option>
                                            <option value="Associate Degrees"><?php _e('Associate Degrees', 'diploma-builder'); ?></option>
                                            <option value="Bachelor Degrees"><?php _e('Bachelor Degrees', 'diploma-builder'); ?></option>
                                            <option value="Master Degrees"><?php _e('Master Degrees', 'diploma-builder'); ?></option>
                                            <option value="Certificates"><?php _e('Certificates', 'diploma-builder'); ?></option>
                                        </select>
                                    </div>

                                <!-- Major and Concentration -->
                                    <div class="field-group">
                                        <label for="major"><?php _e('Select Major', 'diploma-builder'); ?></label>
                                        <select id="major" name="major" class="form-select">
                                            <option value=""><?php _e('Select major (optional)', 'diploma-builder'); ?></option>
                                            <optgroup label="<?php _e('High School Diplomas', 'diploma-builder'); ?>" data-degree-type="High School & Secondary Education">
                                                <option value="High School Diploma — Standard Program"><?php _e('High School Diploma — Standard Program', 'diploma-builder'); ?></option>
                                                <option value="High School Diploma — College Preparatory Program"><?php _e('High School Diploma — College Preparatory Program', 'diploma-builder'); ?></option>
                                                <option value="High School Diploma — Honors Program"><?php _e('High School Diploma — Honors Program', 'diploma-builder'); ?></option>
                                            </optgroup>
                                            <optgroup label="<?php _e('High School Equivalency (HSE)', 'diploma-builder'); ?>" data-degree-type="High School & Secondary Education">
                                                <option value="GED"><?php _e('General Educational Development (GED®)', 'diploma-builder'); ?></option>
                                                <option value="HiSET"><?php _e('High School Equivalency Test (HiSET®)', 'diploma-builder'); ?></option>
                                                <option value="TASC"><?php _e('Test Assessing Secondary Completion (TASC®)', 'diploma-builder'); ?></option>
                                            </optgroup>

                                            <optgroup label="<?php _e('Associate of Arts (AA)', 'diploma-builder'); ?>" data-degree-type="Associate Degrees">
                                                <option value="liberal_arts"><?php _e('Liberal Arts and Sciences', 'diploma-builder'); ?></option>
                                                <option value="humanities"><?php _e('Humanities', 'diploma-builder'); ?></option>
                                                <option value="social_sciences"><?php _e('Social Sciences', 'diploma-builder'); ?></option>
                                                <option value="fine_arts"><?php _e('Fine Arts', 'diploma-builder'); ?></option>
                                            </optgroup> 
                                            
                                            <optgroup label="<?php _e('Associate of Science (AS)', 'diploma-builder'); ?>" data-degree-type="Associate Degrees">
                                                <option value="biology"><?php _e('Biology', 'diploma-builder'); ?></option>
                                                <option value="chemistry"><?php _e('Chemistry', 'diploma-builder'); ?></option>
                                                <option value="physics"><?php _e('Physics', 'diploma-builder'); ?></option>
                                                <option value="mathematics"><?php _e('Mathematics', 'diploma-builder'); ?></option>
                                                <option value="computer_science"><?php _e('Computer Science', 'diploma-builder'); ?></option>
                                            </optgroup>

                                            <optgroup label="<?php _e('Associate of Applied Science (AAS)', 'diploma-builder'); ?>" data-degree-type="Associate Degrees">
                                                <option value="criminal_justice"><?php _e('Criminal Justice', 'diploma-builder'); ?></option>
                                                <option value="paralegal_studies"><?php _e('Paralegal Studies', 'diploma-builder'); ?></option>
                                                <option value="culinary_arts"><?php _e('Culinary Arts', 'diploma-builder'); ?></option>
                                                <option value="automotive_technology"><?php _e('Automotive Technology', 'diploma-builder'); ?></option>
                                                <option value="skilled_trades"><?php _e('Skilled Trades', 'diploma-builder'); ?></option>
                                            </optgroup>

                                            <optgroup label="<?php _e('Associate of Liberal Arts & Sciences', 'diploma-builder'); ?>" data-degree-type="Associate Degrees">
                                                <option value="general_electives"><?php _e('General Electives', 'diploma-builder'); ?></option>
                                                <option value="humanities_core"><?php _e('Humanities Core', 'diploma-builder'); ?></option>
                                                <option value="natural_sciences_core"><?php _e('Natural Sciences Core', 'diploma-builder'); ?></option>
                                                <option value="interdisciplinary_studies"><?php _e('Interdisciplinary Studies', 'diploma-builder'); ?></option>
                                                <option value="quantitative_reasoning"><?php _e('Quantitative Reasoning', 'diploma-builder'); ?></option>
                                                <option value="social_sciences_core"><?php _e('Social Sciences Core', 'diploma-builder'); ?></option>
                                            </optgroup>

                                            <optgroup label="<?php _e('Bachelor of Arts (BA)', 'diploma-builder'); ?>" data-degree-type="Bachelor Degrees">
                                                <option value="english"><?php _e('English', 'diploma-builder'); ?></option>
                                                <option value="history"><?php _e('History', 'diploma-builder'); ?></option>
                                                <option value="philosophy"><?php _e('Philosophy', 'diploma-builder'); ?></option>
                                                <option value="fine_arts"><?php _e('Fine Arts', 'diploma-builder'); ?></option>
                                                <option value="languages_linguistics"><?php _e('Languages and Linguistics', 'diploma-builder'); ?></option>
                                                <option value="communications"><?php _e('Communications', 'diploma-builder'); ?></option>
                                            </optgroup>

                                            <optgroup label="<?php _e('Bachelor of Science (BS)', 'diploma-builder'); ?>" data-degree-type="Bachelor Degrees">
                                                <option value="psychology"><?php _e('Psychology', 'diploma-builder'); ?></option>
                                                <option value="sociology"><?php _e('Sociology', 'diploma-builder'); ?></option>
                                                <option value="political_science"><?php _e('Political Science', 'diploma-builder'); ?></option>
                                                <option value="anthropology"><?php _e('Anthropology', 'diploma-builder'); ?></option>
                                                <option value="economics"><?php _e('Economics', 'diploma-builder'); ?></option>
                                                <option value="biology"><?php _e('Biology', 'diploma-builder'); ?></option>
                                                <option value="chemistry"><?php _e('Chemistry', 'diploma-builder'); ?></option>
                                                <option value="physics"><?php _e('Physics', 'diploma-builder'); ?></option>
                                                <option value="mathematics"><?php _e('Mathematics', 'diploma-builder'); ?></option>
                                                <option value="computer_science"><?php _e('Computer Science', 'diploma-builder'); ?></option>
                                                <option value="engineering"><?php _e('Engineering', 'diploma-builder'); ?></option>
                                                <option value="environmental_science"><?php _e('Environmental Science', 'diploma-builder'); ?></option>
                                            </optgroup>

                                            <optgroup label="<?php _e('Bachelor of Business Administration (BBA)', 'diploma-builder'); ?>" data-degree-type="Bachelor Degrees">
                                                <option value="management"><?php _e('Management', 'diploma-builder'); ?></option>
                                                <option value="finance"><?php _e('Finance', 'diploma-builder'); ?></option>
                                                <option value="marketing"><?php _e('Marketing', 'diploma-builder'); ?></option>
                                                <option value="accounting"><?php _e('Accounting', 'diploma-builder'); ?></option>
                                                <option value="entrepreneurship"><?php _e('Entrepreneurship', 'diploma-builder'); ?></option>
                                                <option value="information_systems"><?php _e('Information Systems', 'diploma-builder'); ?></option>
                                            </optgroup>

                                            <optgroup label="<?php _e('Bachelor of Education (B.Ed.)', 'diploma-builder'); ?>" data-degree-type="Bachelor Degrees">
                                                <option value="elementary_education"><?php _e('Elementary Education', 'diploma-builder'); ?></option>
                                                <option value="secondary_education"><?php _e('Secondary Education', 'diploma-builder'); ?></option>
                                                <option value="special_education"><?php _e('Special Education', 'diploma-builder'); ?></option>
                                            </optgroup>
                                            
                                            <optgroup label="<?php _e('Master of Arts (MA)', 'diploma-builder'); ?>" data-degree-type="Master Degrees">
                                                <option value="english"><?php _e('English', 'diploma-builder'); ?></option>
                                                <option value="history"><?php _e('History', 'diploma-builder'); ?></option>
                                                <option value="philosophy"><?php _e('Philosophy', 'diploma-builder'); ?></option>
                                                <option value="music"><?php _e('Music', 'diploma-builder'); ?></option>
                                                <option value="film"><?php _e('Film', 'diploma-builder'); ?></option>
                                            </optgroup>

                                            <optgroup label="<?php _e('Master of Science (MS)', 'diploma-builder'); ?>" data-degree-type="Master Degrees">
                                                <option value="computer_science"><?php _e('Computer Science', 'diploma-builder'); ?></option>
                                                <option value="data_science"><?php _e('Data Science', 'diploma-builder'); ?></option>
                                                <option value="engineering"><?php _e('Engineering', 'diploma-builder'); ?></option>
                                                <option value="environmental_science"><?php _e('Environmental Science', 'diploma-builder'); ?></option>
                                                <option value="physics"><?php _e('Physics', 'diploma-builder'); ?></option>
                                                <option value="mathematics"><?php _e('Mathematics', 'diploma-builder'); ?></option>
                                            </optgroup>

                                            <optgroup label="<?php _e('Master of Business Administration (MBA)', 'diploma-builder'); ?>" data-degree-type="Master Degrees">
                                                <option value="accounting"><?php _e('Accounting', 'diploma-builder'); ?></option>
                                                <option value="finance"><?php _e('Finance', 'diploma-builder'); ?></option>
                                                <option value="management"><?php _e('Management', 'diploma-builder'); ?></option>
                                                <option value="marketing"><?php _e('Marketing', 'diploma-builder'); ?></option>
                                                <option value="information_systems"><?php _e('Information Systems', 'diploma-builder'); ?></option>
                                                <option value="supply_chain_management"><?php _e('Supply Chain Management', 'diploma-builder'); ?></option>
                                            </optgroup>

                                            <optgroup label="<?php _e('Master of Education (M.Ed.)', 'diploma-builder'); ?>" data-degree-type="Master Degrees">
                                                <option value="curriculum_and_instruction"><?php _e('Curriculum and Instruction', 'diploma-builder'); ?></option>
                                                <option value="higher_education"><?php _e('Higher Education', 'diploma-builder'); ?></option>
                                                <option value="educational_leadership"><?php _e('Educational Leadership', 'diploma-builder'); ?></option>
                                                <option value="arts_education"><?php _e('Arts Education', 'diploma-builder'); ?></option>
                                            </optgroup>

                                            <optgroup label="<?php _e('Master of Public Administration (MPA)', 'diploma-builder'); ?>" data-degree-type="Master Degrees">
                                                <option value="public_policy"><?php _e('Public Policy', 'diploma-builder'); ?></option>
                                                <option value="government_administration"><?php _e('Government Administration', 'diploma-builder'); ?></option>
                                                <option value="public_affairs"><?php _e('Public Affairs', 'diploma-builder'); ?></option>
                                            </optgroup>
                                            
                                            <optgroup label="<?php _e('Business Certificates', 'diploma-builder'); ?>" data-degree-type="Certificates">
                                                <option value="project_management"><?php _e('Project Management', 'diploma-builder'); ?></option>
                                                <option value="human_resources"><?php _e('Human Resources', 'diploma-builder'); ?></option>
                                                <option value="leadership"><?php _e('Leadership', 'diploma-builder'); ?></option>
                                                <option value="business_and_management"><?php _e('Business and Management', 'diploma-builder'); ?></option>
                                            </optgroup>

                                            <optgroup label="<?php _e('Information Technology Certificates', 'diploma-builder'); ?>" data-degree-type="Certificates">
                                                <option value="cybersecurity"><?php _e('Cybersecurity', 'diploma-builder'); ?></option>
                                                <option value="networking"><?php _e('Networking', 'diploma-builder'); ?></option>
                                                <option value="cloud_computing"><?php _e('Cloud Computing', 'diploma-builder'); ?></option>
                                                <option value="web_development"><?php _e('Web Development', 'diploma-builder'); ?></option>
                                                <option value="data_analytics"><?php _e('Data Analytics', 'diploma-builder'); ?></option>
                                            </optgroup>

                                            <optgroup label="<?php _e('Design & Creative Certificates', 'diploma-builder'); ?>" data-degree-type="Certificates">
                                                <option value="graphic_design"><?php _e('Graphic Design', 'diploma-builder'); ?></option>
                                                <option value="digital_media"><?php _e('Digital Media', 'diploma-builder'); ?></option>
                                                <option value="ux_ui_design"><?php _e('UX and UI Design', 'diploma-builder'); ?></option>
                                            </optgroup>

                                            <optgroup label="<?php _e('Post‑Graduate / Professional Certificates', 'diploma-builder'); ?>" data-degree-type="Certificates">
                                                <option value="applied_statistics"><?php _e('Applied Statistics', 'diploma-builder'); ?></option>
                                                <option value="public_administration"><?php _e('Public Administration', 'diploma-builder'); ?></option>
                                                <option value="environmental_policy"><?php _e('Environmental Policy', 'diploma-builder'); ?></option>
                                            </optgroup>
                                            
                                            
                                        </select>
                                    </div>
                                    <div class="field-group">
                                        <label for="concentration"><?php _e('Concentration (Optional)', 'diploma-builder'); ?></label>
                                        <input type="text" id="concentration" name="concentration" class="form-input" placeholder="<?php _e('e.g., Software Engineering, Digital Marketing', 'diploma-builder'); ?>" value="<?php _e('Software Engineering', 'diploma-builder'); ?>" maxlength="100">
                                    </div>

                                <!-- School Information -->
                                    <div class="field-group">
                                        <label for="school_name"><?php _e('High School Name *', 'diploma-builder'); ?></label>
                                        <input type="text" id="school_name" name="school_name" placeholder="<?php _e('e.g., Lincoln High School', 'diploma-builder'); ?>" value="<?php _e('Lincoln High School', 'diploma-builder'); ?>" maxlength="100" required>
                                    </div>
                                    <div class="field-row">
                                        <div class="field-group">
                                            <label for="city"><?php _e('City *', 'diploma-builder'); ?></label>
                                            <input type="text" id="city" name="city" placeholder="<?php _e('e.g., San Francisco', 'diploma-builder'); ?>" value="<?php _e('San Francisco', 'diploma-builder'); ?>" maxlength="50" required>
                                        </div>
                                        <div class="field-group">
                                            <label for="state"><?php _e('State *', 'diploma-builder'); ?></label>
                                            <select id="state" name="state" class="form-select" required>
                                                <option value=""><?php _e('Select state', 'diploma-builder'); ?></option>
                                                <?php
                                                $state_index = 0;
                                                foreach ($us_states as $code => $name):
                                                ?>
                                                    <option value="<?php echo esc_attr($code); ?>" <?php echo ($code === 'CA') ? 'selected' : ''; ?>><?php echo esc_html($name); ?></option>
                                                <?php
                                                $state_index++;
                                                endforeach;
                                                ?>
                                            </select>
                                        </div>
                                    </div>

                                <!-- Graduation Information -->
                                    <div class="field-group">
                                        <label for="student_name"><?php _e('Student Name *', 'diploma-builder'); ?></label>
                                        <input type="text" id="student_name" name="student_name" placeholder="<?php _e('Enter student\'s full name', 'diploma-builder'); ?>" value="<?php _e('John Michael Smith', 'diploma-builder'); ?>" maxlength="100" required>
                                    </div>
                                    <div class="field-group">
                                        <label for="graduation_date"><?php _e('Graduation Date *', 'diploma-builder'); ?></label>
                                        <input type="date" id="graduation_date" name="graduation_date" class="form-input" value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>

                                <!-- Signatures & Layout -->
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
                                            <input type="text" id="signature1_name" name="signature1_name" class="form-input" placeholder="<?php _e('e.g., Principal\'s Name', 'diploma-builder'); ?>" value="<?php _e('Dr. Robert Johnson', 'diploma-builder'); ?>" maxlength="100" required>
                                        </div>
                                        <div class="field-group" id="signature2-field" style="display: none;">
                                            <label for="signature2_name"><?php _e('Signature 2 Name', 'diploma-builder'); ?></label>
                                            <input type="text" id="signature2_name" name="signature2_name" class="form-input" placeholder="<?php _e('e.g., Dean\'s Name', 'diploma-builder'); ?>" value="<?php _e('Sarah Williams', 'diploma-builder'); ?>" maxlength="100">
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

                                    </div>
                                    <!-- End Left Column -->

                                    <!-- Right Column: Live Preview -->
                                    <div class="step-3-right">
                                        <div class="diploma-preview-wrapper" id="diploma-preview-wrapper" style="display: none;">
                                            <div class="diploma-preview-container-inner" id="diploma-preview-container-inner">
                                                <div class="preview-header">
                                                    <div class="preview-title">
                                                        <span class="preview-icon">✨</span>
                                                        <h3><?php _e('Live Preview', 'diploma-builder'); ?></h3>
                                                    </div>
                                                    <div class="preview-controls">
                                                        <button type="button" id="zoom-out" class="btn-icon-small" title="<?php _e('Zoom Out', 'diploma-builder'); ?>">−</button>
                                                        <span id="zoom-level">100%</span>
                                                        <button type="button" id="zoom-in" class="btn-icon-small" title="<?php _e('Zoom In', 'diploma-builder'); ?>">+</button>
                                                        <button type="button" id="reset-zoom" class="btn-icon-small" title="<?php _e('Reset Zoom', 'diploma-builder'); ?>">⟲</button>
                                                    </div>
                                                </div>

                                                <div class="preview-body">
                                                    <div class="diploma-preview" id="diploma-preview"></div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Emblem Selection Carousel -->
                                        <div class="emblem-selection-section">
                                            <h4 class="emblem-section-title"><?php _e('Choose Your Emblem', 'diploma-builder'); ?></h4>

                                            <div class="emblem-type-tabs" style="display: none;">
                                                <button type="button" class="emblem-tab-btn active" data-tab="generic">
                                                    <span class="tab-icon">🎓</span>
                                                    <?php _e('Generic', 'diploma-builder'); ?>
                                                </button>
                                                <!-- State emblem tab hidden for now -->
                                                <!-- <button type="button" class="emblem-tab-btn" data-tab="state">
                                                    <span class="tab-icon">🏛️</span>
                                                    <?php //_e('State', 'diploma-builder'); ?>
                                                </button> -->
                                            </div>

                                            <!-- Generic Emblems Carousel -->
                                            <div class="emblem-tab-content active" id="generic-emblems">
                                                <div class="emblem-carousel-wrapper">
                                                    <button type="button" class="emblem-carousel-nav emblem-prev" id="emblem-prev">
                                                        <span>‹</span>
                                                    </button>

                                                    <div class="emblem-carousel">
                                                        <div class="emblem-carousel-track" id="emblem-carousel-track">
                                                            <?php
                                                            $emblem_index = 0;
                                                            foreach ($generic_emblems as $key => $emblem):
                                                            ?>
                                                                <div class="emblem-carousel-slide <?php echo $emblem_index === 0 ? 'active' : ''; ?>" data-emblem="<?php echo esc_attr($key); ?>">
                                                                    <label class="emblem-carousel-option" for="emblem_<?php echo $key; ?>">
                                                                        <input type="radio" name="emblem_value" value="<?php echo $key; ?>" id="emblem_<?php echo $key; ?>" data-type="generic" <?php echo $emblem_index === 0 ? 'checked' : ''; ?>>
                                                                        <div class="emblem-carousel-preview">
                                                                            <img src="<?php echo DIPLOMA_BUILDER_URL . 'assets/emblems/generic/' . $key . '.png'; ?>" alt="<?php echo esc_attr($emblem['name']); ?>" loading="lazy">
                                                                        </div>
                                                                        <div class="emblem-carousel-info">
                                                                            <h6><?php echo esc_html($emblem['name']); ?></h6>
                                                                        </div>
                                                                    </label>
                                                                </div>
                                                            <?php
                                                            $emblem_index++;
                                                            endforeach;
                                                            ?>
                                                        </div>
                                                    </div>

                                                    <button type="button" class="emblem-carousel-nav emblem-next" id="emblem-next">
                                                        <span>›</span>
                                                    </button>
                                                </div>

                                                <!-- Carousel Indicators -->
                                                <div class="emblem-carousel-indicators" id="emblem-carousel-indicators"></div>
                                            </div>

                                            <!-- State Emblems - Hidden for now -->
                                            <div class="emblem-tab-content" id="state-emblems" style="display: none;">
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
                                    </div>
                                    <!-- End Right Column -->
                                </div>
                            </div>
                        </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hidden sections for compatibility -->
            <div style="display: none;">
                <div class="form-section" data-step="5">
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

    private function get_uk_regions() {
        return array(
            'England' => __('England', 'diploma-builder'),
            'Scotland' => __('Scotland', 'diploma-builder'),
            'Wales' => __('Wales', 'diploma-builder'),
            'Northern Ireland' => __('Northern Ireland', 'diploma-builder')
        );
    }

    private function get_canada_provinces() {
        return array(
            'AB' => __('Alberta', 'diploma-builder'),
            'BC' => __('British Columbia', 'diploma-builder'),
            'MB' => __('Manitoba', 'diploma-builder'),
            'NB' => __('New Brunswick', 'diploma-builder'),
            'NL' => __('Newfoundland and Labrador', 'diploma-builder'),
            'NS' => __('Nova Scotia', 'diploma-builder'),
            'ON' => __('Ontario', 'diploma-builder'),
            'PE' => __('Prince Edward Island', 'diploma-builder'),
            'QC' => __('Quebec', 'diploma-builder'),
            'SK' => __('Saskatchewan', 'diploma-builder'),
            'NT' => __('Northwest Territories', 'diploma-builder'),
            'NU' => __('Nunavut', 'diploma-builder'),
            'YT' => __('Yukon', 'diploma-builder')
        );
    }

    private function get_all_countries() {
        return array(
            'AF' => __('Afghanistan', 'diploma-builder'), 'AL' => __('Albania', 'diploma-builder'),
            'DZ' => __('Algeria', 'diploma-builder'), 'AD' => __('Andorra', 'diploma-builder'),
            'AO' => __('Angola', 'diploma-builder'), 'AG' => __('Antigua and Barbuda', 'diploma-builder'),
            'AR' => __('Argentina', 'diploma-builder'), 'AM' => __('Armenia', 'diploma-builder'),
            'AU' => __('Australia', 'diploma-builder'), 'AT' => __('Austria', 'diploma-builder'),
            'AZ' => __('Azerbaijan', 'diploma-builder'), 'BS' => __('Bahamas', 'diploma-builder'),
            'BH' => __('Bahrain', 'diploma-builder'), 'BD' => __('Bangladesh', 'diploma-builder'),
            'BB' => __('Barbados', 'diploma-builder'), 'BY' => __('Belarus', 'diploma-builder'),
            'BE' => __('Belgium', 'diploma-builder'), 'BZ' => __('Belize', 'diploma-builder'),
            'BJ' => __('Benin', 'diploma-builder'), 'BT' => __('Bhutan', 'diploma-builder'),
            'BO' => __('Bolivia', 'diploma-builder'), 'BA' => __('Bosnia and Herzegovina', 'diploma-builder'),
            'BW' => __('Botswana', 'diploma-builder'), 'BR' => __('Brazil', 'diploma-builder'),
            'BN' => __('Brunei', 'diploma-builder'), 'BG' => __('Bulgaria', 'diploma-builder'),
            'BF' => __('Burkina Faso', 'diploma-builder'), 'BI' => __('Burundi', 'diploma-builder'),
            'KH' => __('Cambodia', 'diploma-builder'), 'CM' => __('Cameroon', 'diploma-builder'),
            'CA' => __('Canada', 'diploma-builder'), 'CV' => __('Cape Verde', 'diploma-builder'),
            'CF' => __('Central African Republic', 'diploma-builder'), 'TD' => __('Chad', 'diploma-builder'),
            'CL' => __('Chile', 'diploma-builder'), 'CN' => __('China', 'diploma-builder'),
            'CO' => __('Colombia', 'diploma-builder'), 'KM' => __('Comoros', 'diploma-builder'),
            'CG' => __('Congo', 'diploma-builder'), 'CR' => __('Costa Rica', 'diploma-builder'),
            'HR' => __('Croatia', 'diploma-builder'), 'CU' => __('Cuba', 'diploma-builder'),
            'CY' => __('Cyprus', 'diploma-builder'), 'CZ' => __('Czech Republic', 'diploma-builder'),
            'DK' => __('Denmark', 'diploma-builder'), 'DJ' => __('Djibouti', 'diploma-builder'),
            'DM' => __('Dominica', 'diploma-builder'), 'DO' => __('Dominican Republic', 'diploma-builder'),
            'EC' => __('Ecuador', 'diploma-builder'), 'EG' => __('Egypt', 'diploma-builder'),
            'SV' => __('El Salvador', 'diploma-builder'), 'GQ' => __('Equatorial Guinea', 'diploma-builder'),
            'ER' => __('Eritrea', 'diploma-builder'), 'EE' => __('Estonia', 'diploma-builder'),
            'ET' => __('Ethiopia', 'diploma-builder'), 'FJ' => __('Fiji', 'diploma-builder'),
            'FI' => __('Finland', 'diploma-builder'), 'FR' => __('France', 'diploma-builder'),
            'GA' => __('Gabon', 'diploma-builder'), 'GM' => __('Gambia', 'diploma-builder'),
            'GE' => __('Georgia', 'diploma-builder'), 'DE' => __('Germany', 'diploma-builder'),
            'GH' => __('Ghana', 'diploma-builder'), 'GR' => __('Greece', 'diploma-builder'),
            'GD' => __('Grenada', 'diploma-builder'), 'GT' => __('Guatemala', 'diploma-builder'),
            'GN' => __('Guinea', 'diploma-builder'), 'GW' => __('Guinea-Bissau', 'diploma-builder'),
            'GY' => __('Guyana', 'diploma-builder'), 'HT' => __('Haiti', 'diploma-builder'),
            'HN' => __('Honduras', 'diploma-builder'), 'HU' => __('Hungary', 'diploma-builder'),
            'IS' => __('Iceland', 'diploma-builder'), 'IN' => __('India', 'diploma-builder'),
            'ID' => __('Indonesia', 'diploma-builder'), 'IR' => __('Iran', 'diploma-builder'),
            'IQ' => __('Iraq', 'diploma-builder'), 'IE' => __('Ireland', 'diploma-builder'),
            'IL' => __('Israel', 'diploma-builder'), 'IT' => __('Italy', 'diploma-builder'),
            'JM' => __('Jamaica', 'diploma-builder'), 'JP' => __('Japan', 'diploma-builder'),
            'JO' => __('Jordan', 'diploma-builder'), 'KZ' => __('Kazakhstan', 'diploma-builder'),
            'KE' => __('Kenya', 'diploma-builder'), 'KI' => __('Kiribati', 'diploma-builder'),
            'KP' => __('North Korea', 'diploma-builder'), 'KR' => __('South Korea', 'diploma-builder'),
            'KW' => __('Kuwait', 'diploma-builder'), 'KG' => __('Kyrgyzstan', 'diploma-builder'),
            'LA' => __('Laos', 'diploma-builder'), 'LV' => __('Latvia', 'diploma-builder'),
            'LB' => __('Lebanon', 'diploma-builder'), 'LS' => __('Lesotho', 'diploma-builder'),
            'LR' => __('Liberia', 'diploma-builder'), 'LY' => __('Libya', 'diploma-builder'),
            'LI' => __('Liechtenstein', 'diploma-builder'), 'LT' => __('Lithuania', 'diploma-builder'),
            'LU' => __('Luxembourg', 'diploma-builder'), 'MK' => __('Macedonia', 'diploma-builder'),
            'MG' => __('Madagascar', 'diploma-builder'), 'MW' => __('Malawi', 'diploma-builder'),
            'MY' => __('Malaysia', 'diploma-builder'), 'MV' => __('Maldives', 'diploma-builder'),
            'ML' => __('Mali', 'diploma-builder'), 'MT' => __('Malta', 'diploma-builder'),
            'MH' => __('Marshall Islands', 'diploma-builder'), 'MR' => __('Mauritania', 'diploma-builder'),
            'MU' => __('Mauritius', 'diploma-builder'), 'MX' => __('Mexico', 'diploma-builder'),
            'FM' => __('Micronesia', 'diploma-builder'), 'MD' => __('Moldova', 'diploma-builder'),
            'MC' => __('Monaco', 'diploma-builder'), 'MN' => __('Mongolia', 'diploma-builder'),
            'ME' => __('Montenegro', 'diploma-builder'), 'MA' => __('Morocco', 'diploma-builder'),
            'MZ' => __('Mozambique', 'diploma-builder'), 'MM' => __('Myanmar', 'diploma-builder'),
            'NA' => __('Namibia', 'diploma-builder'), 'NR' => __('Nauru', 'diploma-builder'),
            'NP' => __('Nepal', 'diploma-builder'), 'NL' => __('Netherlands', 'diploma-builder'),
            'NZ' => __('New Zealand', 'diploma-builder'), 'NI' => __('Nicaragua', 'diploma-builder'),
            'NE' => __('Niger', 'diploma-builder'), 'NG' => __('Nigeria', 'diploma-builder'),
            'NO' => __('Norway', 'diploma-builder'), 'OM' => __('Oman', 'diploma-builder'),
            'PK' => __('Pakistan', 'diploma-builder'), 'PW' => __('Palau', 'diploma-builder'),
            'PA' => __('Panama', 'diploma-builder'), 'PG' => __('Papua New Guinea', 'diploma-builder'),
            'PY' => __('Paraguay', 'diploma-builder'), 'PE' => __('Peru', 'diploma-builder'),
            'PH' => __('Philippines', 'diploma-builder'), 'PL' => __('Poland', 'diploma-builder'),
            'PT' => __('Portugal', 'diploma-builder'), 'QA' => __('Qatar', 'diploma-builder'),
            'RO' => __('Romania', 'diploma-builder'), 'RU' => __('Russia', 'diploma-builder'),
            'RW' => __('Rwanda', 'diploma-builder'), 'KN' => __('Saint Kitts and Nevis', 'diploma-builder'),
            'LC' => __('Saint Lucia', 'diploma-builder'), 'VC' => __('Saint Vincent and the Grenadines', 'diploma-builder'),
            'WS' => __('Samoa', 'diploma-builder'), 'SM' => __('San Marino', 'diploma-builder'),
            'ST' => __('Sao Tome and Principe', 'diploma-builder'), 'SA' => __('Saudi Arabia', 'diploma-builder'),
            'SN' => __('Senegal', 'diploma-builder'), 'RS' => __('Serbia', 'diploma-builder'),
            'SC' => __('Seychelles', 'diploma-builder'), 'SL' => __('Sierra Leone', 'diploma-builder'),
            'SG' => __('Singapore', 'diploma-builder'), 'SK' => __('Slovakia', 'diploma-builder'),
            'SI' => __('Slovenia', 'diploma-builder'), 'SB' => __('Solomon Islands', 'diploma-builder'),
            'SO' => __('Somalia', 'diploma-builder'), 'ZA' => __('South Africa', 'diploma-builder'),
            'SS' => __('South Sudan', 'diploma-builder'), 'ES' => __('Spain', 'diploma-builder'),
            'LK' => __('Sri Lanka', 'diploma-builder'), 'SD' => __('Sudan', 'diploma-builder'),
            'SR' => __('Suriname', 'diploma-builder'), 'SZ' => __('Swaziland', 'diploma-builder'),
            'SE' => __('Sweden', 'diploma-builder'), 'CH' => __('Switzerland', 'diploma-builder'),
            'SY' => __('Syria', 'diploma-builder'), 'TW' => __('Taiwan', 'diploma-builder'),
            'TJ' => __('Tajikistan', 'diploma-builder'), 'TZ' => __('Tanzania', 'diploma-builder'),
            'TH' => __('Thailand', 'diploma-builder'), 'TL' => __('Timor-Leste', 'diploma-builder'),
            'TG' => __('Togo', 'diploma-builder'), 'TO' => __('Tonga', 'diploma-builder'),
            'TT' => __('Trinidad and Tobago', 'diploma-builder'), 'TN' => __('Tunisia', 'diploma-builder'),
            'TR' => __('Turkey', 'diploma-builder'), 'TM' => __('Turkmenistan', 'diploma-builder'),
            'TV' => __('Tuvalu', 'diploma-builder'), 'UG' => __('Uganda', 'diploma-builder'),
            'UA' => __('Ukraine', 'diploma-builder'), 'AE' => __('United Arab Emirates', 'diploma-builder'),
            'GB' => __('United Kingdom', 'diploma-builder'), 'US' => __('United States', 'diploma-builder'),
            'UY' => __('Uruguay', 'diploma-builder'), 'UZ' => __('Uzbekistan', 'diploma-builder'),
            'VU' => __('Vanuatu', 'diploma-builder'), 'VA' => __('Vatican City', 'diploma-builder'),
            'VE' => __('Venezuela', 'diploma-builder'), 'VN' => __('Vietnam', 'diploma-builder'),
            'YE' => __('Yemen', 'diploma-builder'), 'ZM' => __('Zambia', 'diploma-builder'),
            'ZW' => __('Zimbabwe', 'diploma-builder')
        );
    }

}

?>