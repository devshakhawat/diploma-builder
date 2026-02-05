<?php
/**
 * Emblems Custom Post Type for Diploma Builder
 */

if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

class DiplomaBuilder_Emblems {

    const POST_TYPE = 'diploma_emblem';

    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('after_setup_theme', array($this, 'add_thumbnail_support'));
    }

    public function register_post_type() {
        $labels = array(
            'name'                  => __('Emblems', 'diploma-builder'),
            'singular_name'         => __('Emblem', 'diploma-builder'),
            'add_new'               => __('Add New', 'diploma-builder'),
            'add_new_item'          => __('Add New Emblem', 'diploma-builder'),
            'edit_item'             => __('Edit Emblem', 'diploma-builder'),
            'new_item'              => __('New Emblem', 'diploma-builder'),
            'view_item'             => __('View Emblem', 'diploma-builder'),
            'search_items'          => __('Search Emblems', 'diploma-builder'),
            'not_found'             => __('No emblems found', 'diploma-builder'),
            'not_found_in_trash'    => __('No emblems found in Trash', 'diploma-builder'),
            'all_items'             => __('Emblems', 'diploma-builder'),
            'menu_name'             => __('Emblems', 'diploma-builder'),
        );

        $args = array(
            'labels'              => $labels,
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => 'diploma-builder',
            'capability_type'     => 'post',
            'has_archive'         => false,
            'hierarchical'        => false,
            'supports'            => array('title', 'thumbnail'),
            'rewrite'             => false,
            'query_var'           => false,
        );

        register_post_type(self::POST_TYPE, $args);
    }

    public function add_thumbnail_support() {
        $supported = get_theme_support('post-thumbnails');

        if ($supported === false) {
            add_theme_support('post-thumbnails', array(self::POST_TYPE));
        } elseif (is_array($supported) && isset($supported[0]) && is_array($supported[0])) {
            if (!in_array(self::POST_TYPE, $supported[0])) {
                $supported[0][] = self::POST_TYPE;
                add_theme_support('post-thumbnails', $supported[0]);
            }
        }
    }
}
