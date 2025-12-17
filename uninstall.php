<?php

/**
 * @link       http://shopitpress.com
 * @since      1.0.4
 *
 * @package    Sip_Reviews_Shortcode_Woocommerce
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

delete_option( 'sip_rswc_color_options' );
delete_option( 'sip_rswc_settings' );