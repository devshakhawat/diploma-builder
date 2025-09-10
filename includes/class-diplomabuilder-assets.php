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
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'diploma_builder')) {
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
                'jspdf',
                'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js',
                array(),
                '2.5.1',
                true
            );
            
            wp_enqueue_script(
                'diploma-builder',
                DIPLOMA_BUILDER_URL . 'assets/diploma-builder.js',
                array('jquery', 'html2canvas', 'jspdf'),
                DIPLOMA_BUILDER_VERSION,
                true
            );
            
            // Check if user is a customer (has purchased a diploma product)
            $is_customer = false;
            if (is_user_logged_in()) {
                $user_id = get_current_user_id();
                // Check if user has purchased any of the diploma products
                $digital_product_id = get_option('diploma_digital_product_id', 0);
                $printed_product_id = get_option('diploma_printed_product_id', 0);
                $premium_product_id = get_option('diploma_premium_product_id', 0);
                
                if (function_exists('wc_customer_bought_product')) {
                    $current_user = wp_get_current_user();
                    $customer_email = $current_user->user_email;
                    
                    if (($digital_product_id && wc_customer_bought_product($customer_email, $user_id, $digital_product_id)) ||
                        ($printed_product_id && wc_customer_bought_product($customer_email, $user_id, $printed_product_id)) ||
                        ($premium_product_id && wc_customer_bought_product($customer_email, $user_id, $premium_product_id))) {
                        $is_customer = true;
                    }
                }
            }
            
            // Localize script with AJAX URL and nonce
            wp_localize_script('diploma-builder', 'diploma_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('diploma_builder_nonce'),
                'plugin_url' => DIPLOMA_BUILDER_URL,
                'is_user_logged_in' => is_user_logged_in() ? 1 : 0,
                'is_customer' => $is_customer ? 1 : 0,
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