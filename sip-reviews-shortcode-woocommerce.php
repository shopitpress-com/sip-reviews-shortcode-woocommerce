<?php
/**
 * @link              https://shopitpress.com
 * @since             1.0.0
 * @package           Sip_Reviews_Shortcode_Woocommerce
 *
 * @wordpress-plugin
 * Plugin Name:			SIP Reviews Shortcode for WooCommerce
 * Plugin URI:			https://shopitpress.com/plugins/sip-reviews-shortcode-woocommerce/
 * Description:			Creates a shortcode, [sip_reviews id="n"],  that displays the reviews of any WooCommerce product. [sip_reviews] will show the reviews of the current product if applicable.  This plugin requires WooCommerce.
 * Version:				1.3.1
 * Requires Plugins:	woocommerce
 * Author:				ShopitPress <hello@shopitpress.com>
 * Author URI:			https://shopitpress.com
 * License:				GPL-2.0+
 * License URI:			http://www.gnu.org/licenses/gpl-2.0.txt
 * Copyright:			© 2015 ShopitPress(email: hello@shopitpress.com)
 * Text Domain:			sip-reviews-shortcode-woocommerce
 * Domain Path:			/languages
 * Requires at least:	6.0
 * Tested up to:		6.9
 * Last updated on: 	04 December, 2025
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'plugins_loaded', 'sip_rswc_activate' );
register_activation_hook( __FILE__, 'activate_sip_reviews_shortcode' );
register_deactivation_hook( __FILE__, 'deactivate_sip_reviews_shortcode' );

define( 'SIP_RSWC_NAME', 'SIP Reviews Shortcode for WooCommerce' );
define( 'SIP_RSWC_VERSION', '1.3.1' );
define( 'SIP_RSWC_PLUGIN_SLUG', 'sip-reviews-shortcode-woocommerce' );
define( 'SIP_RSWC_BASENAME', plugin_basename( __FILE__ ) );
define( 'SIP_RSWC_DIR', trailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'SIP_RSWC_URL', trailingslashit( plugin_dir_url( __FILE__ ) ) );
define( 'SIP_RSWC_INCLUDES', SIP_RSWC_DIR . trailingslashit( 'includes' ) );
define( 'SIP_RSWC_PUBLIC', SIP_RSWC_DIR . trailingslashit( 'public' ) );
define( 'SIP_RSWC_SHOPITPRESS_URL', 'https://shopitpress.com/' );

add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables', // HPOS (High-Performance Order Storage)
            __FILE__,
            true // true = compatible
        );
    }
});


/**
 * The code that runs during plugin activation.
 * To Run the activation  code
 * This action is documented in includes/class-sip-reviews-shortcode-activator.php
 */
function activate_sip_reviews_shortcode() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-sip-reviews-shortcode-activator.php';
	SIP_Reviews_Shortcode_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * To Run the deactivation  code
 * This action is documented in includes/class-sip-reviews-shortcode-deactivator.php
 */
function deactivate_sip_reviews_shortcode() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-sip-reviews-shortcode-deactivator.php';
	SIP_Reviews_Shortcode_Deactivator::deactivate();
}

/**
 * To chek the woocommerce is active or not
 *
 * @since    1.0.0
 * @access   public
 */
function sip_rswc_activate () {
	include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	$plugin = plugin_basename( __FILE__ );

	if( !class_exists( 'WooCommerce' ) ) {
		deactivate_plugins( $plugin );
		add_action( 'admin_notices', 'sip_reviews_shortcode_admin_notice_error' );
	}
}

function sip_reviews_shortcode_admin_notice_error() {
	$class = 'notice notice-error';
	
	$message = sprintf(
		wp_kses(
			/* translators: %s: URL to the WooCommerce plugin page */
			__(
				'SIP Reviews Shortcode for WooCommerce requires <a href="%s" target="_blank">WooCommerce</a> plugin to be active!',
				'sip-reviews-shortcode-woocommerce'
			),
			[
				'a' => [
					'href'   => [],
					'target' => [],
				],
			]
		),
		esc_url( 'https://wordpress.org/plugins/woocommerce/' )
	);

	printf(
		'<div class="%1$s"><p>%2$s</p></div>',
		esc_attr( $class ),
		esc_html( $message )
	);
}

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-sip-reviews-shortcode.php';

/**
 * Begins execution of the reviews shortcode plugin.
 *
 * @since    1.0.4
 */
function run_sip_reviews_shortcode() {

	$plugin = new SIP_Reviews_Shortcode();
	$plugin->run();
}
run_sip_reviews_shortcode();