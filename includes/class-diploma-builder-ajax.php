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
            
            $emblem_url = DIPLOMA_BUILDER_URL . 'assets/images/emblems/states/' . $state_code . '.png';
            
            wp_send_json_success(array(
                'state_name' => $states[$state_code],
                'state_code' => $state_code,
                'emblem_url' => $emblem_url,
                'emblem_exists' => file_exists(DIPLOMA_BUILDER_PATH . 'assets/images/emblems/states/' . $state_code . '.png')
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