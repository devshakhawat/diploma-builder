<?php
/**
 * Assets management for Diploma Builder
 */

if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

class DiplomaBuilder_Assets {
    
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    public function enqueue_frontend_assets() {
        global $post;
        
        // Only load assets on pages that use the shortcode
        if (is_a($post, 'WP_Post') && (has_shortcode($post->post_content, 'diploma_builder') || has_shortcode($post->post_content, 'diploma_gallery'))) {
            // CSS
            wp_enqueue_style(
                'diploma-builder',
                DIPLOMA_BUILDER_URL . 'assets/diploma-builder.css',
                array(),
                DIPLOMA_BUILDER_VERSION
            );
            
            // JavaScript
            wp_enqueue_script(
                'html2canvas',
                'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js',
                array(),
                '1.4.1',
                true
            );
            
            wp_enqueue_script(
                'diploma-builder',
                DIPLOMA_BUILDER_URL . 'assets/diploma-builder.js',
                array('jquery', 'html2canvas'),
                DIPLOMA_BUILDER_VERSION,
                true
            );
            
            // Localize script with AJAX URL and nonce
            wp_localize_script('diploma-builder', 'diploma_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('diploma_builder_nonce'),
                'plugin_url' => DIPLOMA_BUILDER_URL
            ));
        }
    }
    
    public function enqueue_admin_assets($hook) {
        // Only load on diploma builder admin pages
        if (strpos($hook, 'diploma-builder') !== false) {
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
        }
    }
}