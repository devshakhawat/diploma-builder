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
            // CSS — Google Fonts (Dancing Script for signatures)
            wp_enqueue_style(
                'diploma-builder-fonts',
                'https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;700&display=swap',
                array(),
                null
            );

            wp_enqueue_style(
                'diploma-builder',
                DIPLOMA_BUILDER_URL . 'assets/diploma-builder.css',
                array('diploma-builder-fonts'),
                DIPLOMA_BUILDER_VERSION
            );

            wp_enqueue_style(
                'diploma-builder-step-cards',
                DIPLOMA_BUILDER_URL . 'assets/step-cards.css',
                array('diploma-builder'),
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
            $is_admin = false;
            if (is_user_logged_in()) {
                $user_id = get_current_user_id();
                // Check if user is admin
                if (current_user_can('manage_options')) {
                    $is_admin = true;
                }
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
            
            // Build emblem URL map from custom post type
            $emblem_urls = array();
            $emblems = DiplomaBuilder_Emblems::get_all();
            foreach ($emblems as $id => $emblem) {
                $emblem_urls[$id] = $emblem['image_url'];
            }

            // Build country image map from countries custom post type
            $country_images = array();
            $countries = DiplomaBuilder_Countries::get_all();
            foreach ($countries as $id => $country) {
                if ( ! empty( $country['image_url'] ) ) {
                    $country_images[ $country['name'] ] = $country['image_url'];
                }
            }

            // Build state image map from diploma_state custom post type
            $state_images = array();
            $states = DiplomaBuilder_States::get_all();
            foreach ($states as $id => $state) {
                if ( ! empty( $state['image_url'] ) ) {
                    $state_images[ $state['name'] ] = $state['image_url'];
                }
            }

            // Localize script with AJAX URL and nonce
            wp_localize_script('diploma-builder', 'diploma_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('diploma_builder_nonce'),
                'plugin_url' => DIPLOMA_BUILDER_URL,
                'is_user_logged_in' => is_user_logged_in() ? 1 : 0,
                'is_customer' => $is_customer ? 1 : 0,
                'is_admin' => $is_admin ? 1 : 0,
                'emblem_urls' => $emblem_urls,
                'country_images' => $country_images,
                'state_images' => $state_images,
                'allow_edit_location' => get_option('diploma_allow_edit_location', 0) ? 1 : 0,
                'papers_url' => DIPLOMA_BUILDER_URL . 'assets/papers/',
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