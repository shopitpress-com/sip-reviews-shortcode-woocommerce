<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://shopitpress.com
 * @since      1.0.4
 *
 * @package    SIP_Reviews_Shortcode
 * @subpackage SIP_Reviews_Shortcode/includes
 */

/**
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.4
 * @package    SIP_Reviews_Shortcode
 * @subpackage SIP_Reviews_Shortcode/includes
 * @author     ShopitPress <hello@shopitpress.com>
 */
class SIP_Reviews_Shortcode {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.4
	 * @access   protected
	 * @var      SIP_Reviews_Shortcode_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.4
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.4
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.4
	 */
	public function __construct() {

		$this->plugin_name = 'sip-reviews-shortcode-woocommerce';
		$this->version = '1.3.0';

		$this->load_dependencies();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		// add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'plugins_loaded', [ 'SIP_RSWC_Display', 'init' ] );
	}

  	public function admin_init() {
  	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - SIP_Reviews_Shortcode_Loader. Orchestrates the hooks of the plugin.
	 * - SIP_Reviews_Shortcode_i18n. Defines internationalization functionality.
	 * - SIP_Reviews_Shortcode_Admin. Defines all hooks for the admin area.
	 * - SIP_Reviews_Shortcode_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.4
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-sip-reviews-shortcode-loader.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-sip-reviews-shortcode-wc-admin.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-sip-suite-dashboard.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-sip-rswc-settings-screen.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-sip-rswc-db-helper.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-sip-rswc-review-renderer.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-sip-rswc-schema-helper.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-sip-rswc-schema-output.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-sip-reviews-shortcode-wc-public.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/partials/plugin-reviews-shortcode-display.php';

		$this->loader = new SIP_Reviews_Shortcode_Loader();
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.4
	 * @access   private
	 */
	private function define_admin_hooks() {

		new \SIP_Reviews_Shortcode\Admin\SIP_Reviews_Shortcode_WC_Admin( $this->get_plugin_name(), $this->get_version() );


		// $plugin_admin = new SIP_Reviews_Shortcode_WC_Admin( $this->get_plugin_name(), $this->get_version() );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.4
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new SIP_Reviews_Shortcode_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );	
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.4
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.4
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.4
	 * @return    SIP_Reviews_Shortcode_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.4
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}