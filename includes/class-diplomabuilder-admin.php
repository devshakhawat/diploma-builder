<?php
/**
 * Admin functionality for Diploma Builder
 */

if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

class DiplomaBuilder_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            __('Diploma Builder', 'diploma-builder'),
            __('Diploma Builder', 'diploma-builder'),
            'manage_options',
            'diploma-builder',
            array($this, 'admin_page'),
            'dashicons-awards',
            30
        );
        
        add_submenu_page(
            'diploma-builder',
            __('All Diplomas', 'diploma-builder'),
            __('All Diplomas', 'diploma-builder'),
            'manage_options',
            'diploma-builder',
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'diploma-builder',
            __('Settings', 'diploma-builder'),
            __('Settings', 'diploma-builder'),
            'manage_options',
            'diploma-builder-settings',
            array($this, 'settings_page')
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'toplevel_page_diploma-builder' && $hook !== 'diploma-builder_page_diploma-builder-settings') {
            return;
        }
        
        wp_enqueue_style(
            'diploma-builder-admin',
            DIPLOMA_BUILDER_URL . 'assets/diploma-builder-admin.css',
            array(),
            DIPLOMA_BUILDER_VERSION
        );
        
        wp_enqueue_script(
            'diploma-builder-admin',
            DIPLOMA_BUILDER_URL . 'assets/diploma-builder-admin.js',
            array('jquery'),
            DIPLOMA_BUILDER_VERSION,
            true
        );
        
        wp_localize_script('diploma-builder-admin', 'diploma_admin_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('diploma_builder_admin_nonce')
        ));
        
        wp_localize_script('diploma-builder-admin', 'diploma_builder_admin', array(
            'delete_confirm' => __('Are you sure you want to delete this diploma?', 'diploma-builder'),
            'delete_error' => __('Error deleting diploma. Please try again.', 'diploma-builder'),
            'bulk_delete_confirm' => __('Are you sure you want to delete %d diplomas?', 'diploma-builder'),
            'bulk_delete_error' => __('Error deleting diplomas. Please try again.', 'diploma-builder'),
            'select_diplomas' => __('Please select at least one diploma to delete.', 'diploma-builder'),
            'preview_error' => __('Error loading diploma preview. Please try again.', 'diploma-builder')
        ));
    }
    
    public function register_settings() {
        register_setting('diploma_builder_settings', 'diploma_allow_guests');
        register_setting('diploma_builder_settings', 'diploma_max_per_user');
        register_setting('diploma_builder_settings', 'diploma_default_paper');
        register_setting('diploma_builder_settings', 'diploma_single_product_id');
    }
    
    public function admin_page() {
        // Get diplomas for display
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $diplomas = DiplomaBuilder_Database::get_all_diplomas($limit, $offset, $search);
        $total_diplomas = count(DiplomaBuilder_Database::get_all_diplomas(0, 0, $search));
        
        $total_pages = ceil($total_diplomas / $limit);
        
        include DIPLOMA_BUILDER_PATH . 'includes/admin/partials/admin-page.php';
    }
    
    public function settings_page() {
        include DIPLOMA_BUILDER_PATH . 'includes/admin/partials/settings-page.php';
    }
}