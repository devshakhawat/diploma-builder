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
		add_action( 'init', array( $this, 'register_taxonomy' ) );
		add_action( 'after_setup_theme', array( $this, 'add_thumbnail_support' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_gallery_meta_box' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save_gallery_meta' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_gallery_scripts' ) );
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

	public function register_taxonomy() {
		$labels = array(
			'name'              => __( 'Countries', 'diploma-builder' ),
			'singular_name'     => __( 'Country', 'diploma-builder' ),
			'search_items'      => __( 'Search Countries', 'diploma-builder' ),
			'all_items'         => __( 'All Countries', 'diploma-builder' ),
			'parent_item'       => __( 'Parent Country', 'diploma-builder' ),
			'parent_item_colon' => __( 'Parent Country:', 'diploma-builder' ),
			'edit_item'         => __( 'Edit Country', 'diploma-builder' ),
			'update_item'       => __( 'Update Country', 'diploma-builder' ),
			'add_new_item'      => __( 'Add New Country', 'diploma-builder' ),
			'new_item_name'     => __( 'New Country Name', 'diploma-builder' ),
			'menu_name'         => __( 'Countries', 'diploma-builder' ),
		);

		$args = array(
			'labels'            => $labels,
			'hierarchical'      => true,
			'public'            => false,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_menu'      => true,
			'rewrite'           => false,
			'query_var'         => false,
		);

		register_taxonomy( 'diploma_state_country', self::POST_TYPE, $args );
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

	public function enqueue_gallery_scripts( $hook ) {
		global $post_type;
		if ( $post_type !== self::POST_TYPE || ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_script(
			'diploma-state-gallery',
			DIPLOMA_BUILDER_URL . 'assets/state-gallery-admin.js',
			array( 'jquery' ),
			DIPLOMA_BUILDER_VERSION,
			true
		);
		wp_enqueue_style(
			'diploma-state-gallery',
			DIPLOMA_BUILDER_URL . 'assets/state-gallery-admin.css',
			array(),
			DIPLOMA_BUILDER_VERSION
		);
	}

	public function add_gallery_meta_box() {
		add_meta_box(
			'diploma_state_gallery',
			__( 'State Gallery', 'diploma-builder' ),
			array( $this, 'render_gallery_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	public function render_gallery_meta_box( $post ) {
		wp_nonce_field( 'diploma_state_gallery_nonce', 'state_gallery_nonce' );
		$gallery_ids = get_post_meta( $post->ID, '_state_gallery', true );
		$gallery_ids = ! empty( $gallery_ids ) ? array_filter( array_map( 'intval', explode( ',', $gallery_ids ) ) ) : array();
		?>
		<div id="state-gallery-container">
			<ul id="state-gallery-images" class="state-gallery-list">
				<?php foreach ( $gallery_ids as $attachment_id ) :
					$image_url = wp_get_attachment_image_url( $attachment_id, 'thumbnail' );
					if ( $image_url ) : ?>
						<li data-id="<?php echo esc_attr( $attachment_id ); ?>">
							<img src="<?php echo esc_url( $image_url ); ?>" alt="">
							<button type="button" class="state-gallery-remove" title="<?php esc_attr_e( 'Remove', 'diploma-builder' ); ?>">&times;</button>
						</li>
					<?php endif;
				endforeach; ?>
			</ul>
			<input type="hidden" id="state-gallery-ids" name="state_gallery_ids" value="<?php echo esc_attr( implode( ',', $gallery_ids ) ); ?>">
			<button type="button" id="state-gallery-add" class="button"><?php _e( 'Add Images', 'diploma-builder' ); ?></button>
		</div>
		<?php
	}

	public function save_gallery_meta( $post_id, $post ) {
		if ( ! isset( $_POST['state_gallery_nonce'] ) || ! wp_verify_nonce( $_POST['state_gallery_nonce'], 'diploma_state_gallery_nonce' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['state_gallery_ids'] ) ) {
			$ids = sanitize_text_field( $_POST['state_gallery_ids'] );
			$ids = implode( ',', array_filter( array_map( 'intval', explode( ',', $ids ) ) ) );
			update_post_meta( $post_id, '_state_gallery', $ids );
		} else {
			delete_post_meta( $post_id, '_state_gallery' );
		}
	}

	/**
	 * Get gallery image URLs for a specific state post.
	 *
	 * @param int    $post_id The state post ID.
	 * @param string $size    Image size (default 'medium').
	 * @return array Array of ['id' => int, 'url' => string].
	 */
	public static function get_gallery( $post_id, $size = 'medium' ) {
		$gallery_ids = get_post_meta( $post_id, '_state_gallery', true );
		if ( empty( $gallery_ids ) ) {
			return array();
		}

		$images = array();
		$ids    = array_filter( array_map( 'intval', explode( ',', $gallery_ids ) ) );
		foreach ( $ids as $attachment_id ) {
			$url = wp_get_attachment_image_url( $attachment_id, $size );
			if ( $url ) {
				$images[] = array(
					'id'  => $attachment_id,
					'url' => $url,
				);
			}
		}
		return $images;
	}

	/**
	 * Get gallery images for the diploma_state post whose title matches the given state name.
	 *
	 * @param string $state_name The state name to match against post titles.
	 * @param string $size       Image size (default 'medium').
	 * @return array Keyed by attachment ID: ['name' => string, 'image_url' => string]
	 */
	public static function get_gallery_by_name( $state_name, $size = 'medium' ) {
		$images = array();

		if ( empty( $state_name ) ) {
			return $images;
		}

		$query = new WP_Query(
			array(
				'post_type'      => self::POST_TYPE,
				'posts_per_page' => 1,
				'post_status'    => 'publish',
				'title'          => $state_name,
			)
		);

		if ( ! $query->have_posts() ) {
			wp_reset_postdata();
			return $images;
		}

		$query->the_post();
		$post_id = get_the_ID();
		wp_reset_postdata();

		$gallery = self::get_gallery( $post_id, $size );
		foreach ( $gallery as $image ) {
			$images[ $image['id'] ] = array(
				'name'      => get_the_title( $post_id ) . ' #' . $image['id'],
				'image_url' => $image['url'],
			);
		}

		return $images;
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
