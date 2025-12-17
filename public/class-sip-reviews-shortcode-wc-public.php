<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://shopitpress.com
 * @since      1.0.0
 *
 * @package    SIP_Reviews_Shortcode
 * @subpackage SIP_Reviews_Shortcode/public
 */


/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    SIP_Reviews_Shortcode
 * @subpackage SIP_Reviews_Shortcode/public
 * @author     shopitpress <hello@shopitpress.com>
 */
class SIP_Reviews_Shortcode_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		$handle = $this->plugin_name . '-front'; // e.g. 'sip-reviews-shortcode-front'

		// Enqueue the base stylesheet
		wp_enqueue_style(
			$handle,
			plugins_url( 'css/main.min.css', __FILE__  ),
			[],
			$this->version
		);

		// Pull color options (sanitized)
		$defaults = [
			'star_color'              => '#c62437',
			'bar_color'               => '#3f51b5',
			'review_background_color' => '#ffffff',
			'review_body_text_color'  => '#333333',
			'review_title_color'      => '#111111',
			'load_more_button'        => '#3f51b5',
			'load_more_text'          => '#ffffff',
		];
		
		$colors = get_option( \SIP_Reviews_Shortcode\Admin\SIP_Reviews_Shortcode_WC_Admin::OPTION_COLORS, [] );
		$c = [];
		foreach ($defaults as $k => $fallback) {
			$val = isset($colors[$k]) ? sanitize_hex_color($colors[$k]) : $fallback;
			$c[$k] = $val ?: $fallback;
		}

		// Provide both CSS variables (preferred) and direct fallbacks
		$css = "
			.sip-reviews {
				--sip-star-color: 	{$c['star_color']};
				--sip-bar-color: 	{$c['bar_color']};
				--sip-review-bg: 	{$c['review_background_color']};
				--sip-body-text: 	{$c['review_body_text_color']};
				--sip-title-text: 	{$c['review_title_color']};
				--sip-load-more-bg: {$c['load_more_button']};
				--sip-load-more-text: {$c['load_more_text']};
			}
			.sip-reviews .sip-rswc-star-label .sip-rswc-star, 
			.sip-reviews .sip-rswc-star-selected:after { 
				color: var(--sip-star-color); 
			}
			.sip-reviews .sip-rswc-bar { 
				background: var(--sip-bar-color); 
			}
			.sip-reviews .comment-borderbox { 
				background: var(--sip-review-bg);
				color: var(--sip-body-text);
			}
			.sip-reviews .sip-rswc-summary-left,
			.sip-reviews .sip-rswc-star-label a,
			.sip-reviews .sip-rswc-rating-count a {
				color: var(--sip-title-text);
			}
			.sip-reviews .sip-rswc-load-more-btn { 
				background: var(--sip-load-more-bg);
				color: var(--sip-load-more-text);
			}
		";

		$css = $this->sip_minify_css( $css );
		wp_add_inline_style($handle, $css);
	}

	/**
	 * Minifies a block of CSS by removing comments, whitespace, line breaks,
	 * and unnecessary characters. This helps reduce output size when generating
	 * inline styles dynamically.
	 *
	 * This function is designed for safe inline CSS minification and avoids
	 * aggressive optimizations that could break complex CSS rules.
	 *
	 * @since 1.0.3
	 *
	 * @param string $css The raw CSS string to be minified.
	 * @return string The minified single-line CSS output.
	 */
	public function sip_minify_css( $css ) {
	    if ( empty( $css ) ) {
	        return $css;
	    }

	    // Remove CSS comments.
	    $css = preg_replace( '!/\*.*?\*/!s', '', $css );

	    // Normalize whitespace.
	    $css = preg_replace( '/\s+/', ' ', $css );
	    $css = str_replace( array("\n", "\r", "\t"), '', $css );

	    // Remove unnecessary spaces around symbols.
	    $css = preg_replace( '/\s*([{};:,])\s*/', '$1', $css );

	    return trim( $css );
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/main.min.js', array( 'jquery' ), $this->version, true );
		wp_localize_script( $this->plugin_name, 'sip_rswc_ajax', [
			'ajax_url' 			=> admin_url( 'admin-ajax.php' ),
			'nonce'    			=> wp_create_nonce( 'sip_rswc_reviews_nonce' ),
			'text_load_more'	=> esc_html__( 'Load More', 'sip-reviews-shortcode-woocommerce' ),
			'loader'			=> esc_url( SIP_RSWC_URL . 'public/img/ajax-loader.gif' )
		] );
	}
}