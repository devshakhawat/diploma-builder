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
            // For this function, we'll verify the nonce differently since it comes from html2canvas
            // We'll check if the user is logged in or if guest creation is allowed
            $user_id = get_current_user_id();
            if (!$user_id) {
                // Check if guest diploma creation is allowed
                if (!DiplomaBuilder_Database::user_can_create_diploma(0)) {
                    throw new Exception(__('Guest diploma creation is not allowed.', 'diploma-builder'));
                }
            }
            
            $image_data = sanitize_text_field($_POST['image_data'] ?? '');
            
            if (!$image_data) {
                throw new Exception(__('Invalid diploma data provided.', 'diploma-builder'));
            }
            
            // Create a temporary diploma record
            $data = array(
                'diploma_style' => sanitize_text_field($_POST['diploma_style'] ?? 'classic'),
                'paper_color' => sanitize_text_field($_POST['paper_color'] ?? 'white'),
                'emblem_type' => sanitize_text_field($_POST['emblem_type'] ?? 'generic'),
                'emblem_value' => sanitize_text_field($_POST['emblem_value'] ?? 'graduation_cap'),
                'school_name' => sanitize_text_field($_POST['school_name'] ?? ''),
                'student_name' => sanitize_text_field($_POST['student_name'] ?? ''),
                'graduation_date' => sanitize_text_field($_POST['graduation_date'] ?? ''),
                'city' => sanitize_text_field($_POST['city'] ?? ''),
                'state' => sanitize_text_field($_POST['state'] ?? ''),
                'user_id' => $user_id
            );
            
            // Validate required fields
            $required_fields = array('school_name', 'graduation_date', 'city', 'state');
            foreach ($required_fields as $field) {
                if (empty($data[$field])) {
                    throw new Exception(sprintf(__('The %s field is required.', 'diploma-builder'), str_replace('_', ' ', $field)));
                }
            }
            
            // Save to database to get an ID
            $diploma_id = DiplomaBuilder_Database::save_diploma($data);
            if (!$diploma_id) {
                throw new Exception(__('Failed to save diploma. Please try again.', 'diploma-builder'));
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
            // For preview, we don't need strict nonce verification as it's just for display
            // But we'll keep a basic check for security
            if (!wp_verify_nonce($_POST['nonce'] ?? '', 'diploma_builder_nonce')) {
                throw new Exception(__('Security check failed.', 'diploma-builder'));
            }
            
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
            // Basic nonce verification
            if (!wp_verify_nonce($_POST['nonce'] ?? '', 'diploma_builder_nonce')) {
                throw new Exception(__('Security check failed.', 'diploma-builder'));
            }
            
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
            $emblem_path_png = DIPLOMA_BUILDER_PATH . 'assets/emblems/states/' . $state_code . '.png';
            
            if (file_exists($emblem_path_svg)) {
                $emblem_url = DIPLOMA_BUILDER_URL . 'assets/emblems/states/' . $state_code . '.svg';
                $emblem_exists = true;
            } elseif (file_exists($emblem_path_png)) {
                $emblem_url = DIPLOMA_BUILDER_URL . 'assets/emblems/states/' . $state_code . '.png';
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
        
        // Add watermark for non-logged-in users
        $watermark_html = '';
        if (!is_user_logged_in()) {
            $watermark_html = '<div class="diploma-preview-watermark">PREVIEW</div>';
        }
        
        // Get emblem URL
        $emblem_html = '';
        if ($diploma->emblem_value) {
            if ($diploma->emblem_type === 'generic') {
                // Check for SVG first, then PNG
                $emblem_path_svg = DIPLOMA_BUILDER_PATH . 'assets/emblems/generic/' . $diploma->emblem_value . '.svg';
                $emblem_path_png = DIPLOMA_BUILDER_PATH . 'assets/emblems/generic/' . $diploma->emblem_value . '.png';
                
                if (file_exists($emblem_path_svg)) {
                    $emblem_url = DIPLOMA_BUILDER_URL . 'assets/emblems/generic/' . $diploma->emblem_value . '.svg';
                } elseif (file_exists($emblem_path_png)) {
                    $emblem_url = DIPLOMA_BUILDER_URL . 'assets/emblems/generic/' . $diploma->emblem_value . '.png';
                } else {
                    $emblem_url = DIPLOMA_BUILDER_URL . 'assets/emblems/generic/' . $diploma->emblem_value . '.svg';
                }
            } else {
                // Check for SVG first, then PNG
                $emblem_path_svg = DIPLOMA_BUILDER_PATH . 'assets/emblems/states/' . $diploma->emblem_value . '.svg';
                $emblem_path_png = DIPLOMA_BUILDER_PATH . 'assets/emblems/states/' . $diploma->emblem_value . '.png';
                
                if (file_exists($emblem_path_svg)) {
                    $emblem_url = DIPLOMA_BUILDER_URL . 'assets/emblems/states/' . $diploma->emblem_value . '.svg';
                } elseif (file_exists($emblem_path_png)) {
                    $emblem_url = DIPLOMA_BUILDER_URL . 'assets/emblems/states/' . $diploma->emblem_value . '.png';
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

        // Dynamically adjust font size based on text length
        $font_size = 56; // Default font size
        if (strlen($school_name) > 20) {
            $font_size = max(30, 56 - (strlen($school_name) - 20) * 1.5);
        }

        $signature = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="290" height="111" viewBox="0 0 290 111" fill="none">
        <rect width="290" height="111" fill="url(#pattern0_37_4)"/>
        <defs>
        <pattern id="pattern0_37_4" patternContentUnits="objectBoundingBox" width="1" height="1">
        <use xlink:href="#image0_37_4" transform="scale(0.00411568 0.0107527)"/>
        </pattern>
        <image id="image0_37_4" width="243" height="93" preserveAspectRatio="none" xlink:href="data:image/png;base64,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"/>
        </defs>
        </svg>';

        $signature2 = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="290" height="119" viewBox="0 0 290 119" fill="none">
        <rect width="290" height="119" fill="url(#pattern0_37_2)"/>
        <defs>
        <pattern id="pattern0_37_2" patternContentUnits="objectBoundingBox" width="1" height="1">
        <use xlink:href="#image0_37_2" transform="matrix(0.00441231 0 0 0.0107527 -0.00300334 0)"/>
        </pattern>
        <image id="image0_37_2" width="228" height="93" preserveAspectRatio="none" xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAOQAAABdCAYAAAC1ksowAAAQAElEQVR4AexdB3wU1dafXrZn00WkgyAEJQKPFkIqISEEMEhAkCZSBOUhAvKeiA0VFRXpPRApARJSSCMhBghFQEVFnw0eLaTsJtunz3cHP3hRCDVAsuz+5mbu3HvuPef87/znttkNAnk+HgQ8CDQYBDyEbDBN4THEgwAEeQjpuQs8CDQgBDyEbECN4THFg4CHkI33HvBY7oYIeAjpho3qcanxIuAhZONtO4/lbojAQ0fIxMRENDQ0VBMbG+sVHR1tjIiI0MfHx2tjYmJIN2zfB+JSYmKo5oEodgOlbk9IhWghISFNIyP79YqJjXy9psacSdPkDzzPXXA67SYIkmocDtsZBIEK+/ePmh4bG9FeIa0btO0DcaFf/37tTCbhRExMxFMPxIDGobROK5E6cxp5BugFA8LDQyNY3rnQP8B7r9Pl3O+wO15BECRAEIT9gsB/ptVq5+I49i+KolOBuwYQPpNl7CQg7Rvh4b3bgmvPcZsIEDA6SaVWt5Flofo2i3rEAQJuR0iFiKGhvUYimLyUVlE7AeGGVplMX6tVmlGIiuiSl1fQpaCgcFRRUfHcPXty38/JyX83O3vPpKef7hYkSVBfGJa2kRT+htHHNys+PrYfwMhz3CIC8fGRj2g16ud5Qdim1fqeu8ViHrFaCLgNIefPh5CIiD6DUFxeRlLkcgxFA50O59vAwZiQ3iWj8/MLU4pzis/X8v0v0QULFkj5+fkl2dn5o3AMe47neH9aRWXGxvaP/oug56JOBGAUf8Zut3tBErQmNTVVrFPQk1EnAuB+rTOv0WR06xbUorik16cSBK8nCKKtLEHzRAF+rqio5KOcnMJTCxaAW+TWvZHT0rJSZEkeKoqiA5By/ZAhcV1uvfjDKaksjGEYlkSS5PcqFXry4UTh7r1u9IQMCek1hFbrshAYGQXmhp+JPD88P79oaWFh4R93A8/u3dl7RVmajOO4v93unDNyZIzubuq787KNoyTH2XqCUcU/IBhOTkvLr2gcVjc8KxszIeGevbq9iyDoTkhCGDA8HVhVUbMwP7/4BwCzBMJdHxhM5thtzncoSpVoNkPP3HWFblwBQdLhFEVxNoet1I3dvOeuNUpCBgcH67v9I3gTx3Gv253WRRjGDjhy5PiBU6dOcfWJGJgHuWBY3AmGwae1WvWLYDvksfqs313qiosLa4IgcCTLMvv0au//uItfD8KPRkfIHj06NwH7iKmIjIyURHFci2Zt5hYWHim/V+BlZOSe5EV+GcM6uzGMtfe90tOY64Vh/GkwUglCYGxPWloa2NttzN48WNsbFSF79+7WFoHozaIgRGI4NuTEie83gF7snq/moTB0QK1WVyEY3gf0ksSDbbKGp10U4e4wDDs4ifvmTqyLi4t4rHffHkmRkX2TQvr1HhEZGZbUo0e3Ef37hw8PD+8zPDT0/wOIh4eHPKukKeeI6H6xUVEhTe9E5/0pc/taGg0hFTJCEPqRwcvQB0bgpAMHDqUBd2UQ7vmBolXf2W32fASG4xFEaH/PFTYiBVFRUS1A7xgHsNkPCZgyf79t651O9l1Ikr90upgvwaJcCsuBsySkWKzWLS6G3QKuLwee5bYwLLuVAWeW47ZyDJvFSdLo21bYgAs0CkKGhHRrgWG0soDTt9pUPbZJk8eUN2vuG6ypqYdcNE2VgmX9R1hWaH3fFD8gRWAUgCqvHN6KelmW21MU2cFisxzIzs6+o7dzvL0DJuIY2pHAscsBReCOGIp00mk1HUkCV0IncO4EZDopMspZyUdgog2Naz+6FTsbi0yDJ2SPHj2MggTPoWg8EkGkl0uPHEm5H8PUvzegAImnwL4kOIQWIA8GwS0PsGCGmysvJTGM7dmbOai8jCGKfFdASieOEodvJl9XPmhP1759B3+sHUpLj/2Ql7fvclpxcekPV4IicyVeXFz8W05ODltXvY0xvcETkqTRKbIkDa82V8+vqKj5EoBcL1saoJ7bOkQW/g1BkAsEQQTFjohV3nu9rfKNRdiLogIJHJ8rCVKfm9n83Xf9H9NoNPE8L3yDouSxm8l78m+OQIMmJJjYJ7AM+08ERVYhCLGqvrc1bg7P/yR27959juf5XwkCD8adov5/OfUUayDVoCTiA4bm7VAU/vFmJjkcbDMwZAgSRaE4IyPDdjN5T/7NEWiwhOzatWuABIkfi6L4tSyiiw4dOuS6uTv3VkKW5DJBFL15GKXvraYHV7sow4+DBw8Mo9h3N7ICzDMJEif7APIKgiDtvZGsJ+/WEWiwhGRY+yeQDHmJCPxqaWlpA3kVS7KA+RKJiih+6xA3Hkll/ogiSE/wEDSrVPD3N7KcZVkvXuCHgtXRH3U63ZEbyXrybh2BBknIf/zj6RjgQhLPcTOPHzp+wxsDyN23Q5IgKyAkIUksdt+U3kdF3t7eBAxD3UD4PTOzuOpGqq1VF31VKvpJlmUywaIMdyNZT96tI9AgCVljqXkH3PiHW7X6PvnWXbn3kmBBxwV6D4gkyQaJ290i4HQ6CQiCn8Aw/KY9nkpniKmuruYFhsuAHvZPPfrf4G6sLl06P4vj+BNgX+Gl1FTonr+FcztYiqKkAg8KEdy4Dcqu2/HhRrIqFdakoqJCxTLM0RvJhYaGYogMj8Ax/OTekpI7ejvnRvU/zHkNjpAIhr4oSWLJ99//fLyhNYwMSVpASJbSafmGZlu92CPCIUajUYZk4YYk0+lwP5PZ9CQMI1vrRa+nkqsINChC9u3bs6so8EEYijWooeoVtERJ0FM0zWCS6JaEFESuK45hjhqn8OsVn693tltdo0C6JEiM8voiiHqO+kKgQRHSZrP1U5EUD8PEgfpysD7rIUnK3261mR0OO1Of9dZVV0JCgiEqNqoj2GK4L/ueKrU6GIbh348fP37DBw6MIOM0GnVpUVHp73XZ7km/MwQaFCEpgghCUPQPmqatd+bOvSs1aNCgpqIgtiZp6iRB6Cx3qulWy02cOBFnWXsShWGbnM6avrda7m7kampq2oqyfOJGdYSH925ZWVkJ5KC1N5Lz5N0ZAg2KkBCCBkiidB5BkBs+oe/M1bsrJUlSS0mE/HmW/75z58719sAYNmxIv5HDh44DveBfflz49MXTzTmem2YGczWWZ33uzvqblw4PD/eutlhIUZJP3kha5KVxYLWZg1E2s7ac8l5rWFhYs4kTg+vco1V+QDk8PDQiLq5/b2VhqHZ5T/xPBBoUIRGwSgAWTVBBEMAi658GNpS/FIW3AA8KHIaxM8ov1NWHXcOHg15XFD7hRHEMRQmq2nWSEPx4WVlZe61Gc1YW4Z9q592LuIzynQHRIBRHb/iGjtPlmqDWaHILC4+aattx5EhEiFZDLj5zRtuudvqVeEREsL68nH2JpqkdrMv1sb+/V5sreZ7z/xBoUITEMfwMiqHtOY4j/2diw4hZrZYngV1m8MA4dysWKV9fio3tHw3mga3qkgeLI+NcTmdHURTXtWwZdHUjPjGxB11VYeqFISgE9BY7ndy3ddVRb+kS1A08CCEE4er8TmOvXt0jeVHwh2Fp+d/1Ah/6gPbrSSLUNa8VKr2hIFDPoDA69/ffftNDMGysulhVZ0/697ofpusGRUi7014IGrY5JHIhDakRYmPDW1KUuq9KRR8GpLzpQkZ4dN9+DOPIq66uyXU4bKOv50tSUmJvCIamwxCcLsvOzNq9bmUlqVarVd29vb1FiqIz78d7vAgMP2UwGJwaTYD5evYqaTIsT8TAKiwEkcXKde0AphptXQxTiVLw9co/DlbO3yJIQtekSRMZgZFctZfX6drlG0P8ftjYoAgpSWiag3EeRkl8SVBQkPK9w/uBwU110LSuE9iOaS2KUimCIM0iwsPSoiLCXr1ewb59+7zAOYWdAi/0ZVwuSOL5a27QqKgotdlkHothOKGi6FVpaYV/Gf7hOE5AMNyGIMiDRmNA9vX01HeaJMmt1Wr1mdTU1Ou+9NCzZ08/rUY7EGz7rCsuLv7LKnNMTB9flUrTHIKRUzYbV1PbNtCOalkSXuB58REExc5ynHCOE/gsz7dDaqP0v3iDIuTx48edOEqDxuPBeobz6w4d2sX/z9QHFoNlWVK+hMvBMnxJ4Nh1drst4Y/Tpxf16dMjvLZVERH9hqIo8qlWq/21adOmJRiGuSAUz6kto8RJFIoEc6mxYLj6eYegpwqVtNrBYqkMtdvtTew221FAEFftvHsVr6mpeRSS5Tp7fwkSnr148SJBk9jiv9sgiqi/IPCBYIh92mg0/oWQGo3Gh2GY5yVJgmqqa7Qcw6yWZfQan/9e59+vIyJCnurWs0vY39Pd7bpBEVIB99ixY39gKBUiCNIJmqJTg4I6Fnfv/vSo7t27+wcHB6vAaiSqyN2vkJAwoDPHsCNJkjqOYFAPhmUe1Wl0gwHhBAzBnr9ix8CBUY8De98A4ZJOqz1abbZ0U9HatWAIfvGKjHIeMmBAMwTBZokcfwoj0M21h6pKfkxMjC/n5BezLA/hOOWrpIEAg3BPDxIn/KxW+y91KIH1et04MNw8YjI5rvl3DBhGqEE52ulkzOAB8pceVuSYuAsXLujNZjMkS3KO0dd/DehhBSB/9Rg4MNQnJjpqcXR05EyQeF1fOU4aKjJSYb+QkDof0r169dJG9+v3RI/uXV4BI5XdvXr1OBnc5ckz3bs+fTIsLHSe0suD+hv00eAIqaAFesqzv/72R5QkS2NIgoBEQVrqsll/NGjU+00Vl3aE9Oq1ODosbHZEWN/JURH9xkVGhk2IioqYER0ZPjMqKnxuVGT4eyD+bnRUxOLYmOhVl8OA/mviYmPWxsfFrh00MHZpfHzcRwnxcR8PHRz/4eBBsW8MeyZh7nPPDXszcfDAMMUGJSjkh2UohAdPfxmCvkdgpB/YJz0IY9gRGIK/lmT5H4qcEmpM9qdNJtPjKIrgYJ9uLMsy+9Va9WJw89mVfCUoixusIMQ7nc6uMiR9lp299++rp7DNbH4fRVEfPz8/E4bjQfHx8e2ioyMmDBo08MW4uLh78ruwip/AdvAAQP/y8FBsVkJYVN/+YMgeJMrSStA212xJgYcOAXpAYC7+l6Gs4i8vSlGgl4S8vAz7EAxf2LVr16tfpRswYEBAdHTkKIdd/tVkNr0CRgzvRYSFTVUWtRS9tYNe7/UeeNgVVlRVbO7Ro0d07bzevXt7hYb0epYkkNwqi+k7L6P3J5AsP6lWqcseCQysFEShk8ViSaQotHntcg0x3iAJeQWob745ueXrYydCvbS6EEpDvyvD0i+yLD5KU3isw2mbyrPsGy6Gfd/ldL3tsNtn2B2O6Q67YyK4TmJYVgnxIC3carVFgpsmAgydwh1ORxTLcQlgUPw8kBnP8dyLVptNKTvHVGWeYXU6p13Rj/B8cxmCX9TpdL/gOOIEQ9fWOp12d15eXhmEwEU2m61Nj95d+4OHwLOcwI2yWq1EtblGh6LoZowgxjscjrPgptQkXeC17AAAEABJREFUhCYY5s+fj2CS1AnomwXBcCaCq655DzS0Z8+hLp4dQ2s1byMQ8irQ14FjXXkkQa40m0wrMBROHThwwJjY2NjgwYMHe1+x827P58+f1/M8D+5h2PL3usCoBAd7r6NUKvV5VhKuO9SEYYgBPjEwCl/9aZPo6B5G4P9QSZYifP38/tBoDbNLS0t/PHr0KA5GAa0iIvoNgyA5g3G51mEo+lOLlq2jcQLPAw+q91wur9cHDYrpAB4UV1dsMzMznYFN1EP9A/x/kyVxZUhISFiHDh0I0Cs+AfHsx6Ikr/LzC3gyqHOXNU0Dm3Zr93iH9t4Gr7dhBKmiabpCpaU/Kiraf8OX5v/u+4O4btCEvAJIQXHxt0ePnlhcVHwgaV9JaVdWhIIwCump1qv7aml1lEqt7afVGULUGl1vELqrNNpgLyPWBTRQRz//wPYoTrRnWP5xnKDaESTdGsi2pmjV47RK0wrFqGZGb/9WFI08rjf4dGzWTDXyil6H4IoUBKGt027fDG4cP5Zhz0kSfHnjHEGkFJVK/YtWrU0RJWGlLMtRvr6+kI+PT1aTwMBjNEkOUFGqf5E4+Y6JNy08fKBkHgTLs0RRVNEk9cnfFzViIiOjUBzb4mXw2hcYWPOeg2GyAUM+kSHov4IovkWR9EywBygJgriUILBsBJE3JT47eMHQYfHjhg1LSBg2bEjI6NFJQYMHD2ibkJDQPCkp3v+KHzc7C4JNrVKpIM7lutqbXynj6+vV3aDXR9lt9vUOk+PSlfTaZ4YRy2AYOU+SRPTAgTFh4IERjWGGhZIkrIFhmAYrxT/5+Oi5cPCBYXmqILDJNK3aAEESoTcYpui9vEO3bduWT9OaySiGb0cRZAaGkLtY1vFJXNyAfz0zeNBsMKJ5TRbUkzQqzany8kvNQD2r/fx8/o3I8gqL1TaWJEmdXq//jsAwHaEmDXardShKYKvAQ/IfQMfc/cWlm2vb3FDjjYKQfwevGKzyFRcfOb9nT+EvewoKvs3Pz/85Nzf3DDifA6EC9GDm9PTiGjCfcYHA/SlfzOTk5LBKAGmutLQ0kxLS09MVOfOuXXllW7duPbdqVaZT0TdkyIBmMITMRRDkMK3RHJEgubsEQwdJkv0NAp+9e0t+MnrrEsETeA7PcVO0Bs2HoAevAsO6ARUm0/sM63pXkvkxECz0Jkj0ifKqqjfPX7qU5GScUo2tpl2/fr07R0VFNR0wIKITiE8RZD5FkIWfEUx+KTX1FLd///7Kwn1fva7TGcL27Ml9s6Cw8BNIjQyCUPl5URZ2S5DUDGw1zIYkaDkYK27heDbT6XLmUDS9R60hMiQZTU1KSuwPTL3pgfIYKkmgRhiWaguDHooQZWkkC4YUarUq+3rDVUUe4HsekCxVEPkuCIp8iaDSVpZzDaRUxBKdTpPqdNn6XiwrS9bq6I0c75qt0ap5hnHM5TgxPiMjazVoD06pJysr6wKOE7NsLtsLMiqdEkShP04gr8ooNE+ShXl2xjETI9HQ5i2b/YGgkO8fp3/7l81h69K8VcsLgHhQSUlJjz05WUlnTp/ewEvs6rKKMlqjpV9SqTQblfobQ2iUhLwfwDqdzMsOhz3QZncstNsdvoCYzRiOLam9RZGRkXsyL2fv6pKSQ1+qKHgRQZGTeZ5/C4Lk12VRngDBaFKNxZ6EStBEDMMdBIFBkiybKZp6B/R+6QQG75ZEOUOtUn8Cw/AJjVYzJj+/+Ofa/oGbVbxynZ+WX5GTlb8jIz37RQyRYmRJjoFkaDzLuN7AUGw5iqBZTofza5ZhzjIM42QE9mrZK3Vc76xTqcBwXIYogtDWzq+pqeqDoegzMAJvEwT4hv+zQ6cjt3EsP5lh2S1A/4c4hicd3H/kdQyD/y1D8PsEQXxld9iX4RT5ol1gRhQUFH+2d+/es7X1KfHs7OzqooKvtrgc/BgcQ2NFSIytNlfHgfriUQRJcNjtQ2AISuJFcWrrNq33gN77PZvdNkcUpXNgpRgCPb0EyEnYbbaNOIqOyMrKT6mNoaKjIYeHnpCxsVHBg+IGTB0yJLbllYYKD+8TxLDcDIIkt+I49RWKI51BHoNC8F/IAtKuHpmZxVX5OYU7cnLyPs3Ozl2VX1iYDnrtQ0eOHPn1YtWl1uaaKi0n8A6CJGY5nPZX1Cp6syiLRwSRT5EheQzo0V7Izs4/frXCm0RSU7PO7tqVsW/nzozNO3dmLdqxY/ecbdt2vejl5RoLw+RIAteMSEvNKLhJNZezOYIwUzghgWH21X/jHhcX10RFU9NxHLeBm2RbTk7ODd/fVUYYubkFG8AD6p/5+fsW5uQUfKVUXlh44JfSA4ffrWxaPXNvfvG7OZn56QUZBReVvBsFRd/u3TmnMnblHCwqKgEPwsyvsrPzS/Pyio4WFe0/qlXpt0OSMB3FyRWiCOUZvfVvBAT6v633Nk7BCWIYmBK/lptbeOhGOurIe6DJAOsHqv+BKE9MTEQBAWPDw0MKJVE6wPLsbATBAq4YgxHkKlmWbYwsgN4OMmpUqj7g+ne1GquTkFfKXu+MYeR00ANCOp2uLDCw6Z6DB7/eTqu93oYgfLaXl/9b4Ebber3e4np13Sxtw4ZiBvQIFhCueSGhrrJgyClotLpfLBZLhLL4BIbSapfLNo5h2DBe4JY1bdrylh8UQIcMwjXH8VXH+WsS7yIBEJYtKir9vbCw0KQM7wEBN/v6Bn5w9NCxVXv3FhcDPC13Uf0DK4o8MM0PSHFkZOiTF8v+u6+yypTOC3xLMHScTyDEIBgmLn9LPjom4oXff/utuwzDrxTnFf9mNOqa252uzhiOHb2Tf0QaFBTUQpL5fiJ4jAuidAoQ5fIwEpw5cFNZlfMDguIvagkMWckwTPDhwyVvsy77EjDs/DcYDu9AIHLtqlWr6pVMf1FcTxfKQyU/P98BqrvuAwGkN4oDaRRW1o+RcI9eXRdUmaq+QVH0CZqiX0RgslNOXuGHGTk53wBiuOLiIh6zWizLAgMCi/19AjcqagWBb6FSqQwQjHyvXN9uUKmI0S6XCwO9I4Sj6Le3W/5+ybdu32kppaYzfv7pP7MqKioG+/r4fKIzeE/LBnO6+2WDRw8EPRSEjI7uE9g3pMeR6irTGxpatUStklqAec468FS9usyvDNU4QVoNgw9Na59NTU0VBw4cqCJJqi2OYTUkht02IefPB/jK8JhmTZtDAsdLKpK66a+5PaibUukFDx85MahFy7a65i3bBKTtzp4DMLiKz4Oy62HTW2+EHD9+RLOpU0d7T5sWQz733HOB06ZNIydNmuQ3ZswY6pVXxhhGj/5zI3vMmOHNlTncxImJ+okTJ/qAODF27Fhf5TxhwgT/GTNm0CBOjxqVePmtlFGTRvkp10o9IyaP8AJx4uWXX768x6aUHz9+vBE0GqzoV/LGj080KnWPGhWlBpvnfglx/UPB/OI7lmWDfbyNoyGEfDsn54gNyAZMnBiMg7Nekfvuu2PTTVWmKF8/33lGoxEbPXq0d9OmTRGCIIv1XobxNK0rB/pUr7zyimEysAMCn/HjxzcD5dGRI0fqJgE7Fd/HjEkMUIgcHx//yNFDYdGQKD1m1BtqwEqlLEJSWUJCgmHEiBFeCkZTpiRqRk4Y+ajiGwgBCg7z589HJk8e21LBb+bMiT5z5kz2mjJlimbWrCnA3on4zJlTmyl5r732mnb27OkKRvDrr78euGjRq+qpc6d6g/LGN998UzVz3sxmwERo3rwZTebPf0V5MYGYN29eUyXvnXfmNXn//dl6UM5fyQe6qZdmvvS44l9goFbTqlUr71GjRqknzZjUREkbN27cI9OmjdQBvbrx45+7/N+/Zs+e/djkyZMv26a0M8gjJ7w8wX/+/FBMwQKUU9oWnTBhVAulfuXeUAKoSztlypgAgBto9+FNE2ck0kp7jR0L4mBur+Cn6FYwAjJGJZ6YGNcE+AIreQkJoYbg4GB8yJDowDFjQqmYmBjl3tEouA4eHK74jwD5x5S2HTkyRgfmw8r9QygvwIeGhlKhocE+4KwB6eoePXoo9ULgOiA+vpdWaTeQ7geuqaionko5NCIiQg+uNUA/pMSV870M9UbImhr7dFONY7goPhoMI8KCGkdV1ypz+SxeZnpVmpzjMUI7RbkJq6stK2k9/YQgUGMliXnNy0vbTZKZ13U6VVdZ5t4xmcqjdF7qaIvNsRg0Ki7Z2Df0enooikOJKhGbqzeq+1ZXVywEDd0ewcT5Kgp7ecKEkU0EXl6sVhNdWBc8jefJRIah4wTetcXJMvt4lkXUGvWzOoPBiqLizIEDo5ozTuvES5f8Q8F+1SiOc7wJwfBinVZ3ThbRZQxjm4thcqLFaekIQdIoiRdrSBKZA8NiOMib6GTgf44BN5XJXL7Wy0vdQaXBx9odPFh6fzTY5RL+SVFYZxSFp7s4YQGMocjZC+cNRi+jGezlPYOiULjZXBmKIALYViF6ooK0AMOktgQBzcYI6KWyst/9BQkCK4dMMM8js+xO+UWUkAYxjDQPU8lPsJy4HEGk7sCOJIbhV8yaNa2l1WZaXFMDxxsJzescZ5/lclnibCbzckAavcPFfuFwSK8wTHU3lrevdLlq+joc3AcWi5Agyq7pDCPO9vXVNqk4f2ElQcAdtAbfsYLkmhsQoH+SqbbNBUPubpQKfUsQ6HhQdyzLcUuUdqmsPP8ORsjDcBKJZzjHGxznCpEd/DsXLzbvKIr4bEFgpur1mM+FC5c+Bdh15AR0qiBgzwsCG8Ew8qs+Pj6PW6yORWil/DSwJcruZN4SRTHQbmdn2BlLH1awPyvBXJIosj2An/PAQ9NYZbbOkiR6SMvHW7YxVVtn2Wy6jjguT6ipMfWkKGQgx8Fjjx/fH1hWVv5BWZl3K5OJjUVR4QVLZWVbnoHG4BDfEUc141jW0dteU9UdlrnZCrkkiZvkcODhFovpH4zDMk6joZ5CIGqy3W5uh8j88xoNmaSQVZLYwYr8vQxIfVUOlt9nbk3ZuXTZstWlm5K3Tty0bsuBHdvTZqVs3FqYsmn7x+vWJL+9efPmMrCUHZ28JvnkunWbPl2zJvm1VavWHdi4YcuMtWs3Hly7NvmF5OQvd69dtSE9I33PUGUYlZKy/SUgt3nt6g2b1qxKfm3Nyg0Fyclbxq1Zs+mnFcvWTluydOX8NWtSzm9M3jJkw4aUw5u/3L5g/frNaymKqKQoMgjH8B9RHAstLNy/Y8+evbuLi0vnZGbmn87MznsrI2NPASyh2wmKbsvzvE3jpRmmvEGTnp49Ddi3IgXUB+p6fsWKNUUrVqydunLl2kxw/nDj+pR/b1i24VJ6WlbEqlUbvl+9csNnmzdt/afi+7Ztaa+lpqYdYhhuvlaj0QUEBJwiSRJyMS4wVS2ct3Nn+s7c3IK05OStczZuTMlP3rBl/BqAB6hnxuqV6+evWrW5DJyjlLo+/3zF7C8+X/H+kk9XpCxZsmLask9Xf2VYCScAAAySSURBVPvFkhUDPvtsacnnny9f9dlnywcsWrTk9y+WrBz+7ruLtry94IOZCxd+PPeDDxZvX7Vi/YAPPvjA8uknywZ/9NHnb37wwecHPvrwM5D2ac7ChYueA3IbP1i4eN7HH38+Xalj+7a0vl98sfLYquXrFim4Llr0xUGA50uffvpF8bIvVk9YvnzN5pUr12/ZvGl7jNIu69aljF7y2YqVny3+4ssN6za9tHLlmoK1aza+sHr1hm9Xr143c9265AVr1mwpB9sfg1auXHcM+PQWOH+SnJyStm7dxleXL19+ctfOzOFbN+/YDzBLTd2WPnbXrl3nd+zImA22a3J3bs9YsXP77qVffrl9b2Zm3pS0tDRTVkbezIyMnHWpKamnvtp36J/gnjsG9oIXFhQU5W/dumtTdvbej7Kyii4cOPB1Emjjn3Nzi7bk5BS9m19c/MPeffsXFRQfPFZQ+NWHhw59nVt69ERR6eHj05X7v6Sk9M3CwuJ0cC4qOXjk/ays3EO5+UULcnIKT+UXfvV5Vlbe6szMTCfYbtmgyN/LgNzLyh9U3SNHJra3VFs/5jiO58CTGpDwut+CV4Y8nOCcKnBsCIHjb1Oo5uv6shlMRduCjepAsDr7+7lz5yCSpG9pT7C+9HvqaZwIuB0hlfkIw7AvUTQdxLPsW2azs7SuprHZtLFajRYMiQ37MIzeBBYxLm9J1CV/O+myLD6FoqhaFEQNTdOQJMEHbqd8Y5Z9ftzIqNGjR/RT5o7KXLCh+KLMPYEt/3/Pg1gDPBq0cXeCl9Uq9oZkeZzD6UomaGlrXe9fJg4a1EFFqT5CEKQCbPrPB2S8dCf66irDMM5WOp0GBnPQp72NhguHDh26UJesO6VPmDA2DBagZ1EE+cRhr8mRbTa0PvwDiy3quLiogdera8CAsGbR0T2Uxb3rZV9O6927t5dBq54Y1rf3ILAQRF5OvMkfsOhDx4SFtbqJWL1muxUhR40a7Ge32yfxAl+Jw9Dy9PTimuuhFRsb60Wo8H9ZrJZHBV6cm5Ky7fD15O4mTafRGsAiBQKGq1owcs64m7oaS1ll9VQQ+Ckqmt6rUquWgmG7GsVxXLEfrJjSY0aMaDN27Fjf8eNHP6WsqI8YMSxk4sSBqueeS+qvkASsECMjhgxpOWTIkEeTkuL9E4YnNB86ND4oNDFUYzBogjEMPRUfH/nI5a9mDRgQAMp0AG3ZHoLQuZJEjgXXusjIyEeio/s9oegMDe319ODwcO/IkJA2Oh1GgoevQ63VzGFd1mkhISFNFZnIyJA2gOwtlLLx8TFPDRkS82h8fHRr5drHxzBHbVDPiouL7qzI3o/gVoS0mBydRUGK4XlxfXpW3tG6AERReWx5RWUSRauXtG9vSq5L7m7SLZbqMofDASlBgqR1d1NXYynrdPIJAs9HnL94Ifn0H6eXWSyWrherK08NHzZ4m1pNDUJItEBNE29JopROk9h8MJLZYbeqloJh/VbQe73/86lTExiB34ci8CpEIl8hJXwLxEubdaxmYnVVdbIkQENxmH5dFORklKbnIbC0jibxZyEIG6BWa7tiGNaf59k1wI5sQMy+KIov4yjiTVyjXo1h2tm+AQH+KrWuiqTV00gSfysuLmakLONfYhicJiPiYATDwGKg5j2NSpfjY9RO0mo0Y3GCDCNJ6vL2CHQfPsh90HFfVCjfMpdRbKgkSRdkWUipS2lsbHSfarP5Q7VGkyvLyLwFC4r/8nMSdZW73XSNjt7kcNgLUQzdcfToN7fzLujtqmoQ8uPBfjCO4mOMRuO5tu3a5jdv3hwDc7azTwc/nWYwGLsbvQwzAgMDvf0D/B7TaDR+Xl6GGj8/P9jP18crwNfva7VKPUytonqArSebQas5zrIsr1WrZBlC5hqt9mU0RXGyIFeIgvgfb4PRX6/Xf+tl8PLx9fcX/P39LF5eXnsJAnvU29ub1ut1h/V6NabX6yWDwYD4+vpeBDqbQxDclaLIs+3atfsKrH4HqNWaAUaj0YRhxPcGrV4yGgxqf39/7pEmj2gea97MS6fTwQiK5CMIUQLdp4/bEJK36f2dDvtQFMWKc3P3/ed6+IFhk29VZWU2AsO/wTCeBOaNruvJXZN2BwnK8vvXx76L+P77nxNBcRkEtz7Wrl1rhlHiDQhG7WVlFQPUGh3M8aL2q/0HxkIIWoiguDeGUzBBqiBBlESWF62SBFlkWXIJPGtGEIigCFzPsM5qJ1exiCKJ3xiXkwOr3+UbiosZF+NggIBAUQRjs1tEGZZckixKHOPkYFSSCQJlcBxFnE5bE6eTmQ3at1SUBQFFIR6CJSeCyDIgKUGSmHDx4jkbBImCJAl4dbX5UZKkXkJR5GeSpCHWxbOSDHOQBGE0TQkysA80HAfCfTnchpCcxHcET1UjmD/uvB5y8fHx/qaq8m9UKpWg0akHp6enX3d+eb2ynrSbI/DCC2OetFqr3z116lQnQRAgQRB/BaV+6Nix004YhjmTqXqey+UsM5nMiCBIgDhOjON5L1ZgW9mcTszlcpWZLdUOgiBOq9WYAAGCSgikhRFJB+qBGBfDCIJA8RKPyjCMgLZWDs7JMzLPClCNza5CEISAYLgM9IxAmlGLogjX1FjY8vJLThAXrTYrawIERDBUa3c4zYLIixRF/8GyrMSyIuVwuECPLNfIksRw4GM2V6Msy2mAfgKE+3K4BSGVxQAXz4CxPnlekpBr3heNCQ19VJa4QpfL6UuQ5KDs7Gt+XOq+gO3OSngeegTDyPf9/ALCwNxx5ZkzZ/iTJ0/2qqqqQmpqrF+QJLnvwoWLeWaz6QIgwOHy8vIAq9VK//7H6a4Xy8s6SAg8UUbRi6fP/reVqQaNYnjhLCBvuclieQKMbGijj3e+i2PbYigh1NgsJ2ps1jKHy3XMbrE5qi3V/2EZV1uHw25xOBwqi8U02iE7SEuN9bQg8GcxgjhZXn6p2ulw7qmsqNRXllcaIRna7HA6TlRUlDe32WyDOc4hAVt/MJlNP/337Ll95eXlnMvp3O6w29tIDHP5NcT70X5uQcijR1NwUZS72B32XwoLC8trAxceHt5SJtEvzWZzWxzFkvLyCvfXzvfE6weBDRs27AELqt/wgJne3r6vBQV17gTDYhcvL+OILVu2/LhixYqK1q3bTLdYbC9SFB3dvv0TIG7dplGrPqJITTdlpXvblh2zn3yqa9jO7enpO3ak54O2is3PL1qiTC12Z+b8Oz+/8LVtO3at8TEGDNu9O7uUcfETMjNzl9mtTFLnTsGv7d69ZymoK7SoqOSjoqyiCwH+Tcbt2VOwNGv3njXe3gGT09Ky1qtV+hij0X9IWlpGQX7uvkVarb7Pvn37kjMycg6COeWI7dt3bgbTnqktWrV7e/uO9FdhhIzdmZn5Y/2gdPNa3IKQFRV6lHG5fPVavTJMuuo1WP7uTBLIOgRBgnV67diSg4d3Xc30ROodgfXr1/8ByHM0JSXFumDBAikvb993gKjMFUVKGsgXlVfvlLjRqJ6P4NjejIwMMKf7U2rJkiXsn7HLf2vPva/GQR2X53TFxX8uyClnpT6lRP6f34lUotBlucsxSImLSjQnJ4etnV77i8xX0sFZvFKfIq+Uu1/BLQgJnsowGPITVqvl6rfk+/bt1QdFsJUAyCdYFzslJ6ewzpVXIOM5HgACmzalnt25fXfeA1DdYFW6BSEVdFEUZ2QZ1vTq1UsbHhryPFgh28DznJfdYZtaWFxyT/YaFb2e4EGgPhFwC0Kq1WoJgHJGq9ONEXnuS1GWl8gy9CskShNLSg5vB3lXhzsg7jk8CDRYBNyCkIcOHWJkSP6C5/lzag39CILCb7GsY1LBvv2Xf/mswaLvMcyDwN8QcAtCAp/kEye+y7M7XP2cLj6yT5+ST4qLD58B6bd1eIQ9CDxoBNyFkJdxPH78eBXoLc0LFkDS5QTPHw8CjQwBtyJkI8PeY64HgWsQ8BDyGkg8CR4EHhwCHkI+OOw9musRAXepykNId2lJjx9ugYCHkG7RjB4n3AUBDyHdpSU9frgFAh5CukUzepxwFwQeRkK6S9t5/HBDBDyEdMNG9bjUeBHwELLxtp3HcjdEwENIN2xUj0uNFwEPIRtv2z2Mlru9zx5Cun0TexxsTAh4CNmYWstjq9sj4CGk2zexx8HGhICHkI2ptTy2uj0CbkxIt287j4NuiICHkG7YqB6XGi8CHkI23rbzWO6GCHgI6YaN6nGp8SLgIWTjbTs3tvzhdc1DyIe37T2eN0AEPIRsgI3iMenhRcBDyIe37T2eN0AE/g8AAP//2LmqlAAAAAZJREFUAwCCqO/Yd7eswwAAAABJRU5ErkJggg=="/>
        </defs>
        </svg>';

        $signature3 = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="291" height="119" viewBox="0 0 291 119" fill="none">
        <rect width="291" height="119" fill="url(#pattern0_37_3)"/>
        <defs>
        <pattern id="pattern0_37_3" patternContentUnits="objectBoundingBox" width="1" height="1">
        <use xlink:href="#image0_37_3" transform="matrix(0.00439715 0 0 0.0107527 -0.0012748 0)"/>
        </pattern>
        <image id="image0_37_3" width="228" height="93" preserveAspectRatio="none" xlink:href="data:image/png;base64,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"/>
        </defs>
        </svg>';

        $diploma_html = '
                        <div class="diploma-container" style="background-color: ' . $paper_color . ';">                            
                            ' . $watermark_html . '
                            <div class="diploma">
                                <div class="header">
                                    <svg viewBox="0 0 600 120" class="arched-header">
                                        <defs>
                                            <path id="curve" d="M50,100 Q300,10 550,100" />
                                        </defs>
                                        <text font-family="UnifrakturMaguntia, cursive" font-size="' . $font_size . '" fill="#2c1810" text-anchor="middle">
                                            <textPath href="#curve" startOffset="50%">
                                                ' . $school_name . '
                                            </textPath>
                                        </text>
                                    </svg>
                                </div>

                                <div class="certificate-text">
                                    <h2 class="certifies">This Certifies That</h2>
                                </div>

                                <div class="student-name">
                                    <h3>' . $student_name . '</h3>
                                </div>

                                <!-- Body Text -->
                                <div class="body-text">
                                    <p>Has satisfactorily completed the Course of Study prescribed<br>
                                        for Graduation and is therefore entitled to this</p>
                                </div>

                                <div class="diploma-title">
                                    <h4>Diploma</h4>
                                </div>

                                <!-- Date and Location -->
                                <div class="date-location">
                                    <p>Given at ' . $city . ', ' . $state . ', ' . $graduation_date . '.</p>
                                </div>

                                <!-- Bottom Section -->
                                <div class="location-seal-section">
                                    <div class="location-left">
                                        ' . $signature3 . '
                                    </div>                                    
                                        ' . $emblem_html . '
                                    <div class="location-right">
                                    <p>'. $signature .'</p>
                                    <p> '. $signature2 .' </p>
                                    </div>
                                </div>
                            </div>
                        </div>';


        return $diploma_html;
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