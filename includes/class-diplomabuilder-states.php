<?php
/**
 * States Custom Post Type for Diploma Builder
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct access denied.' );
}

class DiplomaBuilder_States {

	const POST_TYPE = 'diploma_state';

	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'after_setup_theme', array( $this, 'add_thumbnail_support' ) );
	}

	public function register_post_type() {
		$labels = array(
			'name'               => __( 'States', 'diploma-builder' ),
			'singular_name'      => __( 'State', 'diploma-builder' ),
			'add_new'            => __( 'Add New', 'diploma-builder' ),
			'add_new_item'       => __( 'Add New State', 'diploma-builder' ),
			'edit_item'          => __( 'Edit State', 'diploma-builder' ),
			'new_item'           => __( 'New State', 'diploma-builder' ),
			'view_item'          => __( 'View State', 'diploma-builder' ),
			'search_items'       => __( 'Search States', 'diploma-builder' ),
			'not_found'          => __( 'No states found', 'diploma-builder' ),
			'not_found_in_trash' => __( 'No states found in Trash', 'diploma-builder' ),
			'all_items'          => __( 'States', 'diploma-builder' ),
			'menu_name'          => __( 'States', 'diploma-builder' ),
		);

		$args = array(
			'labels'          => $labels,
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => 'diploma-builder',
			'capability_type' => 'post',
			'has_archive'     => false,
			'hierarchical'    => false,
			'supports'        => array( 'title', 'thumbnail' ),
			'rewrite'         => false,
			'query_var'       => false,
		);

		register_post_type( self::POST_TYPE, $args );
	}

	public function add_thumbnail_support() {
		$supported = get_theme_support( 'post-thumbnails' );

		if ( $supported === false ) {
			add_theme_support( 'post-thumbnails', array( self::POST_TYPE ) );
		} elseif ( is_array( $supported ) && isset( $supported[0] ) && is_array( $supported[0] ) ) {
			if ( ! in_array( self::POST_TYPE, $supported[0] ) ) {
				$supported[0][] = self::POST_TYPE;
				add_theme_support( 'post-thumbnails', $supported[0] );
			}
		}
	}

	/**
	 * Get all published states with their featured image URLs.
	 *
	 * @return array Keyed by post ID: ['name' => string, 'image_url' => string]
	 */
	public static function get_all() {
		$states = array();
		$query  = new WP_Query(
			array(
				'post_type'      => self::POST_TYPE,
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$post_id          = get_the_ID();
				$thumbnail_url    = get_the_post_thumbnail_url( $post_id, 'medium' );
				$states[ $post_id ] = array(
					'name'      => get_the_title(),
					'image_url' => $thumbnail_url ? $thumbnail_url : '',
				);
			}
			wp_reset_postdata();
		}

		return $states;
	}
}
