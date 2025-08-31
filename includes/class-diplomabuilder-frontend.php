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
            <div class="diploma-builder-header">
                <div class="header-content">
                    <div class="header-icon">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3zm6.82 6L12 12.72 5.18 9 12 5.28 18.82 9zM17 15.99l-5 2.73-5-2.73v-3.72L12 15l5-2.73v3.72z"/>
                        </svg>
                    </div>
                    <div class="header-text">
                        <h1><?php _e('High School Diploma Builder', 'diploma-builder'); ?></h1>
                        <p><?php _e('Create custom high school diplomas for display, gifts, or film props', 'diploma-builder'); ?></p>
                    </div>
                </div>
            </div>

            <div class="diploma-builder-wrapper">
                <div class="diploma-builder-form">
                    <div class="form-header">
                        <div class="form-header-icon">🏆</div>
                        <h2><?php _e('Customize Your Diploma', 'diploma-builder'); ?></h2>
                    </div>
                    
                    <?php $this->render_progress_bar(); ?>
                    
                    <div class="form-content">
                        <!-- Step 1: Diploma Style (Full Width) -->
                        <div class="form-section" data-step="1" style="display: block;">
                            <div class="section-header">
                                <h3><?php _e('Step 1 of 5', 'diploma-builder'); ?></h3>
                                <div class="section-title"><?php _e('Diploma Style', 'diploma-builder'); ?></div>
                                <p class="section-description"><?php _e('Choose your diploma template', 'diploma-builder'); ?></p>
                            </div>
                            
                            <div class="section-content">
                                <div class="section-icon">📋</div>
                                <h4><?php _e('Choose Your Diploma Style', 'diploma-builder'); ?></h4>
                                
                                <!-- Enhanced Template Gallery -->
                                <div class="template-gallery">
                                    <?php foreach ($diploma_styles as $key => $style): ?>
                                        <label class="template-card" for="style_<?php echo $key; ?>">
                                            <input type="radio" name="diploma_style" value="<?php echo $key; ?>" id="style_<?php echo $key; ?>" <?php echo $key === 'classic' ? 'checked' : ''; ?>>
                                            <div class="template-preview">
                                                <img src="<?php echo DIPLOMA_BUILDER_URL . 'assets/previews/' . $key . '.png'; ?>" alt="<?php echo esc_attr($style['name']); ?>" loading="lazy">
                                                <div class="template-overlay">
                                                    <div class="template-check">✓</div>
                                                </div>
                                            </div>
                                            <div class="template-info">
                                                <h5><?php echo esc_html($style['name']); ?></h5>
                                                <!-- <p><?php //echo esc_html($style['description']); ?></p> -->
                                                <!-- <div class="template-features">
                                                    <span class="feature-badge"><?php //printf(__('%d Emblem(s)', 'diploma-builder'), $style['emblems']); ?></span>
                                                    <span class="feature-badge popular" <?php //echo $key === 'classic' ? 'style="display:inline-block"' : 'style="display:none"'; ?>><?php //_e('Popular', 'diploma-builder'); ?></span>
                                                </div> -->
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                
                                <!-- Template Filter Options -->
                                <!-- <div class="template-filters">
                                    <button type="button" class="filter-btn active" data-filter="all"><?php // _e('All Templates', 'diploma-builder'); ?></button>
                                    <button type="button" class="filter-btn" data-filter="formal"><?php //_e('Formal', 'diploma-builder'); ?></button>
                                    <button type="button" class="filter-btn" data-filter="modern"><?php //_e('Modern', 'diploma-builder'); ?></button>
                                    <button type="button" class="filter-btn" data-filter="classic"><?php //_e('Traditional', 'diploma-builder'); ?></button>
                                </div> -->
                            </div>
                        </div>
                    
                        <!-- Step 2: Paper Color -->
                        <div class="form-section" data-step="2" style="display: none;">
                            <div class="section-header">
                                <h3><?php _e('Step 2 of 5', 'diploma-builder'); ?></h3>
                                <div class="section-title"><?php _e('Paper Color', 'diploma-builder'); ?></div>
                                <p class="section-description"><?php _e('Select your preferred paper color', 'diploma-builder'); ?></p>
                            </div>
                            
                            <div class="section-content">
                                <div class="section-icon">🎨</div>
                                <h4><?php _e('Choose Paper Color', 'diploma-builder'); ?></h4>
                                <p class="form-note"><?php _e('Select the background color for your diploma. This will affect the overall appearance and feel of your certificate.', 'diploma-builder'); ?></p>
                                
                                <div class="color-options">
                                    <?php foreach ($paper_colors as $key => $color): ?>
                                        <label class="color-option" for="color_<?php echo $key; ?>">
                                            <input type="radio" name="paper_color" value="<?php echo $key; ?>" id="color_<?php echo $key; ?>" <?php echo $key === 'white' ? 'checked' : ''; ?>>
                                            <div class="color-preview" style="background-color: <?php echo $color['hex']; ?>">
                                                <!-- <span class="color-checkmark">✓</span> -->
                                            </div>
                                            <span class="color-name"><?php echo esc_html($color['name']); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    
                        <!-- Step 3: Emblem Selection -->
                        <div class="form-section" data-step="3" style="display: none;">
                            <div class="section-header">
                                <h3><?php _e('Step 3 of 5', 'diploma-builder'); ?></h3>
                                <div class="section-title"><?php _e('Emblem Selection', 'diploma-builder'); ?></div>
                                <p class="section-description"><?php _e('Choose an emblem for your diploma', 'diploma-builder'); ?></p>
                            </div>
                            
                            <div class="section-content">
                                <div class="section-icon">🏆</div>
                                <h4><?php _e('Choose Your Emblem', 'diploma-builder'); ?></h4>
                                <p class="form-note"><?php _e('Select an emblem that will appear on your diploma. You can choose from generic academic emblems or official state emblems.', 'diploma-builder'); ?></p>
                                
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
                                                    <!-- <div class="emblem-overlay">
                                                        <div class="emblem-check">✓</div>
                                                    </div> -->
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
                        </div>
                    
                        <!-- Step 4: Custom Text & Details -->
                        <div class="form-section" data-step="4" style="display: none;">
                            <div class="section-header">
                                <h3><?php _e('Step 4 of 5', 'diploma-builder'); ?></h3>
                                <div class="section-title"><?php _e('Custom Text', 'diploma-builder'); ?></div>
                                <p class="section-description"><?php _e('Enter school and student details', 'diploma-builder'); ?></p>
                            </div>
                            
                            <div class="section-content">
                                <div class="section-icon">📝</div>
                                <h4><?php _e('Custom Text & Details', 'diploma-builder'); ?></h4>
                                <p class="form-note"><?php _e('Enter the custom text that will appear on your diploma. All fields marked with * are required.', 'diploma-builder'); ?></p>
                                
                                <!-- Student Information -->
                                <div class="subsection">
                                    <div class="subsection-icon">👤</div>
                                    <h5><?php _e('Student Information', 'diploma-builder'); ?></h5>
                                    <div class="field-group">
                                        <label for="student_name"><?php _e('Student Name *', 'diploma-builder'); ?></label>
                                        <input type="text" id="student_name" name="student_name" placeholder="<?php _e('Enter student\'s full name', 'diploma-builder'); ?>" maxlength="100" required>
                                        <div class="field-hint"><?php _e('This name will appear prominently on the diploma', 'diploma-builder'); ?></div>
                                    </div>
                                </div>
                                
                                <!-- School Information -->
                                <div class="subsection">
                                    <div class="subsection-icon">🏫</div>
                                    <h5><?php _e('School Information', 'diploma-builder'); ?></h5>
                                    <div class="field-row">
                                        <div class="field-group">
                                            <label for="school_name"><?php _e('High School Name *', 'diploma-builder'); ?></label>
                                            <input type="text" id="school_name" name="school_name" placeholder="<?php _e('e.g., Lincoln High School', 'diploma-builder'); ?>" maxlength="100" required>
                                        </div>
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
                                
                                <!-- Date of Graduation -->
                                <div class="subsection">
                                    <div class="subsection-icon">📅</div>
                                    <h5><?php _e('Date of Graduation', 'diploma-builder'); ?></h5>
                                    <div class="field-group">
                                        <label for="graduation_date"><?php _e('Graduation Date *', 'diploma-builder'); ?></label>
                                        <input type="date" id="graduation_date" name="graduation_date" class="form-input" required>
                                        <div class="field-hint"><?php _e('This date will appear on the diploma', 'diploma-builder'); ?></div>
                                    </div>
                                </div>
                                
                                <div class="required-note">
                                    <p><?php _e('* Required fields', 'diploma-builder'); ?></p>
                                </div>
                            </div>
                        </div>
                    
                        <!-- Step 5: Review & Download -->
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
                                <div class="purchase-options">
                                    <h5><?php _e('Purchase Options', 'diploma-builder'); ?></h5>
                                    <div class="purchase-grid">
                                        <div class="purchase-option">
                                            <div class="purchase-header">
                                                <div class="purchase-icon">💰</div>
                                                <h6><?php _e('Digital Download', 'diploma-builder'); ?></h6>
                                            </div>
                                            <div class="purchase-price">
                                                <span class="price-amount">$4.99</span>
                                                <span class="price-description"><?php _e('Instant download', 'diploma-builder'); ?></span>
                                            </div>
                                            <button type="button" class="btn btn-primary purchase-btn" data-product-id="digital">
                                                <span class="btn-icon">📥</span>
                                                <?php _e('Buy Now', 'diploma-builder'); ?>
                                            </button>
                                        </div>
                                        
                                        <div class="purchase-option">
                                            <div class="purchase-header">
                                                <div class="purchase-icon">📦</div>
                                                <h6><?php _e('Printed & Shipped', 'diploma-builder'); ?></h6>
                                            </div>
                                            <div class="purchase-price">
                                                <span class="price-amount">$12.99</span>
                                                <span class="price-description"><?php _e('7-10 business days', 'diploma-builder'); ?></span>
                                            </div>
                                            <button type="button" class="btn btn-primary purchase-btn" data-product-id="printed">
                                                <span class="btn-icon">📦</span>
                                                <?php _e('Buy Now', 'diploma-builder'); ?>
                                            </button>
                                        </div>
                                        
                                        <div class="purchase-option featured">
                                            <div class="purchase-header">
                                                <div class="purchase-icon">🏆</div>
                                                <h6><?php _e('Premium Package', 'diploma-builder'); ?></h6>
                                            </div>
                                            <div class="purchase-price">
                                                <span class="price-amount">$19.99</span>
                                                <span class="price-description"><?php _e('Printed + Digital', 'diploma-builder'); ?></span>
                                            </div>
                                            <button type="button" class="btn btn-success purchase-btn" data-product-id="premium">
                                                <span class="btn-icon">⭐</span>
                                                <?php _e('Buy Now', 'diploma-builder'); ?>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="purchase-note">
                                        <p><?php _e('All purchases include high-resolution files and are processed through our secure checkout.', 'diploma-builder'); ?></p>
                                    </div>
                                </div>
                                
                                <!-- Sharing Options -->
                                <!-- <div class="sharing-options">
                                    <h5><?php // _e('this section', 'diploma-builder'); ?></h5>
                                    <div class="share-buttons">
                                        <button type="button" class="share-btn" id="share-facebook">
                                            <div class="share-icon" style="background: #1877f2;"></div>
                                            <span><?php //_e('Facebook', 'diploma-builder'); ?></span>
                                        </button>
                                        <button type="button" class="share-btn" id="share-twitter">
                                            <div class="share-icon" style="background: #1da1f2;">🐦</div>
                                            <span><?php //_e('Twitter', 'diploma-builder'); ?></span>
                                        </button>
                                        <button type="button" class="share-btn" id="share-linkedin">
                                            <div class="share-icon" style="background: #0077b5;">💼</div>
                                            <span><?php //_e('LinkedIn', 'diploma-builder'); ?></span>
                                        </button>
                                        <button type="button" class="share-btn" id="copy-link">
                                            <div class="share-icon" style="background: #6b7280;">🔗</div>
                                            <span><?php //_e('Copy Link', 'diploma-builder'); ?></span>
                                        </button>
                                    </div>
                                </div> -->

                                

                                <div class="form-actions" style="display: none;">
                                    <button type="button" id="save-diploma" <?php

                                if( ! is_user_logged_in() ) {
                                    echo 'disabled';
                                }

                                ?> class="btn btn-success">
                                        <span class="btn-icon">💾</span>
                                        <?php _e('Save Diploma', 'diploma-builder'); ?>
                                    </button>
                                    <button type="button" id="download-diploma" class="btn btn-primary">
                                        <span class="btn-icon">📥</span>
                                        <?php _e('Download', 'diploma-builder'); ?>
                                    </button>
                                </div>
                                
                                <?php if (!is_user_logged_in() || !$this->current_user_can_purchase_diploma()): ?>
                                <div class="preview-notice">
                                    <p><?php _e('This is a preview only. Purchase a diploma to remove the watermark and unlock full features.', 'diploma-builder'); ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Navigation and Action Buttons -->
                        <div class="form-navigation">
                            <div class="nav-buttons">
                                <button type="button" id="prev-step" class="btn btn-secondary" disabled>
                                    <span class="btn-icon">‹</span>
                                    <?php _e('Previous', 'diploma-builder'); ?>
                                </button>
                                <button type="button" id="next-step" class="btn btn-primary">
                                    <?php _e('Next', 'diploma-builder'); ?>
                                    <span class="btn-icon">›</span>
                                </button>
                            </div>
                            
                            
                        </div>
                    </div>
                </div>

                <div class="diploma-preview-container">
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
                    <!-- <div class="preview-info">
                        <p class="preview-note"><?php // _e('This is a live preview. All changes update instantly.', 'diploma-builder'); ?></p>
                        <div class="preview-specs">
                            <span><?php //_e('Print Size:', 'diploma-builder'); ?> 8.5" × 11"</span>
                            <span><?php //_e('Resolution:', 'diploma-builder'); ?> 300 DPI</span>
                        </div>
                    </div> -->
                </div>
                
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
    
    private function render_progress_bar() {
        ?>
        <!-- <div class="progress-container">
            <div class="progress-bar-container">
                <div class="progress-bar" id="form-progress">
                    <div class="progress-fill"></div>
                </div>
            </div>
            <div class="progress-steps">
                <div class="step active" data-step="1">
                    <div class="step-circle">1</div>
                    <div class="step-label"><?php // _e('Diploma Style', 'diploma-builder'); ?></div>
                </div>
                <div class="step" data-step="2">
                    <div class="step-circle">2</div>
                    <div class="step-label"><?php //_e('Paper Color', 'diploma-builder'); ?></div>
                </div>
                <div class="step" data-step="3">
                    <div class="step-circle">3</div>
                    <div class="step-label"><?php //_e('Emblem', 'diploma-builder'); ?></div>
                </div>
                <div class="step" data-step="4">
                    <div class="step-circle">4</div>
                    <div class="step-label"><?php //_e('Custom Text', 'diploma-builder'); ?></div>
                </div>
                <div class="step" data-step="4">
                    <div class="step-circle">4</div>
                    <div class="step-label"><?php //_e('Review & Download', 'diploma-builder'); ?></div>
                </div>
            </div>
        </div> -->
        <?php
    }
    
    private function get_diploma_styles() {
        return array(
            'classic' => array(
                'name' => __('Classic Traditional', 'diploma-builder'),
                'description' => __('.', 'diploma-builder'),
                'emblems' => 1,
                'template' => 'classic'
            ),
            // 'modern' => array(
            //     'name' => __('Modern Elegant', 'diploma-builder'),
            //     'description' => __('.', 'diploma-builder'),
            //     'emblems' => 1,
            //     'template' => 'modern'
            // ),
            'formal' => array(
                'name' => __('Formal Certificate', 'diploma-builder'),
                'description' => __('', 'diploma-builder'),
                'emblems' => 1,
                'template' => 'formal'
            ),
            // 'decorative' => array(
            //     'name' => __('Decorative Border', 'diploma-builder'),
            //     'description' => __('', 'diploma-builder'),
            //     'emblems' =>2,
            //     'template' => 'decorative'
            // ),
            'minimalist' => array(
                'name' => __('Minimalist Clean', 'diploma-builder'),
                'description' => __('', 'diploma-builder'),
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
    
    /**
     * Check if current user can purchase a diploma
     */
    private function current_user_can_purchase_diploma() {
        // If user is not logged in, they can't purchase
        if (!is_user_logged_in()) {
            return false;
        }
        
        // Check if user has purchased any diploma product
        $user_id = get_current_user_id();
        $digital_product_id = get_option('diploma_digital_product_id', 0);
        $printed_product_id = get_option('diploma_printed_product_id', 0);
        $premium_product_id = get_option('diploma_premium_product_id', 0);
        
        if (function_exists('wc_customer_bought_product')) {
            $current_user = wp_get_current_user();
            $customer_email = $current_user->user_email;
            
            if (($digital_product_id && wc_customer_bought_product($customer_email, $user_id, $digital_product_id)) ||
                ($printed_product_id && wc_customer_bought_product($customer_email, $user_id, $printed_product_id)) ||
                ($premium_product_id && wc_customer_bought_product($customer_email, $user_id, $premium_product_id))) {
                return true;
            }
        }
        
        return false;
    }
}