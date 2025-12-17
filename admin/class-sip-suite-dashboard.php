<?php

/**
 * SIP Suite Dashboard (Plugins/Themes tabs)
 *
 * Keeps dashboard logic isolated from the main admin class.
 * - Renders nav tabs (Plugins, Themes)
 * - Displays items provided via filters:
 *     - sip_dashboard_register_plugins  ➜ list of SIP plugin admin pages
 *     - sip_dashboard_register_themes   ➜ list of SIP themes/products
 * - Enqueues minimal CSS only on this screen
 */

namespace SIP_Reviews_Shortcode\Admin;

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class SIP_Suite_Dashboard {

	private string $version;
	private array  $plugin_entry; // this plugin’s card in “Plugins” tab
	private array  $themes;       // your theme cards for “Themes” tab
	public const MENU_SLUG = 'sip-suite';

	/**
	 * @param array $plugin_entry ['slug','menu_title','page_title','callback','img'(optional)]
	 * @param array $themes       list of ['title','desc','link','img'(optional)]
	 */
	public function __construct(string $version, array $plugin_entry = [], array $themes = []) {
		$this->version      = $version;
		$this->plugin_entry = $plugin_entry;
		$this->themes       = $themes;

		// Register defaults for both filters right here 
		add_filter('sip_dashboard_register_plugins',  [$this, 'filter_register_plugins']);
		add_filter('sip_dashboard_register_themes',   [$this, 'filter_register_themes']);
		add_action('admin_enqueue_scripts',           [ $this, 'enqueue' ] );
	}

	/*
	 * Add/merge this plugin’s card into the Plugins tab list 
	 */
	public function filter_register_plugins(array $plugins): array {

		$plugins = [
			[
				'title' => 'SIP Reviews Shortcode',
				'desc'  => 'Display product reviews in any post/page with a shortcode.',
				'link'  => SIP_RSWC_SHOPITPRESS_URL . 'plugins/sip-reviews-shortcode-woocommerce/',
				'img'   => SIP_RSWC_URL . 'admin/assets/images/Reviews-shortcode.png',
			],
			[
				'title' => 'SIP Advanced Email Rules',
				'desc'  => 'Powerful email automation.',
				'link'  => SIP_RSWC_SHOPITPRESS_URL . 'plugins/sip-advanced-email-notifications-for-woocommerce/',
				'img'   => SIP_RSWC_URL . 'admin/assets/images/Email-rules.png',
			],
			[
				'title' => 'SIP Social Proof',
				'desc'  => 'Show your sales and build trust.',
				'link'  => SIP_RSWC_SHOPITPRESS_URL . 'plugins/sip-social-proof-woocommerce/',
				'img'   => SIP_RSWC_URL . 'admin/assets/images/Social-proof.png',
			],
		];

		return $plugins;
	}

	/*
	 * Add your themes/cards to the Themes tab list 
	 */
	public function filter_register_themes(array $themes): array {

		// $themes[] = [
		//     'title' => 'SIP Classic Shop Theme',
		//     'desc'  => 'Modern WooCommerce theme with full SIP integration.',
		//     'link'  => 'https://your-site.example/themes/classic-shop/',
		//     'img'   => 'https://your-site.example/assets/classic-shop-thumb.jpg',
		// ];

		return $themes;
	}

	/*
	 * Enqueue minimal CSS only on our dashboard screen 
	 */
	public function enqueue( string $hook_suffix ): void {
		$on_dashboard = ( 'toplevel_page_' . self::MENU_SLUG ) === $hook_suffix;
		if ( ! $on_dashboard ) { return; }

		$css = '.sip-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;margin-top:16px}'
		. '.sip-card{background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:16px}'
		. '.sip-card h3{margin:0 0 8px;font-size:14px}'
		. '.sip-card p{margin:0 0 12px;color:#444}'
		. '.sip-card img{max-width:100%;height:auto;border-radius:6px;margin-bottom:10px}'
		. '.sip-grid .button{display:inline-block}';
		wp_register_style( 'sip-suite-dashboard', false, [], $this->version );
		wp_enqueue_style( 'sip-suite-dashboard' );
		wp_add_inline_style( 'sip-suite-dashboard', $css );
	}

	/*
	 * Render the dashboard page with two tabs 
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'sip-reviews-shortcode-woocommerce' ) );
		}
	
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- tab selection is read-only and does not perform any actions.
		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'plugins';
		$base_url   = admin_url( 'admin.php?page=' . self::MENU_SLUG );
		?>
		<div class="wrap sip-suite-wrap">
			<h1><?php esc_html_e( 'SIP Suite', 'sip-reviews-shortcode-woocommerce' ); ?></h1>

			<h2 class="nav-tab-wrapper">
				<a href="<?php echo esc_url( $base_url . '&tab=plugins' ); ?>" class="nav-tab <?php echo ( 'plugins' === $active_tab ) ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Plugins', 'sip-reviews-shortcode-woocommerce' ); ?></a>
				<a href="<?php echo esc_url( $base_url . '&tab=themes' ); ?>" class="nav-tab <?php echo ( 'themes' === $active_tab ) ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Themes', 'sip-reviews-shortcode-woocommerce' ); ?></a>
			</h2>

			<?php
			if ( 'themes' === $active_tab ) {
				$this->render_themes_tab();
			} else {
				$this->render_plugins_tab();
			}
			?>
		</div>
		<?php
	}

	/*
	 * Plugins tab: builds cards from sip_dashboard_register_plugins filter 
	 */
	private function render_plugins_tab(): void {
		$plugins = apply_filters( 'sip_dashboard_register_plugins', [] );
		$items  = is_array( $plugins ) ? $plugins : [];
		$this->render_card_grid( $items, esc_html__( 'No plugins found.', 'sip-reviews-shortcode-woocommerce' ) );
	}

	/*
	 * Themes tab: builds cards from sip_dashboard_register_themes filter 
	 */
	private function render_themes_tab(): void {
		$themes = apply_filters( 'sip_dashboard_register_themes', [] );
		$items  = is_array( $themes ) ? $themes : [];
		$this->render_card_grid( $items, esc_html__( 'No themes found.', 'sip-reviews-shortcode-woocommerce' ) );
	}

	/*
	 * Generic grid renderer 
	 */
	private function render_card_grid( array $items, string $empty_msg ): void {
		if ( empty( $items ) ) {
			echo '<p>' . esc_html( $empty_msg ) . '</p>';
			return;
		}
		echo '<div class="sip-grid">';
		foreach ( $items as $it ) {
			// Store raw-ish values first
			$title = isset( $it['title'] ) ? $it['title'] : '';
			$desc  = isset( $it['desc'] ) ? $it['desc']  : '';
			$link  = isset( $it['link'] ) ? $it['link']  : '';
			$img   = isset( $it['img'] )   ? $it['img']   : '';

			echo '<div class="sip-card">';

			if ( $img ) {
				echo '<img src="' . esc_url( $img ) . '" alt="' . esc_attr( $title ) . '" />';
			}

			if ( $title ) {
				echo '<h3>' . esc_html( $title ) . '</h3>';
			}

			if ( $desc ) {
				echo '<p>' . esc_html( $desc ) . '</p>';
			}

			if ( $link ) {
				echo '<p><a class="button button-primary" href="' . esc_url( $link ) . '">'
					. esc_html__( 'Open', 'sip-reviews-shortcode-woocommerce' )
					. '</a></p>';
			}

			echo '</div>';
		}
		echo '</div>';
	}
}