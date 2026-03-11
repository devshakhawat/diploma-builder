<?php
/**
 * Emblems Custom Post Type for Diploma Builder
 */

if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

class DiplomaBuilder_Emblems {

    const POST_TYPE = 'diploma_emblem';

    const TAXONOMY = 'emblem_category';

    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_taxonomy'));
        add_action('after_setup_theme', array($this, 'add_thumbnail_support'));
        add_action('add_meta_boxes', array($this, 'add_gallery_meta_box'));
        add_action('save_post_' . self::POST_TYPE, array($this, 'save_gallery_meta'), 10, 2);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_gallery_scripts'));
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

    public function register_taxonomy() {
        $labels = array(
            'name'              => __('Emblem Categories', 'diploma-builder'),
            'singular_name'     => __('Emblem Category', 'diploma-builder'),
            'search_items'      => __('Search Categories', 'diploma-builder'),
            'all_items'         => __('All Categories', 'diploma-builder'),
            'parent_item'       => __('Parent Category', 'diploma-builder'),
            'parent_item_colon' => __('Parent Category:', 'diploma-builder'),
            'edit_item'         => __('Edit Category', 'diploma-builder'),
            'update_item'       => __('Update Category', 'diploma-builder'),
            'add_new_item'      => __('Add New Category', 'diploma-builder'),
            'new_item_name'     => __('New Category Name', 'diploma-builder'),
            'menu_name'         => __('Categories', 'diploma-builder'),
        );

        $args = array(
            'labels'            => $labels,
            'hierarchical'      => true,
            'public'            => false,
            'show_ui'           => true,
            'show_in_menu'      => true,
            'show_admin_column' => true,
            'query_var'         => false,
            'rewrite'           => false,
        );

        register_taxonomy(self::TAXONOMY, self::POST_TYPE, $args);
    }

    /**
     * Get all published emblems with their featured image URLs.
     *
     * @return array Keyed by post ID: ['name' => string, 'image_url' => string]
     */
    public static function get_all() {
        $emblems = array();
        $query = new WP_Query(array(
            'post_type'      => self::POST_TYPE,
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'ASC',
        ));

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                $thumbnail_url = get_the_post_thumbnail_url($post_id, 'medium');
                if ($thumbnail_url) {
                    $emblems[$post_id] = array(
                        'name'      => get_the_title(),
                        'image_url' => $thumbnail_url,
                    );
                }
            }
            wp_reset_postdata();
        }

        return $emblems;
    }

    /**
     * Get published emblems filtered by emblem_category slug.
     *
     * @param string $category_slug The emblem_category taxonomy slug (e.g. 'usa', 'uk', 'canada', 'international').
     * @return array Keyed by post ID: ['name' => string, 'image_url' => string]
     */
    public static function get_by_category($category_slug) {
        $emblems = array();

        if (empty($category_slug)) {
            return $emblems;
        }

        $query = new WP_Query(array(
            'post_type'      => self::POST_TYPE,
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'ASC',
            'tax_query'      => array(
                array(
                    'taxonomy' => self::TAXONOMY,
                    'field'    => 'slug',
                    'terms'    => $category_slug,
                ),
            ),
        ));

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                $thumbnail_url = get_the_post_thumbnail_url($post_id, 'medium');
                if ($thumbnail_url) {
                    $emblems[$post_id] = array(
                        'name'      => get_the_title(),
                        'image_url' => $thumbnail_url,
                    );
                }
            }
            wp_reset_postdata();
        }

        return $emblems;
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

    public function enqueue_gallery_scripts($hook) {
        global $post_type;
        if ($post_type !== self::POST_TYPE || !in_array($hook, array('post.php', 'post-new.php'))) {
            return;
        }
        wp_enqueue_media();
        wp_enqueue_script(
            'diploma-emblem-gallery',
            DIPLOMA_BUILDER_URL . 'assets/emblem-gallery-admin.js',
            array('jquery'),
            DIPLOMA_BUILDER_VERSION,
            true
        );
        wp_enqueue_style(
            'diploma-emblem-gallery',
            DIPLOMA_BUILDER_URL . 'assets/emblem-gallery-admin.css',
            array(),
            DIPLOMA_BUILDER_VERSION
        );
    }

    public function add_gallery_meta_box() {
        add_meta_box(
            'diploma_emblem_gallery',
            __('Emblem Gallery', 'diploma-builder'),
            array($this, 'render_gallery_meta_box'),
            self::POST_TYPE,
            'normal',
            'high'
        );
    }

    public function render_gallery_meta_box($post) {
        wp_nonce_field('diploma_emblem_gallery_nonce', 'emblem_gallery_nonce');
        $gallery_ids = get_post_meta($post->ID, '_emblem_gallery', true);
        $gallery_ids = !empty($gallery_ids) ? array_filter(array_map('intval', explode(',', $gallery_ids))) : array();
        ?>
        <div id="emblem-gallery-container">
            <ul id="emblem-gallery-images" class="emblem-gallery-list">
                <?php foreach ($gallery_ids as $attachment_id) :
                    $image_url = wp_get_attachment_image_url($attachment_id, 'thumbnail');
                    if ($image_url) : ?>
                        <li data-id="<?php echo esc_attr($attachment_id); ?>">
                            <img src="<?php echo esc_url($image_url); ?>" alt="">
                            <button type="button" class="emblem-gallery-remove" title="<?php esc_attr_e('Remove', 'diploma-builder'); ?>">&times;</button>
                        </li>
                    <?php endif;
                endforeach; ?>
            </ul>
            <input type="hidden" id="emblem-gallery-ids" name="emblem_gallery_ids" value="<?php echo esc_attr(implode(',', $gallery_ids)); ?>">
            <button type="button" id="emblem-gallery-add" class="button"><?php _e('Add Images', 'diploma-builder'); ?></button>
        </div>
        <?php
    }

    public function save_gallery_meta($post_id, $post) {
        if (!isset($_POST['emblem_gallery_nonce']) || !wp_verify_nonce($_POST['emblem_gallery_nonce'], 'diploma_emblem_gallery_nonce')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['emblem_gallery_ids'])) {
            $ids = sanitize_text_field($_POST['emblem_gallery_ids']);
            $ids = implode(',', array_filter(array_map('intval', explode(',', $ids))));
            update_post_meta($post_id, '_emblem_gallery', $ids);
        } else {
            delete_post_meta($post_id, '_emblem_gallery');
        }
    }

    /**
     * Get gallery image URLs for a specific emblem post.
     *
     * @param int    $post_id The emblem post ID.
     * @param string $size    Image size (default 'medium').
     * @return array Array of image URLs.
     */
    public static function get_gallery($post_id, $size = 'medium') {
        $gallery_ids = get_post_meta($post_id, '_emblem_gallery', true);
        if (empty($gallery_ids)) {
            return array();
        }

        $images = array();
        $ids = array_filter(array_map('intval', explode(',', $gallery_ids)));
        foreach ($ids as $attachment_id) {
            $url = wp_get_attachment_image_url($attachment_id, $size);
            if ($url) {
                $images[] = array(
                    'id'  => $attachment_id,
                    'url' => $url,
                );
            }
        }
        return $images;
    }
}
