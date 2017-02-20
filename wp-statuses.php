<?php
/*
Plugin Name: WP Statuses
Plugin URI: https://imathi.eu/tag/wp-statuses/
Description: TBD
Version: 1.0.0
Requires at least: 4.7
Tested up to: 4.7
License: GNU/GPL 2
Author: imath
Author URI: https://imathi.eu/
Text Domain: wp-statuses
Domain Path: /languages/
*/

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_Statuses' ) ) :
/**
 * Main plugin's class
 *
 * @package WP Statuses
 *
 * @since 2.0.0
 */
final class WP_Statuses {

	/**
	 * Plugin's main instance
	 * @var object
	 */
	protected static $instance;

	/**
	 * Initialize the plugin
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->setup_globals();
		$this->inc();
		$this->setup_hooks();
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since 1.0.0
	 *
	 * @return object A single instance of this class.
	 */
	public static function start() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Setups plugin's globals
	 *
	 * @since 1.0.0
	 */
	private function setup_globals() {
		// Version
		$this->version = '1.0.0-alpha';

		// Domain
		$this->domain = 'wp-statuses';

		// Base name
		$this->file      = __FILE__;
		$this->basename  = plugin_basename( $this->file );

		// Path and URL
		$this->dir     = plugin_dir_path( $this->file );
		$this->url     = plugin_dir_url ( $this->file );
		$this->js_url  = trailingslashit( $this->url . 'js' );
		$this->inc_dir = trailingslashit( $this->dir . 'inc' );
	}

	/**
	 * Includes plugin's needed files
	 *
	 * @since 1.0.0
	 */
	private function inc() {
		spl_autoload_register( array( $this, 'autoload' ) );

		require( $this->inc_dir . 'core/functions.php' );
	}

	/**
	 * Setups some hooks to register post type stuff, scripts, set
	 * the current user & load plugin's BuddyPress integration
	 *
	 * @since 2.0.0
	 */
	private function setup_hooks() {
		add_action( 'init', 'wp_statuses_register', 1000 );

		// Boot the Admin
		if ( is_admin() ) {
			add_action( 'plugins_loaded', array( 'WP_Statuses_Admin', 'start' ), 10 );
		}
	}

	/**
	 * Loads the translation files
	 *
	 * @since 1.0.0
	 */
	public function load_textdomain() {
		// Traditional WordPress plugin locale filter
		load_plugin_textdomain( $this->domain, false, trailingslashit( basename( $this->plugin_dir ) ) . 'languages' );
	}

	/**
	 * Class Autoload function
	 *
	 * @since  1.0.0
	 *
	 * @param  string $class The class name.
	 */
	public function autoload( $class ) {
		$name = str_replace( '_', '-', strtolower( $class ) );

		if ( false === strpos( $name, $this->domain ) ) {
			return;
		}

		$folder = null;
		$parts = explode( '-', $name );

		if ( isset( $parts[2] ) ) {
			$folder = $parts[2];
		}

		$path = $this->inc_dir . "{$folder}/classes/class-{$name}.php";

		// Sanity check.
		if ( ! file_exists( $path ) ) {
			return;
		}

		require $path;
	}
}

endif;

/**
 * Boot function for the plugin
 *
 * @since 1.0.0
 */
function wp_statuses() {
	return WP_Statuses::start();
}
add_action( 'plugins_loaded', 'wp_statuses', 5 );
