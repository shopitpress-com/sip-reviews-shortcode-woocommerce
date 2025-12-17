<?php

/**
 * Admin functionality for SIP Reviews Shortcode for WooCommerce (refactored).
 *
 * Modernized for WP 6.7+ / WooCommerce 10.3+ (PHP 8.1+).
 * - Replaces deprecated hooks (e.g., media_buttons_context ➜ media_buttons)
 * - Uses Settings API with nonces and capability checks
 * - Avoids deprecated Select2 handle (use wc-enhanced-select)
 * - Sanitizes all inputs and escapes all outputs
 * - Adds dismissible admin notice stored per-user
 *
 * @package   SIP_Reviews_Shortcode\Admin
 */

namespace SIP_Reviews_Shortcode\Admin;

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class SIP_Reviews_Shortcode_WC_Admin {

	/** @var string */
	private string $plugin_name;

	/** @var string */
	private string $version;

	/** @var string Base64 SVG data URI used as the top-level menu icon */
	private string $icon_url;

	/** @var string Settings page slug */
	public const PAGE_SLUG = 'sip-rswc-settings';

	/** @var string Umbrella SIP menu slug in multi-plugin mode */
	public const PARENT_MENU_SLUG = 'sip-suite';

	/** @var string Settings option name */
	public const OPTION_NAME = 'sip_rswc_options';

	/** @var string Option group */
	public const OPTION_GROUP = 'sip_rswc_group';

	/** @var string Settings color options */
	public const OPTION_COLORS = 'sip_rswc_color_options';

	private SIP_Suite_Dashboard $dashboard;

	private $settings_screen;



	/**
	 * @param string $plugin_name
	 * @param string $version
	 */
	public function __construct( string $plugin_name, string $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		// Allow overriding icon via a filter; default is our embedded SVG
		$this->icon_url = wp_kses_post( apply_filters( 'sip_suite_icon_url', $this->get_default_icon_svg() ));

		// Admin menus & settings
		add_action( 'admin_menu', [ $this, 'register_admin_menus' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );

		// Enqueue
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin' ] );

		// Action links on the Plugins screen
		$basename = $this->guess_basename();
		add_filter( 'plugin_action_links_' . $basename, [ $this, 'plugin_action_links' ] );

		// Admin notices (dismissible)
		add_action( 'admin_notices', [ $this, 'maybe_show_admin_notice' ] );
		add_action( 'admin_init', [ $this, 'maybe_dismiss_notice' ] );

		// Replace deprecated media_buttons_context with media_buttons
		add_action( 'media_buttons', [ $this, 'render_editor_button' ], 15 );

		$this->dashboard = new \SIP_Reviews_Shortcode\Admin\SIP_Suite_Dashboard( $this->version );

		$this->settings_screen = new \SIP_Reviews_Shortcode\Admin\SIP_RSWC_Settings_Screen(
			$this->version,
			\SIP_Reviews_Shortcode\Admin\SIP_Reviews_Shortcode_WC_Admin::PAGE_SLUG // 'sip-rswc-settings'
		);

		add_action( 'admin_enqueue_scripts', [ $this->settings_screen, 'enqueue' ] );
	}

	/**
	 * Best effort to get this plugin's basename when the class file is not the main plugin file.
	 */
	private function guess_basename(): string {
		// If the main plugin defines a constant, prefer that.
		if ( defined( 'SIP_RSWC_BASENAME' ) && is_string( \SIP_RSWC_BASENAME ) ) {
			return \SIP_RSWC_BASENAME;
		}
		// Fallback: compute from known main plugin file if available; otherwise, from this file.
		return plugin_basename( dirname( __DIR__, 1 ) . '/sip-reviews-shortcode-for-woocommerce.php' );
	}

	/**
	 * Register admin menus.
	 */
	public function register_admin_menus(): void {
		$cap = 'manage_woocommerce';

		// 1) Gather all SIP plugins via a shared filter. Activation order doesn't matter
		// because add_filter hooks are registered before 'admin_menu' fires.
		$plugins = apply_filters(
			'sip_suite_register_plugins',
			[
				'sip-reviews-shortcode-woocommerce' => [
					'page_title' => esc_html__( 'SIP Reviews Shortcode', 'sip-reviews-shortcode-woocommerce' ),
					'menu_title' => esc_html__( 'SIP Reviews Shortcode', 'sip-reviews-shortcode-woocommerce' ),
					'slug'       => self::PAGE_SLUG,
					'callback'   => [ $this->settings_screen, 'render_page' ],
					'position'   => 58,
				],
			]
		);
		if ( ! is_array( $plugins ) || empty( $plugins ) ) {
			$plugins = [ 'sip-reviews-shortcode-woocommerce' => [
				'page_title' => esc_html__( 'SIP Reviews Shortcode', 'sip-reviews-shortcode-woocommerce' ),
				'menu_title' => esc_html__( 'SIP Reviews Shortcode', 'sip-reviews-shortcode-woocommerce' ),
				'slug'       => self::PAGE_SLUG,
				'callback'   => [ $this->settings_screen, 'render_page' ],
				'position'   => 58,
			] ];
		}

		// 2) Single-plugin mode: show it as a top-level menu.
		if ( count( $plugins ) <= 1 ) {
			// $only = reset( $plugins );
			$first = reset( $plugins );

			add_menu_page(
				esc_html__( 'SIP', 'sip-reviews-shortcode-woocommerce' ),
				esc_html__( 'SIP', 'sip-reviews-shortcode-woocommerce' ),
				$cap,
				self::PARENT_MENU_SLUG,
				[ $this->dashboard, 'render_page' ],//[ $this, 'sip_suite_dashboard_page' ],//$first['callback']$first['callback'], // default landing page shows the first plugin screen
				$this->icon_url,
				58
			);//the same slug.
		
			add_submenu_page(
				self::PARENT_MENU_SLUG,
				esc_html__( 'Dashboard', 'sip-reviews-shortcode-woocommerce' ),
				esc_html__( 'Dashboard', 'sip-reviews-shortcode-woocommerce' ),
				$cap,
				self::PARENT_MENU_SLUG,
				[ $this->dashboard, 'render_page' ]//[ $this, 'sip_suite_dashboard_page' ]//$first['callback']
			);
		}

		// 4) Register each SIP plugin as a submenu under the umbrella.
		foreach ( $plugins as $p ) {
			add_submenu_page(
				self::PARENT_MENU_SLUG,
				esc_html( $p['page_title'] ),
				esc_html( $p['menu_title'] ),
				$cap,
				$p['slug'],
				$p['callback']
			);
		}
	}

	/**
	 * Register settings via Settings API.
	 */
	public function register_settings(): void {

		// Colors section
		add_settings_section(
			'sip_rswc_colors',
			esc_html__( 'Colors', 'sip-reviews-shortcode-woocommerce' ),
			function (): void {
				echo '<p>' . esc_html__( 'Pick colors for stars, bars, review backgrounds, titles and buttons.', 'sip-reviews-shortcode-woocommerce' ) . '</p>';
			},
			self::PAGE_SLUG
		);

		// Register the color options array
		register_setting(
			self::OPTION_GROUP,
			self::OPTION_COLORS,
			[
				'type'              => 'array',
				'sanitize_callback' => [ $this, 'sanitize_colors' ],
				'default'           => [
					'star_color'              => '#c62437',
					'bar_color'               => '#3f51b5',
					'review_background_color' => '#ffffff',
					'review_body_text_color'  => '#333333',
					'review_title_color'      => '#111111',
					'load_more_button'        => '#3f51b5',
					'load_more_text'          => '#ffffff',
				],
			]
		);

		// Render the table (your layout) as one field
		add_settings_field(
			'color_options',
			esc_html__( 'Color Options', 'sip-reviews-shortcode-woocommerce' ),
			[ $this, 'field_color_options' ],
			self::PAGE_SLUG,
			'sip_rswc_colors'
		);
	}


	public function field_color_options(): void {
	    $colors = get_option( self::OPTION_COLORS, [] );

	    // Sanitize when reading from the DB
	    $star_color              = ! empty( $colors['star_color'] ) ? sanitize_hex_color( $colors['star_color'] ) : '#c62437';
	    $bar_color               = ! empty( $colors['bar_color'] ) ? sanitize_hex_color( $colors['bar_color'] ) : '#3f51b5';
	    $review_background_color = ! empty( $colors['review_background_color'] ) ? sanitize_hex_color( $colors['review_background_color'] ) : '#ffffff';
	    $review_body_text_color  = ! empty( $colors['review_body_text_color'] ) ? sanitize_hex_color( $colors['review_body_text_color'] ) : '#333333';
	    $review_title_color      = ! empty( $colors['review_title_color'] ) ? sanitize_hex_color( $colors['review_title_color'] ) : '#111111';
	    $load_more_button        = ! empty( $colors['load_more_button'] ) ? sanitize_hex_color( $colors['load_more_button'] ) : '#3f51b5';
	    $load_more_text          = ! empty( $colors['load_more_text'] ) ? sanitize_hex_color( $colors['load_more_text'] ) : '#ffffff';

	    // Fallback if sanitize_hex_color() returns null/false
	    $star_color              = $star_color              ?: '#c62437';
	    $bar_color               = $bar_color               ?: '#3f51b5';
	    $review_background_color = $review_background_color ?: '#ffffff';
	    $review_body_text_color  = $review_body_text_color  ?: '#333333';
	    $review_title_color      = $review_title_color      ?: '#111111';
	    $load_more_button        = $load_more_button        ?: '#3f51b5';
	    $load_more_text          = $load_more_text          ?: '#ffffff';
	    ?>
	    <table class="form-table" role="presentation">
	        <tr>
	            <td width="250"><strong><?php esc_html_e( 'Review stars', 'sip-reviews-shortcode-woocommerce' ); ?></strong></td>
	            <td>
	                <input
	                    class="sip-color-field"
	                    id="star-color"
	                    name="<?php echo esc_attr( self::OPTION_COLORS ); ?>[star_color]"
	                    type="text"
	                    value="<?php echo esc_attr( $star_color ); ?>"
	                />
	                <div id="star-colorpicker"></div>
	            </td>
	        </tr>
	        <tr>
	            <td><strong><?php esc_html_e( 'Reviews bar summary', 'sip-reviews-shortcode-woocommerce' ); ?></strong></td>
	            <td>
	                <input
	                    class="sip-color-field"
	                    id="bar-color"
	                    name="<?php echo esc_attr( self::OPTION_COLORS ); ?>[bar_color]"
	                    type="text"
	                    value="<?php echo esc_attr( $bar_color ); ?>"
	                />
	                <div id="bar-colorpicker"></div>
	            </td>
	        </tr>
	        <tr>
	            <td><strong><?php esc_html_e( 'Review background', 'sip-reviews-shortcode-woocommerce' ); ?></strong></td>
	            <td>
	                <input
	                    class="sip-color-field"
	                    id="review-background-color"
	                    name="<?php echo esc_attr( self::OPTION_COLORS ); ?>[review_background_color]"
	                    type="text"
	                    value="<?php echo esc_attr( $review_background_color ); ?>"
	                />
	                <div id="review-background-colorpicker"></div>
	            </td>
	        </tr>
	        <tr>
	            <td><strong><?php esc_html_e( 'Review body text', 'sip-reviews-shortcode-woocommerce' ); ?></strong></td>
	            <td>
	                <input
	                    class="sip-color-field"
	                    id="review-body-text-color"
	                    name="<?php echo esc_attr( self::OPTION_COLORS ); ?>[review_body_text_color]"
	                    type="text"
	                    value="<?php echo esc_attr( $review_body_text_color ); ?>"
	                />
	                <div id="review-body-text-colorpicker"></div>
	            </td>
	        </tr>
	        <tr>
	            <td><strong><?php esc_html_e( 'Review title', 'sip-reviews-shortcode-woocommerce' ); ?></strong></td>
	            <td>
	                <input
	                    class="sip-color-field"
	                    id="review-title-color"
	                    name="<?php echo esc_attr( self::OPTION_COLORS ); ?>[review_title_color]"
	                    type="text"
	                    value="<?php echo esc_attr( $review_title_color ); ?>"
	                />
	                <div id="review-title-colorpicker"></div>
	            </td>
	        </tr>
	        <tr>
	            <td><strong><?php esc_html_e( 'Load more button background', 'sip-reviews-shortcode-woocommerce' ); ?></strong></td>
	            <td>
	                <input
	                    class="sip-color-field"
	                    id="load-more-button-color"
	                    name="<?php echo esc_attr( self::OPTION_COLORS ); ?>[load_more_button]"
	                    type="text"
	                    value="<?php echo esc_attr( $load_more_button ); ?>"
	                />
	                <div id="load-more-button-colorpicker"></div>
	            </td>
	        </tr>
	        <tr>
	            <td><strong><?php esc_html_e( 'Load more button text', 'sip-reviews-shortcode-woocommerce' ); ?></strong></td>
	            <td>
	                <input
	                    class="sip-color-field"
	                    id="load-more-button-text-color"
	                    name="<?php echo esc_attr( self::OPTION_COLORS ); ?>[load_more_text]"
	                    type="text"
	                    value="<?php echo esc_attr( $load_more_text ); ?>"
	                />
	                <div id="load-more-button-text-colorpicker"></div>
	            </td>
	        </tr>
	    </table>
	    <?php
	}

	public function sanitize_colors( $input ): array {
		$defaults = [
			'star_color'              => '#c62437',
			'bar_color'               => '#3f51b5',
			'review_background_color' => '#ffffff',
			'review_body_text_color'  => '#333333',
			'review_title_color'      => '#111111',
			'load_more_button'        => '#3f51b5',
			'load_more_text'          => '#ffffff',
		];
		$clean = [];

		foreach ( $defaults as $key => $fallback ) {
			$val = is_array( $input ) && isset( $input[ $key ] ) ? $input[ $key ] : $fallback;
			$hex = sanitize_hex_color( $val );
			$clean[ $key ] = $hex ? $hex : $fallback;
		}

		return $clean;
	}


	/**
	 * Enqueue admin assets.
	 */
	public function enqueue_admin( string $hook_suffix ): void {

		// Enqueue only on our settings page and post edit screens where the button is needed.
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}

		$on_settings = ( 'woocommerce_page_' . self::PAGE_SLUG ) === $hook_suffix;
		$on_editor   = in_array( $screen->base, [ 'post', 'page' ], true );

		if( 'woocommerce_page_sip-rswc-settings' != 'woocommerce_page_' . self::PAGE_SLUG) {
			return;
		}

		// Styles
		wp_register_style( 'sip-rswc-admin', plugins_url( 'admin/assets/css/custom.css', $this->main_plugin_file() ), [], $this->version );
		wp_enqueue_style( 'sip-rswc-admin' );

		// Are we on our settings page?
		$on_settings = ( 'sip-rswc-settings' === self::PAGE_SLUG );

		if ( $on_settings ) {
			// WordPress color picker assets
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( 'wp-color-picker' );

			// Initialize color pickers
			wp_add_inline_script(
				'wp-color-picker',
				"jQuery(function($){ $('.sip-color-field').wpColorPicker(); });"
			);
		}
	}

	/**
	 * Plugins screen: add Settings/Docs links.
	 *
	 * @param array $links
	 * @return array
	 */
	public function plugin_action_links( array $links ): array {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) ),
			esc_html__( 'Settings', 'sip-reviews-shortcode-woocommerce' )
		);
		$docs_link = sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_url( 'https://shopitpress.com/plugins/sip-reviews-shortcode-woocommerce/?utm_source=wp-admin&utm_medium=plugin-list&utm_campaign=sip-reviews-shortcode' ),
			esc_html__( 'Docs', 'sip-reviews-shortcode-woocommerce' )
		);
		array_unshift( $links, $settings_link, $docs_link );
		return $links;
	}

	/**
	 * Show a dismissible admin notice on relevant admin screens.
	 */
	public function maybe_show_admin_notice(): void {
		if ( get_user_meta( get_current_user_id(), 'sip_rswc_notice_dismissed', true ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || ( 'plugins' !== $screen->base && strpos( (string) $screen->id, 'woocommerce_page_' . self::PAGE_SLUG ) !== 0 ) ) {
			return;
		}
		$url = wp_nonce_url( add_query_arg( 'sip_rswc_dismiss', '1', admin_url() ), 'sip_rswc_dismiss' );
		?>
		<div class="notice notice-info is-dismissible sip-rswc-notice">
			<p><strong><?php esc_html_e( 'Thanks for using SIP Reviews Shortcode for WooCommerce!', 'sip-reviews-shortcode-woocommerce' ); ?></strong></p>
			<p><?php esc_html_e( 'You can configure defaults under SIP → SIP Reviews Shortcode.', 'sip-reviews-shortcode-woocommerce' ); ?></p>
			<p><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) ); ?>"><?php esc_html_e( 'Open Settings', 'sip-reviews-shortcode-woocommerce' ); ?></a>
				<a class="button button-link" href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'Dismiss', 'sip-reviews-shortcode-woocommerce' ); ?></a>
			</p>
		</div>
		<?php
	}

	/**
	 * Handle notice dismissal.
	 */
	public function maybe_dismiss_notice(): void {
		if ( isset( $_GET['sip_rswc_dismiss'] ) && '1' === $_GET['sip_rswc_dismiss'] && check_admin_referer( 'sip_rswc_dismiss' ) ) {
			update_user_meta( get_current_user_id(), 'sip_rswc_notice_dismissed', 1 );
			wp_safe_redirect( remove_query_arg( [ 'sip_rswc_dismiss', '_wpnonce' ] ) );
			exit;
		}
	}

	/**
	 * Render a TinyMCE/Block editor button (uses the classic media buttons area).
	 * Replaces the old deprecated media_buttons_context filter.
	 */
	public function render_editor_button( string $editor_id = 'content' ): void {
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->base, [ 'post', 'page' ], true ) ) {
			return;
		}

		// Load products for dropdown (WooCommerce).
		$products = get_posts([
			'post_type'      => 'product',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		]);

		add_thickbox();
		$title = esc_attr__( 'Insert Product Reviews Shortcode', 'sip-reviews-shortcode-woocommerce' );

		$button = sprintf(
			'<a href="#TB_inline?width=640&height=450&inlineId=sip-rswc-shortcode-popup" class="button thickbox sip-rswc-insert" data-editor="%1$s" title="%2$s">%3$s</a>',
			esc_attr( $editor_id ),
			$title,
			esc_html__( 'Product Reviews', 'sip-reviews-shortcode-woocommerce' )
		);

		echo $button; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>

		<div id="sip-rswc-shortcode-popup" style="display:none;">
			<div class="sip-rswc-popup-wrap">
				<h2><?php esc_html_e( 'Insert Reviews Shortcode', 'sip-reviews-shortcode-woocommerce' ); ?></h2>

				<!-- PRODUCT DROPDOWN -->
				<p>
					<label><?php esc_html_e( 'Select Product:', 'sip-reviews-shortcode-woocommerce' ); ?></label><br>
					<select id="sip-rswc-product-id" style="width:100%;max-width:300px;">
						<option value=""><?php esc_html_e( '— Select Product —', 'sip-reviews-shortcode-woocommerce' ); ?></option>
						<?php foreach ( $products as $product ) : ?>
							<option value="<?php echo esc_attr( $product->ID ); ?>">
								<?php echo esc_html( $product->post_title . ' (ID: ' . $product->ID . ')' ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</p>

				<!-- LIMIT -->
				<p>
					<label>
						<?php esc_html_e( 'Limit:', 'sip-reviews-shortcode-woocommerce' ); ?>
						<input type="number" min="1" max="50" id="sip-rswc-limit" value="5" />
					</label>
				</p>

				<!-- SCHEMA OPTION -->
				<p>
					<label><?php esc_html_e( 'Schema Markup:', 'sip-reviews-shortcode-woocommerce' ); ?></label><br>
					<select id="sip-rswc-schema">
						<option value="true"><?php esc_html_e( 'True', 'sip-reviews-shortcode-woocommerce' ); ?></option>
						<option value="false"><?php esc_html_e( 'False', 'sip-reviews-shortcode-woocommerce' ); ?></option>
					</select>
				</p>

				<p>
					<button type="button" class="button button-primary" id="sip-rswc-insert-shortcode">
						<?php esc_html_e( 'Insert Shortcode', 'sip-reviews-shortcode-woocommerce' ); ?>
					</button>
				</p>
			</div>
		</div>

		<script>
			jQuery(function($){
				$(document).on('click', '#sip-rswc-insert-shortcode', function(){

					var productID = $('#sip-rswc-product-id').val();
					var limit     = parseInt($('#sip-rswc-limit').val(), 10) || 5;
					var schema    = $('#sip-rswc-schema').val();

					if (!productID) {
						alert("Please select a product.");
						return;
					}

					// Generate shortcode exactly as required:
					var shortcode = '[sip_reviews id="' + productID + '" limit="' + limit + '" schema="' + schema + '"]';

					// Insert into Classic Editor
					if (window.send_to_editor) {
						window.send_to_editor(shortcode);

					// Insert into Gutenberg
					} else if (wp && wp.data) {
						wp.data.dispatch('core/editor').insertBlocks(
							wp.blocks.createBlock('core/shortcode', { text: shortcode })
							);
					}

					// Close popup
					if (typeof tb_remove === 'function') {
						tb_remove();
					}
				});
			});
		</script>

		<?php
	}


	/**
	 * Helper to get the main plugin file path (for plugins_url()).
	 */
	private function main_plugin_file(): string {
		// Adjust if your main plugin bootstrap has a different filename.
		return dirname( __DIR__, 1 ) . '/sip-reviews-shortcode-for-woocommerce.php';
	}

	/**
	 * Default embedded SVG icon (32x32). You can replace via the 'sip_suite_icon_url' filter.
	 */
	private function get_default_icon_svg(): string {
		return 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz48IURPQ1RZUEUgc3ZnIFBVQkxJQyAiLS8vVzNDLy9EVEQgU1ZHIDEuMS8vRU4iICJodHRwOi8vd3d3LnczLm9yZy9HcmFwaGljcy9TVkcvMS4xL0RURC9zdmcxMS5kdGQiPjxzdmcgdmVyc2lvbj0iMS4xIiBpZD0iTGF5ZXJfMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgeD0iMHB4IiB5PSIwcHgiIHdpZHRoPSI0MHB4IiBoZWlnaHQ9IjMycHgiIHZpZXdCb3g9IjAgNTAgNzI1IDQ3MCIgZW5hYmxlLWJhY2tncm91bmQ9Im5ldyAwIDAgNzI1IDQ3MCIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSI+PGc+PHBhdGggZmlsbD0iI0ZGRkZGRiIgZD0iTTY0MC4zMjEsNDguNTk4YzI4LjU0LDAsNDMuNzI5LDI5Ljc5MiwzMi4xNzIsNTUuMTU4bC03Ni40MTYsMTY2Ljk1NGMtMTIuMDMyLTMyLjM0Ni01MC41NjUtNTUuNzU3LTg3LjktNjkuMTczYy00OC44NjItMTcuNjAyLTEyNy44NDMtMjEuODE5LTE5MC4wOTQtMzAuMzc5Yy0zNC4zMjEtNC42NjEtMTEwLjExOC0xMi43NS05Ny43OC01My4xMTVjMTMuMjM5LTQzLjA3NCw5Ni40ODEtNDcuNTkxLDEzMy44OC00Ny41OTFjODYuMTI5LDAsMTYwLjk1NCwxOS43NzEsMTYwLjk1NCw4My44NjZoOTkuNzQxVjQ4LjU5OEg2NDAuMzIxeiBNNTQzLjc5NiwxMDUuNTk0Yy03LjEwNS0yNy40NTgtMzIuMjc3LTQ4LjcxNy01OS4xNjktNTYuOTk3aDgyLjc3NkM1NjYuMjgxLDY2LjYxMyw1NTUuNDQ4LDk0LjE4MSw1NDMuNzk2LDEwNS41OTRMNTQzLjc5NiwxMDUuNTk0eiBNNTUwLjY0MSwzNzAuMTIzbC0xMy42MTEsMjkuNzIzYy02LjAzOCwxMy4yNzktMTkuMzI3LDIxLjYzNS0zMy45MjcsMjEuNjM1SDIyMS45NjljLTE0LjY2NiwwLTI3Ljk1NS04LjM1NS0zNC4wMDMtMjEuNjM1bC0xNS44NDQtMzQuNzIzYzEwLjkxMiwxNC43NDgsMjkuMzMxLDIzLjA4LDQ5LjA5OCwyOC4yODFDMzEzLjE1LDQxNy43MzIsNDY4LjUzNSw0MjEuNDgsNTUwLjY0MSwzNzAuMTIzTDU1MC42NDEsMzcwLjEyM3ogTTE2My43NjEsMzQ2Ljk5bC01OC4xNi0xMjcuMjQzYzE0LjY0MSwxNS42NTUsMzcuNjAxLDI3LjM2LDY2LjcyNCwzNi4yOTdjODUuNDA5LDI2LjI0MiwyMTMuODI1LDIyLjIyOSwyOTYuMjU0LDM1LjExN2M0MS45NDksNi41NjEsNDMuODU3LDQ3LjA4OCwxMy4yODksNjEuOTQ3Yy01Mi4zMzQsMjUuNTA2LTEzNS4yNDUsMjUuMzU5LTE5NC45NTcsMTEuNjk1QzIzNy4yMTksMjg1LjI1LDE1NS44MTksMzA0LjQ5LDE2My43NjEsMzQ2Ljk5TDE2My43NjEsMzQ2Ljk5eiBNODUuODY4LDE3Ni42OTJsLTMzLjM0Ni03Mi45MzdDNDAuOTQ5LDc4LjM5LDU2LjEzMSw0OC41OTgsODQuNjY5LDQ4LjU5OGgxMzYuOTY2QzE1OS43NTEsNjYuMTU0LDc3LjEwNSwxMTAuNjcsODUuODY4LDE3Ni42OTJMODUuODY4LDE3Ni42OTJ6Ii8+PHBhdGggZmlsbD0iI0ZGRkZGRiIgZD0iTTM2Mi41MywwLjA4NmgyNzcuNzkyYzYzLjk2NiwwLDEwMi4xODUsNjYuNzk1LDc2LjEzNSwxMjMuNzI2TDU4MS4wMzEsNDE5Ljk4NEM1NjcuMTQ3LDQ1MC4yODEsNTM2LjQzNSw0NzAsNTAzLjEwMyw0NzBIMzYyLjUzSDIyMS44OTJjLTMzLjM0NSwwLTY0LjA0My0xOS43MTktNzcuOTE3LTUwLjAxNkw4LjUzNSwxMjMuODEyQy0xNy40OTMsNjYuODgyLDIwLjY5MywwLjA4Niw4NC42NjksMC4wODZIMzYyLjUzeiBNMzYyLjUzLDIzLjk0Mkg4NC42NjljLTQ2LjIxOCwwLTczLjU2OCw0OC4yNjYtNTQuNDMsOTAuMDExbDEzNS4zNjIsMjk2LjA3OGMxMC4wNzIsMjEuOTYxLDMyLjIyNSwzNi4xMDUsNTYuMjkxLDM2LjEwNUgzNjIuNTNoMTQwLjU3M2MyNC4wNjcsMCw0Ni4yMTktMTQuMTQ1LDU2LjI3Ny0zNi4xMDVsMTM1LjM4Ni0yOTYuMDc4YzE5LjE0LTQxLjc0NS04LjIyNi05MC4wMTEtNTQuNDQ0LTkwLjAxMUgzNjIuNTN6Ii8+PC9nPjwvc3ZnPg==';
	}
}