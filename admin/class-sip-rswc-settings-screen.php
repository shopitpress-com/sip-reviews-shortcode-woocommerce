<?php

/**
 * SIP Reviews Shortcode – Settings Screen (separate class)
 *
 * Renders a tabbed UI on the plugin page slug `sip-rswc-settings` with:
 *  - Settings  ➜ Outputs your existing Settings API sections/fields (incl. Color Options)
 *  - Help      ➜ Static guidance (customizable via filters)
 *  - Pro       ➜ Pro features overview (customizable via filters)
 *
 * Responsibilities of this class:
 *  - Provide a single public `render_page()` method to be used as the submenu callback
 *  - Enqueue Color Picker + minimal CSS only on this page (`enqueue()`)
 *  - Keep logic/self-contained and reusable
 *
 * Wiring example (inside your Admin class when registering menus):
 * ----------------------------------------------------------------
 *
 * Notes:
 *  - This class assumes your Settings API registration already attached sections/fields to the same PAGE_SLUG.
 *  - Color inputs must carry class `sip-color-field` to auto-init WP Color Picker.
 */

namespace SIP_Reviews_Shortcode\Admin;

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class SIP_RSWC_Settings_Screen {

	/*
	 * @var string 
	 */
	private string $version;

	/*
	 * @var string 
	 */
	private string $page_slug;

	/*
	 * @var string 
	 */
	private string $capability = 'manage_woocommerce';

	public function __construct( string $version, string $page_slug = 'sip-rswc-settings' ) {
		$this->version   = $version;
		$this->page_slug = $page_slug;
	}

	/**
	* Enqueue assets only on this plugin settings screen.
	* - WP Color Picker (for Color Options table)
	* - Minimal CSS for tab content spacing
	*/
	public function enqueue( string $hook_suffix ): void {
		$expected_hooks = [
			'sip-suite_page_' . $this->page_slug,   // if shown under SIP umbrella
			'sip_page_' . $this->page_slug,   // if shown under SIP umbrella
			'woocommerce_page_' . $this->page_slug, // if shown under WooCommerce (legacy)
			'toplevel_page_' . $this->page_slug,    // if single-plugin top-level
		];

		if ( ! in_array( $hook_suffix, $expected_hooks, true ) ) {
			return;
		}

		// Color Picker
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );

		wp_add_inline_script(
			'wp-color-picker',
			"jQuery(function($){ $('.sip-color-field').wpColorPicker(); });"
		);

		// Small CSS polish for the page
		$css = '.sip-rswc .nav-tab-wrapper{margin-bottom:12px}'
			. '.sip-rswc .card{background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:16px;max-width:100%;}'
			. '.sip-rswc .muted{color:#50575e}'
			. '.sip-rswc pre{background:#f6f7f7;border:1px solid #dcdcde;padding:12px;border-radius:6px;overflow:auto}'
			. '.sip-rswc .nav-tab-wrapper a:nth-child(3){background: #f05f28;color: #fff;}'
			. '.sip-pro h2 {background: #f05f28;color: #fff;margin: 0;padding: 15px;text-align:center;}'
			. '.card-list {list-style: none;padding: 0;margin: 0 0 0 10px;}'
			. '.card-list li {border-bottom: 1px solid #eee;padding: 15px 0;}'
			. '.card-list li:last-child {border-bottom: none;}'
			. '.card-list li strong {display: block;font-size: 1.1em;color: #f05f28;margin-bottom: 5px;}'
			. '.card-list li p {margin: 0;color: #555;font-size: 0.95em;}'
			. '.sip-pro-cta {text-align: center;margin-top: 25px;}'
			. '.sip-btn-pro {display: inline-block;padding: 12px 25px;background: #f05f28;color: #fff;font-weight: bold;border-radius: 5px;text-decoration: none;transition: background 0.3s;}'
			. '.sip-btn-pro:hover {color: #000;background: #e04e17;}';
		wp_register_style( 'sip-rswc-settings-ui', false, [], $this->version );
		wp_enqueue_style( 'sip-rswc-settings-ui' );
		wp_add_inline_style( 'sip-rswc-settings-ui', $css );
	}

	/*
	 * Public: Settings screen callback 
	 */
	public function render_page(): void {
		if ( ! current_user_can( $this->capability ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'sip-reviews-shortcode-woocommerce' ) );
		}

		$tab = $this->get_current_tab();
		?>
			<div class="wrap sip-rswc">
				<h1><?php esc_html_e( 'SIP Reviews Shortcode — Settings', 'sip-reviews-shortcode-woocommerce' ); ?></h1>
				<?php $this->render_tabs( $tab ); ?>

				<?php
					switch ( $tab ) {
						case 'help':
							$this->render_help();
						break;
						
						case 'pro':
							$this->render_pro();
						break;

						case 'settings':
						default:
							$this->render_settings();
			  		}
				?>
			</div>
		<?php
	}

	/* ------------------------------------ */
	/* Tabs                                 */
	/* ------------------------------------ */
	private function get_current_tab(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- tab selection is read-only and does not perform any actions.
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'settings';

		return in_array( $tab, [ 'settings', 'help', 'pro' ], true ) ? $tab : 'settings';
	}

	private function page_base_url(): string {
		return admin_url( 'admin.php?page=' . $this->page_slug );
	}

	private function render_tabs( string $active ): void {
		$base = $this->page_base_url();
		?>
		<h2 class="nav-tab-wrapper">
			<a href="<?php echo esc_url( $base . '&tab=settings' ); ?>" class="nav-tab <?php echo ( 'settings' === $active ) ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Settings', 'sip-reviews-shortcode-woocommerce' ); ?></a>
			<a href="<?php echo esc_url( $base . '&tab=help' ); ?>" class="nav-tab <?php echo ( 'help' === $active ) ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Help', 'sip-reviews-shortcode-woocommerce' ); ?></a>
			<a href="<?php echo esc_url( $base . '&tab=pro' ); ?>" class="nav-tab <?php echo ( 'pro' === $active ) ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Pro', 'sip-reviews-shortcode-woocommerce' ); ?></a>
		</h2>
		<?php
	}

	/* ------------------------------------ */
	/* Tab: Settings                         */
	/* ------------------------------------ */

	/**
	 * Prints the Settings API sections/fields that your Admin class registered
	 * against the same page slug. This includes the Color Options table field.
	 */
	private function render_settings(): void { ?>
		<div class="card">
			<form method="post" action="options.php">
				<?php
					// Your Admin class should have used the same OPTION_GROUP and PAGE_SLUG
					// e.g. register_setting( self::OPTION_GROUP, self::OPTION_COLORS, ... )
					settings_fields( SIP_Reviews_Shortcode_WC_Admin::OPTION_GROUP );
					do_settings_sections( $this->page_slug );
					submit_button( esc_html__( 'Save Changes', 'sip-reviews-shortcode-woocommerce' ) );
				?>
			</form>
		</div>
		<?php 
	}

	/* ------------------------------------ */
	/* Tab: Help                             */
	/* ------------------------------------ */

	private function render_help(): void { ?>
		<div class="card">
			<h2><?php esc_html_e( 'How the plugin works', 'sip-reviews-shortcode-woocommerce' ); ?></h2>
			<p class="muted"><?php esc_html_e( 'Use the shortcode to display WooCommerce product reviews anywhere on your site.', 'sip-reviews-shortcode-woocommerce' ); ?></p>

			<h3><?php esc_html_e( 'Basic usage', 'sip-reviews-shortcode-woocommerce' ); ?></h3>
			<pre>[sip_reviews id="123" limit="5" schema="true"]</pre>
			<ul class="ul-disc">
				<li><code>id</code> – <?php esc_html_e( 'Target a specific product ID; on single product pages this is optional.', 'sip-reviews-shortcode-woocommerce' ); ?></li>
				<li><code>limit</code> – <?php esc_html_e( 'Number of reviews to display.', 'sip-reviews-shortcode-woocommerce' ); ?></li>
				<li><code>schema</code> – <?php esc_html_e( 'Enable or disable Schema.org (JSON-LD) markup for better SEO.', 'sip-reviews-shortcode-woocommerce' ); ?></li>
			</ul>

			<h3><?php esc_html_e( 'Colors', 'sip-reviews-shortcode-woocommerce' ); ?></h3>
			<p><?php esc_html_e( 'All colors can be customized in the Settings tab. Changes will be applied automatically to the reviews displayed on your site.', 'sip-reviews-shortcode-woocommerce' ); ?></p>
			
			<h3><?php esc_html_e( 'Schema', 'sip-reviews-shortcode-woocommerce' ); ?></h3>
			<p>SIP Reviews Shortcode for WooCommerce is fully-compatible with Schema.org, allowing you to display product ratings directly from Google's results. You may test your Schema at <a href="https://developers.google.com/structured-data/testing-tool/">Google Structured Data Testing Tool</a>.</p>
			
			<h2>Questions and support</h2>
			<p>All of our plugins come with free support. We care about your plugin after purchase just as much as you do.</p>
			<p>We want to make your life easier and make you happy about choosing our plugins. We guarantee to respond to every inquiry within 1 business day. Please visit our <a href="https://shopitpress.com/community/?utm_source=wordpress.org&amp;utm_medium=backend-help&amp;utm_campaign=sip-reviews-shortcode-woocommerce" target="_blank">community</a> and ask us anything you need.</p>

			<h3><?php esc_html_e( 'Troubleshooting', 'sip-reviews-shortcode-woocommerce' ); ?></h3>
			<ol class="ol-decimal">
				<li><?php esc_html_e( 'Ensure WooCommerce is active.', 'sip-reviews-shortcode-woocommerce' ); ?></li>
				<li><?php esc_html_e( 'Purge cache after changing color options.', 'sip-reviews-shortcode-woocommerce' ); ?></li>
				<li><?php esc_html_e( 'Verify your theme does not override ".sip-reviews" selectors.', 'sip-reviews-shortcode-woocommerce' ); ?></li>
			</ol>
		</div>
		<?php
		// Allow 3rd-parties (or your Pro) to append extra help panels
		do_action( 'sip_rswc_help_after' );
	}

	/* ------------------------------------ */
	/* Tab: Pro                             */
	/* ------------------------------------ */
	private function render_pro(): void { ?>

		<div class="card sip-pro">
			<h2>Go Pro — Unlock more features</h2>
			<ul class="card-list">
				<li>
					<strong>Priority Support</strong>
					<p>Get fast and dedicated support for all your plugin queries.</p>
				</li>
				<li>
					<strong>Display Reviews with a Shortcode</strong>
					<p>Easily show reviews anywhere on your site using our shortcode.</p>
				</li>
				<li>
					<strong>Easy Color Customization</strong>
					<p>Fully customize review colors to match your site design.</p>
				</li>
				<li>
					<strong>Integrate Product Schema</strong>
					<p>Boost SEO with Schema.org structured data for your reviews.</p>
				</li>
				<li>
					<strong>Display Submit Review Form with a Shortcode</strong>
					<p>Allow users to submit reviews anywhere on your site.</p>
				</li>
				<li>
					<strong>2 Extra Beautiful Styles</strong>
					<p>Choose from additional attractive review layouts.</p>
				</li>
				<li>
					<strong>Aggregated Reviews</strong>
					<p>Combine reviews from multiple products or categories.</p>
				</li>
				<li>
					<strong>Multi-Language Support</strong>
					<p>Fully compatible with WPML, Polylang, and other translation plugins.</p>
				</li>
				<li>
					<strong>"Write a Review" CTA and Capture Form</strong>
					<p>Encourage user engagement with a prominent CTA and review capture form.</p>
				</li>
			</ul>
			<div class="sip-pro-cta">
				<a href="<?php echo esc_url( SIP_RSWC_SHOPITPRESS_URL ); ?>plugins/sip-reviews-shortcode-woocommerce/" class="sip-btn sip-btn-pro" target="_blank">Upgrade to Pro</a>
			</div>
		</div>
		<?php
		// Allow more content below from add-ons
		do_action( 'sip_rswc_pro_after' );
	}
}