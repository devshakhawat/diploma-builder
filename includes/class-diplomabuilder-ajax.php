<?php
/**
 * AJAX functionality for Diploma Builder
 */

if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

class DiplomaBuilder_Ajax {
    
    public function __construct() {
        // Public AJAX actions (for both logged in and logged out users)
        add_action('wp_ajax_save_diploma', array($this, 'save_diploma'));
        add_action('wp_ajax_nopriv_save_diploma', array($this, 'save_diploma'));
        
        add_action('wp_ajax_generate_diploma_image', array($this, 'generate_diploma_image'));
        add_action('wp_ajax_nopriv_generate_diploma_image', array($this, 'generate_diploma_image'));
        
        add_action('wp_ajax_get_diploma_preview', array($this, 'get_diploma_preview'));
        add_action('wp_ajax_nopriv_get_diploma_preview', array($this, 'get_diploma_preview'));
        
        add_action('wp_ajax_load_state_emblem', array($this, 'load_state_emblem'));
        add_action('wp_ajax_nopriv_load_state_emblem', array($this, 'load_state_emblem'));
        
        // Admin only actions
        add_action('wp_ajax_delete_diploma', array($this, 'delete_diploma'));
        add_action('wp_ajax_bulk_delete_diplomas', array($this, 'bulk_delete_diplomas'));
        add_action('wp_ajax_export_diplomas', array($this, 'export_diplomas'));
        add_action('wp_ajax_get_diploma_stats', array($this, 'get_diploma_stats'));
    }
    
    /**
     * Save diploma configuration
     */
    public function save_diploma() {
        try {
            $this->verify_nonce();
            
            $data = $this->sanitize_diploma_data($_POST);
            
            // Check if user can create diploma
            $user_id = get_current_user_id();
            if (!DiplomaBuilder_Database::user_can_create_diploma($user_id)) {
                throw new Exception(__('You have reached the maximum number of diplomas allowed.', 'diploma-builder'));
            }
            
            // Validate required fields
            $this->validate_diploma_data($data);
            
            // Save to database
            $diploma_id = DiplomaBuilder_Database::save_diploma($data);
            
            if (!$diploma_id) {
                throw new Exception(__('Failed to save diploma. Please try again.', 'diploma-builder'));
            }
            
            wp_send_json_success(array(
                'message' => __('Diploma saved successfully!', 'diploma-builder'),
                'diploma_id' => $diploma_id,
                'redirect_url' => $this->get_success_redirect_url($diploma_id)
            ));
            
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Generate high-resolution diploma image
     */
    public function generate_diploma_image() {
        try {
            $this->verify_nonce();
            
            $diploma_id = intval($_POST['diploma_id'] ?? 0);
            $image_data = sanitize_text_field($_POST['image_data'] ?? '');
            
            if (!$diploma_id || !$image_data) {
                throw new Exception(__('Invalid diploma data provided.', 'diploma-builder'));
            }
            
            // Get diploma from database
            $diploma = DiplomaBuilder_Database::get_diploma($diploma_id);
            if (!$diploma) {
                throw new Exception(__('Diploma not found.', 'diploma-builder'));
            }
            
            // Check user permissions
            $user_id = get_current_user_id();
            if ($diploma->user_id && $diploma->user_id != $user_id && !current_user_can('manage_options')) {
                throw new Exception(__('Permission denied.', 'diploma-builder'));
            }
            
            // Process and save image
            $image_path = $this->save_diploma_image($image_data, $diploma_id);
            
            // Update diploma record with image path
            global $wpdb;
            $table_name = $wpdb->prefix . 'diploma_configurations';
            $wpdb->update(
                $table_name,
                array('image_path' => $image_path),
                array('id' => $diploma_id),
                array('%s'),
                array('%d')
            );
            
            // Increment download count
            DiplomaBuilder_Database::increment_download_count($diploma_id);
            
            wp_send_json_success(array(
                'message' => __('High-resolution diploma generated successfully!', 'diploma-builder'),
                'download_url' => wp_get_attachment_url($image_path),
                'file_size' => $this->get_file_size($image_path)
            ));
            
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Get diploma preview for modal display
     */
    public function get_diploma_preview() {
        try {
            $this->verify_nonce();
            
            $diploma_id = intval($_POST['diploma_id'] ?? 0);
            if (!$diploma_id) {
                throw new Exception(__('Invalid diploma ID.', 'diploma-builder'));
            }
            
            $diploma = DiplomaBuilder_Database::get_diploma($diploma_id);
            if (!$diploma) {
                throw new Exception(__('Diploma not found.', 'diploma-builder'));
            }
            
            // Check if public or user's own diploma
            $user_id = get_current_user_id();
            if (!$diploma->is_public && $diploma->user_id != $user_id && !current_user_can('manage_options')) {
                throw new Exception(__('Permission denied.', 'diploma-builder'));
            }
            
            $html = $this->generate_diploma_html($diploma);
            
            wp_send_json_success(array(
                'html' => $html,
                'diploma' => array(
                    'id' => $diploma->id,
                    'school_name' => $diploma->school_name,
                    'graduation_date' => $diploma->graduation_date,
                    'city' => $diploma->city,
                    'state' => $diploma->state,
                    'style' => $diploma->diploma_style,
                    'created_at' => $diploma->created_at
                )
            ));
            
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Load state emblem data
     */
    public function load_state_emblem() {
        try {
            $this->verify_nonce();
            
            $state_code = sanitize_text_field($_POST['state_code'] ?? '');
            if (!$state_code) {
                throw new Exception(__('Invalid state code.', 'diploma-builder'));
            }
            
            $states = $this->get_us_states();
            if (!isset($states[$state_code])) {
                throw new Exception(__('State not found.', 'diploma-builder'));
            }
            
            // Check for SVG first, then PNG
            $emblem_path_svg = DIPLOMA_BUILDER_PATH . 'assets/emblems/states/' . $state_code . '.svg';
            $emblem_path_png = DIPLOMA_BUILDER_PATH . 'assets/emblems/states/' . $state_code . '.jpg';
            
            if (file_exists($emblem_path_svg)) {
                $emblem_url = DIPLOMA_BUILDER_URL . 'assets/emblems/states/' . $state_code . '.svg';
                $emblem_exists = true;
            } elseif (file_exists($emblem_path_png)) {
                $emblem_url = DIPLOMA_BUILDER_URL . 'assets/emblems/states/' . $state_code . '.jpg';
                $emblem_exists = true;
            } else {
                $emblem_url = DIPLOMA_BUILDER_URL . 'assets/emblems/states/' . $state_code . '.svg';
                $emblem_exists = false;
            }
            
            wp_send_json_success(array(
                'state_name' => $states[$state_code],
                'state_code' => $state_code,
                'emblem_url' => $emblem_url,
                'emblem_exists' => $emblem_exists
            ));
            
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Delete diploma (admin only)
     */
    public function delete_diploma() {
        try {
            if (!current_user_can('manage_options')) {
                throw new Exception(__('Permission denied.', 'diploma-builder'));
            }
            
            $this->verify_nonce();
            
            $diploma_id = intval($_POST['diploma_id'] ?? 0);
            if (!$diploma_id) {
                throw new Exception(__('Invalid diploma ID.', 'diploma-builder'));
            }
            
            $result = DiplomaBuilder_Database::delete_diploma($diploma_id);
            
            if (!$result) {
                throw new Exception(__('Failed to delete diploma.', 'diploma-builder'));
            }
            
            wp_send_json_success(array(
                'message' => __('Diploma deleted successfully.', 'diploma-builder')
            ));
            
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Bulk delete diplomas (admin only)
     */
    public function bulk_delete_diplomas() {
        try {
            if (!current_user_can('manage_options')) {
                throw new Exception(__('Permission denied.', 'diploma-builder'));
            }
            
            $this->verify_nonce();
            
            $diploma_ids = array_map('intval', $_POST['diploma_ids'] ?? array());
            if (empty($diploma_ids)) {
                throw new Exception(__('No diplomas selected.', 'diploma-builder'));
            }
            
            global $wpdb;
            $table_name = $wpdb->prefix . 'diploma_configurations';
            $placeholders = implode(',', array_fill(0, count($diploma_ids), '%d'));
            
            $deleted_count = $wpdb->query($wpdb->prepare(
                "DELETE FROM $table_name WHERE id IN ($placeholders)",
                ...$diploma_ids
            ));
       
            
            wp_send_json_success(array(
                'message' => sprintf(__('%d diploma(s) deleted successfully.', 'diploma-builder'), $deleted_count),
                'deleted_count' => $deleted_count
            ));
            
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Export diplomas to CSV (admin only)
     */
    public function export_diplomas() {
        try {
            if (!current_user_can('manage_options')) {
                wp_die(__('Permission denied.', 'diploma-builder'));
            }
            
            if (!wp_verify_nonce($_GET['nonce'] ?? '', 'export_diplomas')) {
                wp_die(__('Security check failed.', 'diploma-builder'));
            }
            
            $diplomas = DiplomaBuilder_Database::get_all_diplomas(0); // Get all diplomas
            
            $filename = 'diploma_exports_' . date('Y-m-d_H-i-s') . '.csv';
            
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=' . $filename);
            header('Cache-Control: no-cache, must-revalidate');
            header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
            
            $output = fopen('php://output', 'w');
            
            // Add BOM for Excel compatibility
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // CSV headers
            $headers = array(
                __('ID', 'diploma-builder'),
                __('User ID', 'diploma-builder'),
                __('School Name', 'diploma-builder'),
                __('Student Name', 'diploma-builder'),
                __('Graduation Date', 'diploma-builder'),
                __('City', 'diploma-builder'),
                __('State', 'diploma-builder'),
                __('Diploma Style', 'diploma-builder'),
                __('Paper Color', 'diploma-builder'),
                __('Emblem Type', 'diploma-builder'),
                __('Emblem Value', 'diploma-builder'),
                __('Is Public', 'diploma-builder'),
                __('Download Count', 'diploma-builder'),
                __('Created Date', 'diploma-builder'),
                __('Updated Date', 'diploma-builder')
            );
            fputcsv($output, $headers);
            
            // CSV data
            foreach ($diplomas as $diploma) {
                $row = array(
                    $diploma->id,
                    $diploma->user_id ?: __('Guest', 'diploma-builder'),
                    $diploma->school_name,
                    $diploma->student_name ?: __('Not specified', 'diploma-builder'),
                    $diploma->graduation_date,
                    $diploma->city,
                    $diploma->state,
                    $diploma->diploma_style,
                    $diploma->paper_color,
                    $diploma->emblem_type,
                    $diploma->emblem_value,
                    $diploma->is_public ? __('Yes', 'diploma-builder') : __('No', 'diploma-builder'),
                    $diploma->download_count,
                    $diploma->created_at,
                    $diploma->updated_at
                );
                fputcsv($output, $row);
            }
            
            fclose($output);
            exit;
            
        } catch (Exception $e) {
            wp_die($e->getMessage());
        }
    }
    
    /**
     * Get diploma statistics (admin only)
     */
    public function get_diploma_stats() {
        try {
            if (!current_user_can('manage_options')) {
                throw new Exception(__('Permission denied.', 'diploma-builder'));
            }
            
            $this->verify_nonce();
            
            $stats = DiplomaBuilder_Database::get_statistics();
            
            wp_send_json_success(array(
                'stats' => $stats
            ));
            
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Verify nonce for security
     */
    private function verify_nonce() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'diploma_builder_nonce')) {
            throw new Exception(__('Security check failed.', 'diploma-builder'));
        }
    }
    
    /**
     * Sanitize diploma data
     */
    private function sanitize_diploma_data($data) {
        return array(
            'diploma_style' => sanitize_text_field($data['diploma_style'] ?? 'classic'),
            'paper_color' => sanitize_text_field($data['paper_color'] ?? 'white'),
            'emblem_type' => sanitize_text_field($data['emblem_type'] ?? 'generic'),
            'emblem_value' => sanitize_text_field($data['emblem_value'] ?? 'graduation_cap'),
            'school_name' => sanitize_text_field($data['school_name'] ?? ''),
            'student_name' => sanitize_text_field($data['student_name'] ?? ''),
            'graduation_date' => sanitize_text_field($data['graduation_date'] ?? ''),
            'city' => sanitize_text_field($data['city'] ?? ''),
            'state' => sanitize_text_field($data['state'] ?? ''),
            'is_public' => intval($data['is_public'] ?? 0),
            'diploma_id' => intval($data['diploma_id'] ?? 0)
        );
    }
    
    /**
     * Validate required diploma data
     */
    private function validate_diploma_data($data) {
        $required_fields = array('school_name', 'graduation_date', 'city', 'state');
        
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                throw new Exception(sprintf(__('The %s field is required.', 'diploma-builder'), str_replace('_', ' ', $field)));
            }
        }
    }
    
    /**
     * Generate diploma HTML for preview
     */
    private function generate_diploma_html($diploma) {
        $styles = $this->get_diploma_styles();
        $paper_colors = $this->get_paper_colors();
        $template = isset($styles[$diploma->diploma_style]) ? $styles[$diploma->diploma_style] : $styles['classic'];
        $paper_color = isset($paper_colors[$diploma->paper_color]) ? $paper_colors[$diploma->paper_color]['hex'] : '#ffffff';
        
        $school_name = esc_html($diploma->school_name);
        $student_name = esc_html($diploma->student_name ?: '[Student Name]');
        $graduation_date = esc_html($diploma->graduation_date);
        $city = esc_html($diploma->city);
        $state = esc_html($diploma->state);
        
        // Get emblem URL
        $emblem_html = '';
        if ($diploma->emblem_value) {
            if ($diploma->emblem_type === 'generic') {
                // Check for SVG first, then PNG
                $emblem_path_svg = DIPLOMA_BUILDER_PATH . 'assets/emblems/generic/' . $diploma->emblem_value . '.svg';
                $emblem_path_png = DIPLOMA_BUILDER_PATH . 'assets/emblems/generic/' . $diploma->emblem_value . '.jpg';
                
                if (file_exists($emblem_path_svg)) {
                    $emblem_url = DIPLOMA_BUILDER_URL . 'assets/emblems/generic/' . $diploma->emblem_value . '.svg';
                } elseif (file_exists($emblem_path_png)) {
                    $emblem_url = DIPLOMA_BUILDER_URL . 'assets/emblems/generic/' . $diploma->emblem_value . '.jpg';
                } else {
                    $emblem_url = DIPLOMA_BUILDER_URL . 'assets/emblems/generic/' . $diploma->emblem_value . '.svg';
                }
            } else {
                // Check for SVG first, then PNG
                $emblem_path_svg = DIPLOMA_BUILDER_PATH . 'assets/emblems/states/' . $diploma->emblem_value . '.svg';
                $emblem_path_png = DIPLOMA_BUILDER_PATH . 'assets/emblems/states/' . $diploma->emblem_value . '.jpg';
                
                if (file_exists($emblem_path_svg)) {
                    $emblem_url = DIPLOMA_BUILDER_URL . 'assets/emblems/states/' . $diploma->emblem_value . '.svg';
                } elseif (file_exists($emblem_path_png)) {
                    $emblem_url = DIPLOMA_BUILDER_URL . 'assets/emblems/states/' . $diploma->emblem_value . '.jpg';
                } else {
                    $emblem_url = DIPLOMA_BUILDER_URL . 'assets/emblems/states/' . $diploma->emblem_value . '.svg';
                }
            }
            
            if ($template['emblems'] === 1) {
                $emblem_html = '<div class="diploma-emblems single"><img src="' . $emblem_url . '" alt="Emblem" class="diploma-emblem" onerror="this.parentNode.innerHTML=\'&lt;div class=&quot;emblem-placeholder&quot; style=&quot;width:80px;height:80px;margin:20px;background:#f0f0f0;border:1px solid #ddd;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;color:#666;&quot;&gt;&lt;div&gt;' . esc_attr(substr($diploma->emblem_value, 0, 3)) . '&lt;/div&gt;&lt;/div&gt;\';"></div>';
            } else {
                $emblem_html = '<div class="diploma-emblems"><img src="' . $emblem_url . '" alt="Emblem" class="diploma-emblem" onerror="this.parentNode.innerHTML=\'&lt;div class=&quot;emblem-placeholder&quot; style=&quot;width:80px;height:80px;margin:20px;background:#f0f0f0;border:1px solid #ddd;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;color:#666;&quot;&gt;&lt;div&gt;' . esc_attr(substr($diploma->emblem_value, 0, 3)) . '&lt;/div&gt;&lt;/div&gt;\';"><img src="' . $emblem_url . '" alt="Emblem" class="diploma-emblem" onerror="this.parentNode.innerHTML=\'&lt;div class=&quot;emblem-placeholder&quot; style=&quot;width:80px;height:80px;margin:20px;background:#f0f0f0;border:1px solid #ddd;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;color:#666;&quot;&gt;&lt;div&gt;' . esc_attr(substr($diploma->emblem_value, 0, 3)) . '&lt;/div&gt;&lt;/div&gt;\';"></div>';
            }
        }
        
        return '
            <div class="diploma-template ' . esc_attr($diploma->diploma_style) . '" style="background-color: ' . $paper_color . ';">
                ' . $emblem_html . '
                <div class="diploma-header">
                    <div class="diploma-title">High School Diploma</div>
                    <div class="diploma-subtitle">This certifies that</div>
                </div>
                <div class="diploma-body">
                    <div class="diploma-text">
                        <strong>' . $student_name . '</strong>
                    </div>
                    <div class="diploma-text">
                        has satisfactorily completed the prescribed course of study at
                    </div>
                    <div class="school-name">' . $school_name . '</div>
                    <div class="diploma-text">
                        and is therefore entitled to this diploma
                    </div>
                    <div class="graduation-date">Dated this ' . $graduation_date . '</div>
                    <div class="location">' . $city . ', ' . $state . '</div>
                </div>
            </div>
        ';
    }
    
    /**
     * Get diploma styles
     */
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
    
    /**
     * Get paper colors
     */
    private function get_paper_colors() {
        return array(
            'white' => array('name' => __('Classic White', 'diploma-builder'), 'hex' => '#ffffff'),
            'ivory' => array('name' => __('Ivory Cream', 'diploma-builder'), 'hex' => '#f5f5dc'),
            'light_blue' => array('name' => __('Light Blue', 'diploma-builder'), 'hex' => '#e6f3ff'),
            'light_gray' => array('name' => __('Light Gray', 'diploma-builder'), 'hex' => '#f0f0f0')
        );
    }
    
    /**
     * Get US states
     */
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
     * Save generated image to server
     */
    private function save_diploma_image($image_data, $diploma_id) {
        // Remove data:image/png;base64, prefix
        $image_data = str_replace('data:image/png;base64,', '', $image_data);
        $image_data = base64_decode($image_data);
        
        // Create directory if it doesn't exist
        $upload_dir = wp_upload_dir();
        $diploma_dir = $upload_dir['basedir'] . '/diplomas/generated';
        
        if (!file_exists($diploma_dir)) {
            wp_mkdir_p($diploma_dir);
        }
        
        // Save image
        $filename = 'diploma_' . $diploma_id . '_' . time() . '.jpg';
        $file_path = $diploma_dir . '/' . $filename;
        
        file_put_contents($file_path, $image_data);
        
        // Return attachment ID
        $attachment = array(
            'guid' => $upload_dir['baseurl'] . '/diplomas/generated/' . $filename,
            'post_mime_type' => 'image/png',
            'post_title' => 'Diploma ' . $diploma_id,
            'post_content' => '',
            'post_status' => 'inherit'
        );
        
        $attach_id = wp_insert_attachment($attachment, $file_path);
        
        // Generate metadata
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
        wp_update_attachment_metadata($attach_id, $attach_data);
        
        return $attach_id;
    }
    
    /**
     * Get file size in human readable format
     */
    private function get_file_size($attachment_id) {
        $file_path = get_attached_file($attachment_id);
        if (file_exists($file_path)) {
            $bytes = filesize($file_path);
            $units = array('B', 'KB', 'MB', 'GB');
            $bytes = max($bytes, 0);
            $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
            $pow = min($pow, count($units) - 1);
            $bytes /= pow(1024, $pow);
            return round($bytes, 2) . ' ' . $units[$pow];
        }
        return '0 B';
    }
    
    /**
     * Get success redirect URL
     */
    private function get_success_redirect_url($diploma_id) {
        return home_url('/diploma/' . $diploma_id);
    }
}