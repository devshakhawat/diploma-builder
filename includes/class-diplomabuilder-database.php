<?php
/**
 * Database operations for Diploma Builder
 */

if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

class DiplomaBuilder_Database {
    
    const TABLE_VERSION = '1.0';
    
    public function __construct() {
        add_action('plugins_loaded', array($this, 'update_db_check'));
    }
    
    public function update_db_check() {
        if (get_site_option('diploma_builder_db_version') !== self::TABLE_VERSION) {
            self::create_tables();
        }
    }
    
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Diploma configurations table
        $table_name = $wpdb->prefix . 'diploma_configurations';
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id mediumint(9) DEFAULT NULL,
            diploma_style varchar(50) NOT NULL,
            paper_color varchar(50) NOT NULL,
            emblem_type varchar(50) NOT NULL,
            emblem_value varchar(50) NOT NULL,
            school_name varchar(255) NOT NULL,
            student_name varchar(255) DEFAULT '',
            graduation_date varchar(100) NOT NULL,
            city varchar(100) NOT NULL,
            state varchar(100) NOT NULL,
            configuration_data longtext NOT NULL,
            image_path varchar(500) DEFAULT '',
            is_public tinyint(1) DEFAULT 0,
            download_count int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY diploma_style (diploma_style),
            KEY created_at (created_at),
            KEY is_public (is_public)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Update version
        update_option('diploma_builder_db_version', self::TABLE_VERSION);
    }
    
    public static function drop_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'diploma_configurations';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
        
        delete_option('diploma_builder_db_version');
    }
    
    /**
     * Save diploma configuration
     */
    public static function save_diploma($data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'diploma_configurations';
        
        $diploma_data = array(
            'user_id' => get_current_user_id() ?: null,
            'diploma_style' => sanitize_text_field($data['diploma_style']),
            'paper_color' => sanitize_text_field($data['paper_color']),
            'emblem_type' => sanitize_text_field($data['emblem_type']),
            'emblem_value' => sanitize_text_field($data['emblem_value']),
            'school_name' => sanitize_text_field($data['school_name']),
            'student_name' => sanitize_text_field($data['student_name'] ?? ''),
            'graduation_date' => sanitize_text_field($data['graduation_date']),
            'city' => sanitize_text_field($data['city']),
            'state' => sanitize_text_field($data['state']),
            'configuration_data' => wp_json_encode($data),
            'image_path' => sanitize_text_field($data['image_path'] ?? ''),
            'is_public' => intval($data['is_public'] ?? 0)
        );
        
        if (isset($data['diploma_id']) && $data['diploma_id']) {
            // Update existing diploma
            $diploma_id = intval($data['diploma_id']);
            $result = $wpdb->update(
                $table_name,
                $diploma_data,
                array('id' => $diploma_id),
                array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d'),
                array('%d')
            );
            
            return $result !== false ? $diploma_id : false;
        } else {
            // Create new diploma
            $result = $wpdb->insert($table_name, $diploma_data);
            return $result !== false ? $wpdb->insert_id : false;
        }
    }
    
    /**
     * Get diploma by ID
     */
    public static function get_diploma($diploma_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'diploma_configurations';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            intval($diploma_id)
        ));
    }
    
    /**
     * Get diplomas by user
     */
    public static function get_user_diplomas($user_id, $limit = 20, $offset = 0) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'diploma_configurations';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d",
            intval($user_id),
            intval($limit),
            intval($offset)
        ));
    }
    
    /**
     * Get all diplomas (admin)
     */
    public static function get_all_diplomas($limit = 50, $offset = 0, $search = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'diploma_configurations';
        
        $where = '';
        $params = array();
        
        if (!empty($search)) {
            $where = " WHERE school_name LIKE %s OR city LIKE %s OR state LIKE %s";
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $params = array($search_term, $search_term, $search_term);
        }
        
        $params[] = intval($limit);
        $params[] = intval($offset);
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name $where ORDER BY created_at DESC LIMIT %d OFFSET %d",
            ...$params
        ));
    }
    
    /**
     * Delete diploma
     */
    public static function delete_diploma($diploma_id, $user_id = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'diploma_configurations';
        
        $where = array('id' => intval($diploma_id));
        $where_format = array('%d');
        
        // If user_id is provided, ensure user can only delete their own diplomas
        if ($user_id !== null) {
            $where['user_id'] = intval($user_id);
            $where_format[] = '%d';
        }
        
        return $wpdb->delete($table_name, $where, $where_format);
    }
    
    /**
     * Get diploma statistics
     */
    public static function get_statistics() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'diploma_configurations';
        
        $stats = array();
        
        // Total diplomas
        $stats['total'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        
        // Unique users
        $stats['unique_users'] = $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM $table_name WHERE user_id IS NOT NULL");
        
        // This week
        $stats['this_week'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE created_at >= %s",
            date('Y-m-d H:i:s', strtotime('-7 days'))
        ));
        
        // This month
        $stats['this_month'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE created_at >= %s",
            date('Y-m-01 00:00:00')
        ));
        
        // Most popular style
        $popular_style = $wpdb->get_row("SELECT diploma_style, COUNT(*) as count FROM $table_name GROUP BY diploma_style ORDER BY count DESC LIMIT 1");
        $stats['popular_style'] = $popular_style ? $popular_style->diploma_style : '';
        
        // Most popular paper color
        $popular_paper = $wpdb->get_row("SELECT paper_color, COUNT(*) as count FROM $table_name GROUP BY paper_color ORDER BY count DESC LIMIT 1");
        $stats['popular_paper'] = $popular_paper ? $popular_paper->paper_color : '';
        
        // Style breakdown
        $style_stats = $wpdb->get_results("SELECT diploma_style, COUNT(*) as count FROM $table_name GROUP BY diploma_style ORDER BY count DESC");
        $stats['style_breakdown'] = array();
        foreach ($style_stats as $style) {
            $stats['style_breakdown'][$style->diploma_style] = $style->count;
        }
        
        return $stats;
    }
    
    /**
     * Update download count
     */
    public static function increment_download_count($diploma_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'diploma_configurations';
        
        return $wpdb->query($wpdb->prepare(
            "UPDATE $table_name SET download_count = download_count + 1 WHERE id = %d",
            intval($diploma_id)
        ));
    }
    
    /**
     * Clean up old temporary diplomas
     */
    public static function cleanup_old_diplomas($days = 30) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'diploma_configurations';
        
        return $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name WHERE user_id IS NULL AND created_at < %s",
            date('Y-m-d H:i:s', strtotime("-$days days"))
        ));
    }
    
    /**
     * Check if user has reached diploma limit
     */
    public static function user_can_create_diploma($user_id = null) {
        if (!$user_id) {
            // Check guest creation allowance
            return get_option('diploma_allow_guests', 1);
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'diploma_configurations';
        
        $max_diplomas = get_option('diploma_max_per_user', 10);
        $user_diploma_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE user_id = %d",
            intval($user_id)
        ));
        
        return $user_diploma_count < $max_diplomas;
    }
}