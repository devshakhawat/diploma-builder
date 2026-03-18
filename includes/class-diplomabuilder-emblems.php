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
            'supports'            => array('title'),
            'rewrite'             => false,
            'query_var'           => false,
        );

        register_post_type(self::POST_TYPE, $args);
    }

    /**
     * Get all published emblems with their first gallery image URL.
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
                $gallery = self::get_gallery($post_id, 'medium');
                $image_url = !empty($gallery) ? $gallery[0]['url'] : '';
                if ($image_url) {
                    $emblems[$post_id] = array(
                        'name'      => get_the_title(),
                        'image_url' => $image_url,
                    );
                }
            }
            wp_reset_postdata();
        }

        return $emblems;
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
     * Get gallery images for the diploma_emblem post whose title matches the given country name.
     *
     * @param string $country_name The country name to match against post titles.
     * @param string $size         Image size (default 'medium').
     * @return array Keyed by attachment ID: ['name' => filename, 'image_url' => url]
     */
    public static function get_gallery_by_country($country_name, $size = 'medium') {
        $emblems = array();

        if (empty($country_name)) {
            return $emblems;
        }

        // Find the diploma_emblem post whose title matches the country name
        $query = new WP_Query(array(
            'post_type'      => self::POST_TYPE,
            'posts_per_page' => 1,
            'post_status'    => 'publish',
            'title'          => $country_name,
        ));

        if (!$query->have_posts()) {
            wp_reset_postdata();
            return $emblems;
        }

        $query->the_post();
        $post_id = get_the_ID();
        wp_reset_postdata();

        // Get gallery images from post meta
        $gallery = self::get_gallery($post_id, $size);
        foreach ($gallery as $image) {
            $emblems[$image['id']] = array(
                'name'      => get_the_title($post_id) . ' #' . $image['id'],
                'image_url' => $image['url'],
            );
        }

        return $emblems;
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
