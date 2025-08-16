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
        add_shortcode('diploma_gallery', array($this, 'diploma_gallery_shortcode'));
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
    
    public function diploma_gallery_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 12,
            'columns' => 3,
            'show_user_only' => 'false',
            'show_public_only' => 'true'
        ), $atts, 'diploma_gallery');
        
        ob_start();
        $this->render_diploma_gallery($atts);
        return ob_get_clean();
    }
    
    private function render_diploma_builder($atts) {
        $diploma_styles = $this->get_diploma_styles();
        $paper_colors = $this->get_paper_colors();
        $generic_emblems = $this->get_generic_emblems();
        $us_states = $this->get_us_states();
        ?>
        <div id="diploma-builder-container" style="max-width: <?php echo esc_attr($atts['max_width']); ?>">
            <div class="diploma-builder-wrapper">
                <div class="diploma-builder-form">
                    <div class="form-header">
                        <h2><?php _e('Create Your Diploma', 'diploma-builder'); ?></h2>
                        <p class="form-description"><?php _e('Design and customize your high school diploma with our easy-to-use builder.', 'diploma-builder'); ?></p>
                    </div>
                    
                    <?php $this->render_progress_bar(); ?>
                    
                    <!-- Step 1: Diploma Style -->
                    <div class="form-section" data-step="1">
                        <div class="section-header">
                            <h3><span class="step-number">1</span><?php _e('Choose Diploma Style', 'diploma-builder'); ?></h3>
                            <p class="section-description"><?php _e('Select from our professionally designed diploma templates.', 'diploma-builder'); ?></p>
                        </div>
                        <div class="style-options">
                            <?php foreach ($diploma_styles as $key => $style): ?>
                                <label class="style-option" for="style_<?php echo $key; ?>">
                                    <input type="radio" name="diploma_style" value="<?php echo $key; ?>" id="style_<?php echo $key; ?>" <?php echo $key === 'classic' ? 'checked' : ''; ?>>
                                    <div class="style-preview">
                                        <img src="<?php echo DIPLOMA_BUILDER_URL . 'assets/images/previews/' . $key . '.jpg'; ?>" alt="<?php echo esc_attr($style['name']); ?>">
                                        <div class="style-overlay">
                                            <span class="checkmark">✓</span>
                                        </div>
                                    </div>
                                    <div class="style-info">
                                        <h4><?php echo esc_html($style['name']); ?></h4>
                                        <p><?php echo esc_html($style['description']); ?></p>
                                        <span class="emblem-count"><?php printf(__('%d emblem position(s)', 'diploma-builder'), $style['emblems']); ?></span>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Step 2: Paper Color -->
                    <div class="form-section" data-step="2">
                        <div class="section-header">
                            <h3><span class="step-number">2</span><?php _e('Select Paper Color', 'diploma-builder'); ?></h3>
                            <p class="section-description"><?php _e('Choose the background color for your diploma.', 'diploma-builder'); ?></p>
                        </div>
                        <div class="color-options">
                            <?php foreach ($paper_colors as $key => $color): ?>
                                <label class="color-option" for="color_<?php echo $key; ?>">
                                    <input type="radio" name="paper_color" value="<?php echo $key; ?>" id="color_<?php echo $key; ?>" <?php echo $key === 'white' ? 'checked' : ''; ?>>
                                    <div class="color-preview" style="background-color: <?php echo $color['hex']; ?>">
                                        <span class="color-checkmark">✓</span>
                                    </div>
                                    <span class="color-name"><?php echo esc_html($color['name']); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Step 3: Emblem Selection -->
                    <div class="form-section" data-step="3">
                        <div class="section-header">
                            <h3><span class="step-number">3</span><?php _e('Choose Emblem', 'diploma-builder'); ?></h3>
                            <p class="section-description"><?php _e('Add an official emblem to your diploma.', 'diploma-builder'); ?></p>
                        </div>
                        
                        <div class="emblem-type-tabs">
                            <button type="button" class="tab-button active" data-tab="generic">
                                <span class="tab-icon">🎓</span>
                                <?php _e('Generic Emblems', 'diploma-builder'); ?>
                            </button>
                            <button type="button" class="tab-button" data-tab="state">
                                <span class="tab-icon">🏛️</span>
                                <?php _e('State Emblems', 'diploma-builder'); ?>
                            </button>
                        </div>
                        
                        <div class="emblem-content">
                            <div id="generic-emblems" class="emblem-tab-content active">
                                <div class="emblem-options">
                                    <?php foreach ($generic_emblems as $key => $emblem): ?>
                                        <label class="emblem-option" for="emblem_<?php echo $key; ?>">
                                            <input type="radio" name="emblem_value" value="<?php echo $key; ?>" id="emblem_<?php echo $key; ?>" data-type="generic" <?php echo $key === 'graduation_cap' ? 'checked' : ''; ?>>
                                            <div class="emblem-preview">
                                                <img src="<?php echo DIPLOMA_BUILDER_URL . 'assets/images/emblems/generic/' . $key . '.png'; ?>" alt="<?php echo esc_attr($emblem['name']); ?>">
                                                <div class="emblem-overlay">
                                                    <span class="checkmark">✓</span>
                                                </div>
                                            </div>
                                            <div class="emblem-info">
                                                <h4><?php echo esc_html($emblem['name']); ?></h4>
                                                <p><?php echo esc_html($emblem['description']); ?></p>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div id="state-emblems" class="emblem-tab-content">
                                <div class="state-selector">
                                    <label for="state-emblem-select"><?php _e('Select Your State:', 'diploma-builder'); ?></label>
                                    <select name="state_emblem" id="state-emblem-select">
                                        <option value=""><?php _e('Choose a state...', 'diploma-builder'); ?></option>
                                        <?php foreach ($us_states as $code => $name): ?>
                                            <option value="<?php echo $code; ?>" data-type="state"><?php echo esc_html($name); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div id="state-emblem-preview" class="state-preview" style="display: none;">
                                        <img src="" alt="" id="state-emblem-image">
                                        <p id="state-emblem-name"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Step 4: Custom Text -->
                    <div class="form-section" data-step="4">
                        <div class="section-header">
                            <h3><span class="step-number">4</span><?php _e('Enter Details', 'diploma-builder'); ?></h3>
                            <p class="section-description"><?php _e('Fill in the information that will appear on your diploma.', 'diploma-builder'); ?></p>
                        </div>
                        <div class="text-fields">
                            <div class="field-row">
                                <div class="field-group">
                                    <label for="school_name" class="required"><?php _e('School Name', 'diploma-builder'); ?></label>
                                    <input type="text" id="school_name" name="school_name" placeholder="<?php _e('Enter school name', 'diploma-builder'); ?>" maxlength="100" required>
                                    <div class="field-hint"><?php _e('The official name of your high school', 'diploma-builder'); ?></div>
                                </div>
                                <div class="field-group">
                                    <label for="student_name"><?php _e('Student Name (Optional)', 'diploma-builder'); ?></label>
                                    <input type="text" id="student_name" name="student_name" placeholder="<?php _e('Leave blank for template', 'diploma-builder'); ?>" maxlength="100">
                                    <div class="field-hint"><?php _e('Name of the graduate (optional)', 'diploma-builder'); ?></div>
                                </div>
                            </div>
                            <div class="field-row">
                                <div class="field-group">
                                    <label for="graduation_date" class="required"><?php _e('Date of Graduation', 'diploma-builder'); ?></label>
                                    <input type="text" id="graduation_date" name="graduation_date" placeholder="<?php _e('e.g., June 15, 2024', 'diploma-builder'); ?>" maxlength="50" required>
                                    <div class="field-hint"><?php _e('The date when graduation took place', 'diploma-builder'); ?></div>
                                </div>
                                <div class="field-group">
                                    <label for="city" class="required"><?php _e('City', 'diploma-builder'); ?></label>
                                    <input type="text" id="city" name="city" placeholder="<?php _e('Enter city', 'diploma-builder'); ?>" maxlength="50" required>
                                    <div class="field-hint"><?php _e('City where the school is located', 'diploma-builder'); ?></div>
                                </div>
                                <div class="field-group">
                                    <label for="state" class="required"><?php _e('State', 'diploma-builder'); ?></label>
                                    <input type="text" id="state" name="state" placeholder="<?php _e('Enter state', 'diploma-builder'); ?>" maxlength="50" required>
                                    <div class="field-hint"><?php _e('State where the school is located', 'diploma-builder'); ?></div>
                                </div>
                            </div>
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
                        
                        <div class="form-actions" style="display: none;">
                            <button type="button" id="save-diploma" class="btn btn-success">
                                <span class="btn-icon">💾</span>
                                <?php _e('Save Diploma', 'diploma-builder'); ?>
                            </button>
                            <button type="button" id="download-diploma" class="btn btn-primary">
                                <span class="btn-icon">📥</span>
                                <?php _e('Download High-Res', 'diploma-builder'); ?>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Live Preview -->
                <div class="diploma-preview-container">
                    <div class="preview-header">
                        <h3><?php _e('Live Preview', 'diploma-builder'); ?></h3>
                        <div class="preview-controls">
                            <button type="button" id="toggle-fullscreen" class="btn-icon-small" title="<?php _e('Toggle Fullscreen', 'diploma-builder'); ?>">⛶</button>
                            <button type="button" id="zoom-out" class="btn-icon-small" title="<?php _e('Zoom Out', 'diploma-builder'); ?>">−</button>
                            <span id="zoom-level">100%</span>
                            <button type="button" id="zoom-in" class="btn-icon-small" title="<?php _e('Zoom In', 'diploma-builder'); ?>">+</button>
                        </div>
                    </div>
                    <div class="diploma-preview">
                        <div id="diploma-canvas" class="diploma-canvas">
                            <!-- Dynamic diploma content will be inserted here -->
                        </div>
                    </div>
                    <div class="preview-info">
                        <p class="preview-note"><?php _e('This is a live preview. All changes update instantly.', 'diploma-builder'); ?></p>
                        <div class="preview-specs">
                            <span><?php _e('Print Size:', 'diploma-builder'); ?> 8.5" × 11"</span>
                            <span><?php _e('Resolution:', 'diploma-builder'); ?> 300 DPI</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if ($atts['show_gallery'] === 'true'): ?>
                <div class="diploma-gallery-section">
                    <h3><?php _e('Recent Diplomas', 'diploma-builder'); ?></h3>
                    <?php $this->render_diploma_gallery(array('limit' => 6, 'columns' => 3)); ?>
                </div>
            <?php endif; ?>
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
                        <button type="button" id="view-gallery" class="btn btn-primary"><?php _e('View Gallery', 'diploma-builder'); ?></button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function render_progress_bar() {
        ?>
        <div class="progress-container">
            <div class="progress-bar-container">
                <div class="progress-bar" id="form-progress">
                    <div class="progress-fill"></div>
                </div>
            </div>
            <div class="progress-steps">
                <div class="step active" data-step="1">
                    <div class="step-circle">1</div>
                    <div class="step-label"><?php _e('Style', 'diploma-builder'); ?></div>
                </div>
                <div class="step" data-step="2">
                    <div class="step-circle">2</div>
                    <div class="step-label"><?php _e('Color', 'diploma-builder'); ?></div>
                </div>
                <div class="step" data-step="3">
                    <div class="step-circle">3</div>
                    <div class="step-label"><?php _e('Emblem', 'diploma-builder'); ?></div>
                </div>
                <div class="step" data-step="4">
                    <div class="step-circle">4</div>
                    <div class="step-label"><?php _e('Details', 'diploma-builder'); ?></div>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function render_diploma_gallery($atts) {
        $limit = intval($atts['limit']);
        $columns = intval($atts['columns']);
        $show_user_only = $atts['show_user_only'] === 'true';
        $show_public_only = $atts['show_public_only'] === 'true';
        
        // Get diplomas based on settings
        if ($show_user_only && is_user_logged_in()) {
            $diplomas = DiplomaBuilder_Database::get_user_diplomas(get_current_user_id(), $limit);
        } else {
            $diplomas = DiplomaBuilder_Database::get_all_diplomas($limit);
            if ($show_public_only) {
                $diplomas = array_filter($diplomas, function($diploma) {
                    return $diploma->is_public;
                });
            }
        }
        
        if (empty($diplomas)) {
            echo '<p class="no-diplomas">' . __('No diplomas to display yet.', 'diploma-builder') . '</p>';
            return;
        }
        
        ?>
        <div class="diploma-gallery" data-columns="<?php echo $columns; ?>">
            <?php foreach ($diplomas as $diploma): ?>
                <div class="diploma-card">
                    <div class="diploma-thumbnail">
                        <?php if ($diploma->image_path && file_exists($diploma->image_path)): ?>
                            <img src="<?php echo wp_get_attachment_url($diploma->image_path); ?>" alt="<?php echo esc_attr($diploma->school_name); ?>">
                        <?php else: ?>
                            <div class="diploma-placeholder">
                                <span class="placeholder-icon">🎓</span>
                            </div>
                        <?php endif; ?>
                        <div class="diploma-overlay">
                            <button type="button" class="btn-view-diploma" data-diploma-id="<?php echo $diploma->id; ?>">
                                <?php _e('View', 'diploma-builder'); ?>
                            </button>
                        </div>
                    </div>
                    <div class="diploma-info">
                        <h4><?php echo esc_html($diploma->school_name); ?></h4>
                        <p class="diploma-date"><?php echo esc_html($diploma->graduation_date); ?></p>
                        <p class="diploma-location"><?php echo esc_html($diploma->city . ', ' . $diploma->state); ?></p>
                        <div class="diploma-meta">
                            <span class="diploma-style"><?php echo esc_html(ucwords(str_replace('_', ' ', $diploma->diploma_style))); ?></span>
                            <span class="diploma-downloads"><?php printf(__('%d downloads', 'diploma-builder'), $diploma->download_count); ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    private function get_diploma_styles() {
        return array(
            'classic' => array(
                'name' => __('Classic Traditional', 'diploma-builder'),
                'description' => __('Traditional diploma with elegant borders and classic typography.', 'diploma-builder'),
                'emblems' => 1,
                'template' => 'classic'
            ),
            'modern' => array(
                'name' => __('Modern Elegant', 'diploma-builder'),
                'description' => __('Contemporary design with clean lines and modern styling.', 'diploma-builder'),
                'emblems' => 2,
                'template' => 'modern'
            ),
            'formal' => array(
                'name' => __('Formal Certificate', 'diploma-builder'),
                'description' => __('Professional certificate style with formal presentation.', 'diploma-builder'),
                'emblems' => 1,
                'template' => 'formal'
            ),
            'decorative' => array(
                'name' => __('Decorative Border', 'diploma-builder'),
                'description' => __('Ornate design with decorative elements and rich details.', 'diploma-builder'),
                'emblems' => 2,
                'template' => 'decorative'
            ),
            'minimalist' => array(
                'name' => __('Minimalist Clean', 'diploma-builder'),
                'description' => __('Simple, clean design focusing on content and readability.', 'diploma-builder'),
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
            'laurel_wreath' => array(
                'name' => __('Laurel Wreath', 'diploma-builder'),
                'description' => __('Symbol of achievement and honor', 'diploma-builder')
            ),
            'school_crest' => array(
                'name' => __('School Crest', 'diploma-builder'),
                'description' => __('Generic educational institution crest', 'diploma-builder')
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