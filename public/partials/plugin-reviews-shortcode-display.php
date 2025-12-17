<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Provide a public-facing view for the plugin
 * Handles display logic, AJAX, and shortcode for SIP Reviews Shortcode plugin.
 * 
 * This file is used to markup the public-facing aspects of the plugin.
 *
 * @link       http://shopitpress.com
 * @since      1.0.0
 *
 * @package    Sip_Reviews_Shortcode_Woocommerce
 * @subpackage Sip_Reviews_Shortcode_Woocommerce/public/partials
 */
class SIP_RSWC_Display {

    /**
     * Initialize hooks.
     *
     * @since 1.0.0
     */
    public static function init() {
		add_shortcode( 'sip_reviews', [ __CLASS__, 'render_shortcode' ] );
		add_action( 'wp_ajax_sip_rswc_load_more_reviews', [ __CLASS__, 'ajax_load_more_reviews' ] );
		add_action( 'wp_ajax_nopriv_sip_rswc_load_more_reviews', [ __CLASS__, 'ajax_load_more_reviews' ] );
		add_action( 'wp_ajax_sip_rswc_filter_reviews_by_rating', [ __CLASS__, 'ajax_filter_reviews_by_rating' ] );
		add_action( 'wp_ajax_nopriv_sip_rswc_filter_reviews_by_rating', [ __CLASS__, 'ajax_filter_reviews_by_rating' ] );
	}

    /**
     * Shortcode handler for displaying WooCommerce reviews.
     *
     * @since 1.0.0
     */
	public static function render_shortcode( $atts ) {
		global $product;

		$atts = shortcode_atts(
			array(
				'id' => 0,
				'limit' => 5,
				'schema' => false,
			),
			$atts,
			'sip_reviews'
		);

		// Sanitize and cast values
		$product_id = absint( $atts['id'] );
		$limit		= absint( $atts['limit'] );
		$schema     = filter_var( $atts['schema'], FILTER_VALIDATE_BOOLEAN );


		// If ID not provided, try to get current product
		if ( empty( $product_id ) && isset( $product ) && is_a( $product, 'WC_Product' ) ) {
			$product_id = $product->get_id();
		}

		// Fallback — if still no ID, bail early
		if ( empty( $product_id ) ) {
			return esc_html__( 'No product found for reviews.', 'sip-reviews-shortcode-woocommerce' );
		}

		// To check that post id is product or not
		if( get_post_type( $product_id ) == 'product' ) {
			ob_start();

			if ( empty( $product_id ) || ! is_numeric( $product_id ) ) {
				return;
			}
			// to get the detail of the comments etc aproved and panding status
			$comments_count = wp_count_comments( $product_id );
			$get_avg_rating = SIP_RSWC_DB_Helper::sip_get_avg_rating( $product_id );
			$get_review_count 	= SIP_RSWC_DB_Helper::sip_get_review_count( $product_id );
			// $get_price = SIP_RSWC_DB_Helper::sip_get_price( $product_id );
			SIP_RSWC_Schema_Helper::set_product_id( $product_id );
			SIP_RSWC_Schema_Helper::set_enabled( $schema );
			?>

			<!-- SIP Reviews Shortcode for WooCommerce -->
			<div class="sip-rswc-wrap sip-reviews">
				<div class="sip-rswc-container">
					
					<!-- Review Summary -->
					<div class="sip-rswc-summary">
						
						<div class="sip-rswc-summary-left">
							<div class="sip-rswc-summary-rating">
								<?php echo esc_html( $get_avg_rating ); ?> 
								<?php esc_html_e( 'out of 5 stars', 'sip-reviews-shortcode-woocommerce' ); ?>
							</div>
							<div class="sip-rswc-summary-count">
								<?php echo esc_html( $get_review_count ); ?> 
								<span class="sip-rswc-summary-label">
									<?php esc_html_e( 'reviews', 'sip-reviews-shortcode-woocommerce' ); ?>
								</span>
							</div>
						</div>

						<div class="sip-rswc-summary-right">
							<div class="sip-rswc-rating-details">
								<table class="sip-rswc-rating-table" data-product-id="<?php echo esc_attr( $product_id ); ?>" >
									<tbody>
										<?php
										$get_rating_count = SIP_RSWC_DB_Helper::sip_get_rating_count( $product_id );

										for ( $i = 5; $i > 0; $i-- ) :
											$count      = isset( $get_rating_count[ $i ] ) ? (int) $get_rating_count[ $i ] : 0;
											$percentage = $get_review_count > 0 ? ( $count / $get_review_count ) * 100 : 0;
											$url        = get_permalink();
											?>
											<tr class="sip-rswc-rating-row">
												<td class="sip-rswc-star-label">
													<a href="javascript:void(0);"
													   class="sip-rswc-rating-data-count"
													   data-count="<?php echo esc_attr( $i ); ?>"
													   aria-label="<?php
														   printf(
														   		/* translators: %d: star rating value (1–5). */
																esc_attr__( '%d star rating', 'sip-reviews-shortcode-woocommerce' ),
																absint( $i )
														   );
													   ?>">
														<?php echo esc_html( $i ); ?> <span class="sip-rswc-star"></span>
													</a>
												</td>

												<td class="sip-rswc-bar-cell">
													<div class="sip-rswc-bar-wrapper">
														<span class="sip-rswc-bar"
														      style="--target-width: <?php echo esc_attr( $percentage ); ?>%; width: <?php echo esc_attr( $percentage ); ?>%;"></span>
													</div>
												</td>

												<td class="sip-rswc-rating-count">
													<a href="javascript:void(0);"
													   class="sip-rswc-rating-data-count"
													   data-count="<?php echo esc_attr( $i ); ?>"
													   aria-label="<?php
														   printf(
														   		/* translators: 1: number of reviews, 2: star rating value (1–5). */
																esc_attr__( '%1$d reviews with %2$d stars', 'sip-reviews-shortcode-woocommerce' ),
																absint( $count ),
																absint( $i )
														   );
													   ?>">
														<?php echo esc_html( $count ); ?>
													</a>
												</td>
											</tr>
										<?php endfor; ?>
									</tbody>

								</table>
							</div>
						</div>
					</div>

					<!-- Reviews Section -->
					<div class="sip-rswc-tabs-wrap">
						<div class="sip-rswc-tabs-content">
							<?php 

							$total_reviews   = SIP_RSWC_DB_Helper::get_total_reviews( $product_id );
							$comments_first  = SIP_RSWC_DB_Helper::get_initial_reviews( $product_id, $limit );

							echo wp_kses_post( SIP_RSWC_Review_Renderer::render_reviews( $comments_first, $product_id, false, $limit, $total_reviews ) );

							?>
						</div>
					</div>

				</div>
			</div>

			<?php
			return ob_get_clean();
		}// end of post id is product or not
    }

    /**
     * AJAX handler for loading more reviews.
     *
     * @since 1.0.0
     */
    public static function ajax_load_more_reviews() {
		check_ajax_referer( 'sip_rswc_reviews_nonce', 'nonce' );

		$product_id = intval( $_POST['product_id'] ?? 0 );
		$offset = intval( $_POST['offset'] ?? 0 );
		$limit  = intval( $_POST['limit'] ?? 5 );

		$total_reviews	= SIP_RSWC_DB_Helper::get_total_reviews( $product_id );
		$comments		= SIP_RSWC_DB_Helper::get_paginated_reviews( $product_id, $limit, $offset );
		$html			= wp_kses_post( SIP_RSWC_Review_Renderer::render_reviews( $comments, $product_id, true ) );

		$remaining = $total_reviews - $limit - $offset;
		$remaining = ($remaining == $offset) ? 0 : $remaining;

		$btn_text = sprintf(
			esc_html( 
				/* translators: %d: number of remaining reviews to be loaded. */
				_n(
				'Load %d more review',
				'Load %d more reviews',
				$remaining,
				'sip-reviews-shortcode-woocommerce'
			) ),
			absint( $remaining )
		);

		$btn_text = wp_kses_post( apply_filters( 'sip_rswc_load_more_button_text', $btn_text, $remaining, $product_id ));

		wp_send_json_success( [
								'html'	=> $html,
								'btn'	=> ( $offset  == ( $total_reviews-1 ) ),
								'txt'	=> $btn_text,
								'count' => $remaining
							] );
    }

    /**
     * AJAX handler for filtering reviews by rating.
     *
     * @since 1.0.0
     */
    public static function ajax_filter_reviews_by_rating() {
		check_ajax_referer( 'sip_rswc_reviews_nonce', 'nonce' );

		$product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;
		$rating		= isset( $_POST['rating'] ) ? intval( $_POST['rating'] ) : 0;

		if ( ! $product_id || ! $rating ) {
		    wp_send_json_error( [ 'message' => 'Invalid request.' ] );
		}

		$comments	= SIP_RSWC_DB_Helper::get_reviews_by_rating( $product_id, $rating );
		$html		= wp_kses_post( SIP_RSWC_Review_Renderer::render_reviews( $comments, $product_id, true ) );

		wp_send_json_success( [ 'html' => $html, 'done' => count( $comments ) ] );
	}
}