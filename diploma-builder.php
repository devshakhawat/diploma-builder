<?php
/**
 * Plugin Name: Diploma Builder
 * Plugin URI: https://yourwebsite.com/diploma-builder
 * Description: Create custom high school diplomas with live preview and print-ready output
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: diploma-builder
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.3
 * Requires PHP: 7.4
 * Network: false
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct access denied.' );
}

// Define plugin constants
define( 'DIPLOMA_BUILDER_VERSION', '1.0.0' );
define( 'DIPLOMA_BUILDER_URL', plugin_dir_url( __FILE__ ) );
define( 'DIPLOMA_BUILDER_PATH', plugin_dir_path( __FILE__ ) );
define( 'DIPLOMA_BUILDER_BASENAME', plugin_basename( __FILE__ ) );

// Autoloader for classes
spl_autoload_register(
	function ( $class ) {
		if ( strpos( $class, 'DiplomaBuilder' ) === 0 ) {
			// Convert camelCase to snake_case for file naming
			$class_name = strtolower( preg_replace( '/([a-z])([A-Z])/', '$1-$2', substr( $class, strlen( 'DiplomaBuilder' ) ) ) );
			$class_file = DIPLOMA_BUILDER_PATH . 'includes/class-diplomabuilder' . $class_name . '.php';
			if ( file_exists( $class_file ) ) {
				require_once $class_file;
			}
		}
	}
);

// Main plugin class
class DiplomaBuilder {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->init_hooks();
		$this->load_dependencies();
	}

	private function init_hooks() {
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
		register_uninstall_hook( __FILE__, array( 'DiplomaBuilder', 'uninstall' ) );

		add_action( 'plugins_loaded', array( $this, 'init' ) );
		add_action( 'init', array( $this, 'load_textdomain' ) );
	}

	private function load_dependencies() {
		// Load required files
		require_once DIPLOMA_BUILDER_PATH . 'includes/class-diplomabuilder-database.php';
		require_once DIPLOMA_BUILDER_PATH . 'includes/class-diplomabuilder-frontend.php';
		require_once DIPLOMA_BUILDER_PATH . 'includes/class-diplomabuilder-admin.php';
		require_once DIPLOMA_BUILDER_PATH . 'includes/class-diplomabuilder-ajax.php';
		require_once DIPLOMA_BUILDER_PATH . 'includes/class-diplomabuilder-assets.php';
	}

	public function init() {
		// Initialize components
		new DiplomaBuilder_Database();
		new DiplomaBuilder_Frontend();
		new DiplomaBuilder_Ajax();
		new DiplomaBuilder_Assets();

		if ( is_admin() ) {
			new DiplomaBuilder_Admin();
		}
	}

	public function activate() {
		// Create database tables
		DiplomaBuilder_Database::create_tables();

		// Create upload directories
		$this->create_upload_directories();

		// Set default options
		$this->set_default_options();

		// Flush rewrite rules
		flush_rewrite_rules();
	}

	public function deactivate() {
		// Clean up temporary files
		$this->cleanup_temp_files();

		// Clear scheduled events
		wp_clear_scheduled_hook( 'diploma_builder_cleanup' );
	}

	public static function uninstall() {
		// Remove database tables
		DiplomaBuilder_Database::drop_tables();

		// Remove options
		delete_option( 'diploma_builder_version' );
		delete_option( 'diploma_allow_guests' );
		delete_option( 'diploma_default_paper' );
		delete_option( 'diploma_max_per_user' );

		// Remove upload directories
		$upload_dir  = wp_upload_dir();
		$diploma_dir = $upload_dir['basedir'] . '/diplomas';
		if ( file_exists( $diploma_dir ) ) {
			self::remove_directory( $diploma_dir );
		}
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'diploma-builder', false, dirname( DIPLOMA_BUILDER_BASENAME ) . '/languages' );
	}

	private function create_upload_directories() {
		$upload_dir  = wp_upload_dir();
		$directories = array(
			$upload_dir['basedir'] . '/diplomas',
			$upload_dir['basedir'] . '/diplomas/temp',
			$upload_dir['basedir'] . '/diplomas/generated',
		);

		foreach ( $directories as $dir ) {
			if ( ! file_exists( $dir ) ) {
				wp_mkdir_p( $dir );
				// Add index.html to prevent directory browsing
				file_put_contents( $dir . '/index.html', '' );
			}
		}
	}

	private function set_default_options() {
		add_option( 'diploma_builder_version', DIPLOMA_BUILDER_VERSION );
		add_option( 'diploma_allow_guests', 1 );
		add_option( 'diploma_default_paper', 'white' );
		add_option( 'diploma_max_per_user', 10 );
		add_option( 'diploma_digital_product_id', 0 );
		add_option( 'diploma_printed_product_id', 0 );
		add_option( 'diploma_premium_product_id', 0 );
	}

	private function cleanup_temp_files() {
		$upload_dir = wp_upload_dir();
		$temp_dir   = $upload_dir['basedir'] . '/diplomas/temp';

		if ( file_exists( $temp_dir ) ) {
			$files       = glob( $temp_dir . '/*' );
			$cutoff_time = time() - ( 24 * 60 * 60 ); // 24 hours ago

			foreach ( $files as $file ) {
				if ( filemtime( $file ) < $cutoff_time ) {
					unlink( $file );
				}
			}
		}
	}

	private static function remove_directory( $dir ) {
		if ( ! is_dir( $dir ) ) {
			return;
		}

		$files = array_diff( scandir( $dir ), array( '.', '..' ) );
		foreach ( $files as $file ) {
			$path = $dir . DIRECTORY_SEPARATOR . $file;
			is_dir( $path ) ? self::remove_directory( $path ) : unlink( $path );
		}
		rmdir( $dir );
	}
}

// Initialize the plugin
DiplomaBuilder::get_instance();
