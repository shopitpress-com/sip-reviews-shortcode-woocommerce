<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }

class SIP_RSWC_Review_Renderer {

	/**
	 * Render a list of review comments as HTML.
	 *
	 * @param array $comments   List of WP_Comment objects.
	 * @param int   $product_id Product ID.
	 * @param bool  $items_only If true, output only <li> elements (for AJAX append).
	 * @param int   $limit      Number of reviews per load (used for pagination button).
	 * @param int   $total      Total number of approved top-level reviews.
	 * @return string HTML output.
	 */
	public static function render_reviews( $comments, $product_id = 0, $items_only = false, $limit = 5, $total = 0 ) {
		ob_start();

		if ( $comments ) {
			if ( ! $items_only ) {
				echo '<ul class="commentbox commentlist commentlist-' . esc_attr( $product_id ) . '">';
			}

			foreach ( $comments as $comment ) {
				$comment_id    = (int) $comment->comment_ID;
				$rating_val    = intval( get_comment_meta( $comment_id, 'rating', true ) );
				$author        = esc_html( $comment->comment_author );
				$date_readable = esc_html( get_comment_date( wc_date_format(), $comment_id ) );
				$comment_text  = nl2br( esc_html( $comment->comment_content ) );

				// Filters for customization.
				$author        = wp_kses_post( apply_filters( 'sip_rswc_review_author_name', $author, $comment ));
				$date_readable = wp_kses_post( apply_filters( 'sip_rswc_review_date', $date_readable, $comment ));
				$comment_text  = wp_kses_post( apply_filters( 'sip_rswc_review_text', $comment_text, $comment ));

				ob_start();
				for ( $i = 1; $i <= 5; $i++ ) {
					$classes = 'sip-rswc-star' . ( $i <= $rating_val ? ' sip-rswc-star-selected' : '' );
					echo '<span class="' . esc_attr( $classes ) . '"></span>';
				}
				$rating_html = wp_kses_post( apply_filters( 'sip_rswc_review_rating_html', ob_get_clean(), $rating_val, $comment ));

				$item  = '<li class="sip-rswc-review-item" id="review-' . esc_attr( $comment_id ) . '">';
				$item .= '<div class="comment-borderbox">';
				$item .= '<div class="sip-rswc-rating-widget">' . $rating_html . '</div>';
				$item .= '<p class="author"><strong>' . $author . '</strong> – <time>' . $date_readable . '</time></p>';
				$item .= '<div><p>' . $comment_text . '</p></div>';
				$item .= '</div></li>';

				echo wp_kses_post( apply_filters( 'sip_rswc_review_item_html', $item, $comment ));
			}

			if ( ! $items_only ) {
				echo '</ul>';

				// Add Load More button if there are more reviews left
				if ( $total > $limit ) {
					$remaining = $total - $limit;
					$btn_text = sprintf(
						/* translators: %d: remaining reviews */
						esc_html( _n(
							'Load %d more review',
							'Load %d more reviews',
							$remaining,
							'sip-reviews-shortcode-woocommerce'
						) ),
						absint( $remaining )
					);

					$btn_text = wp_kses_post( apply_filters( 'sip_rswc_load_more_button_text', $btn_text, $remaining, $product_id ));

					echo '<div class="sip-rswc-load-more-wrap">';
					echo '<button class="sip-rswc-load-more-btn" data-product-id="' . esc_attr( $product_id ) . '" data-offset="' . esc_attr( $limit ) . '" data-limit="' . esc_attr( $limit ) . '">';
					echo esc_html( $btn_text );
					echo '</button></div>';
				}
			}
		} else {
			$message = __( 'No reviews found for this rating.', 'sip-reviews-shortcode-woocommerce' );

			if ( $items_only ) {
				echo '<li><p>' . esc_html( $message ) . '</p></li>';
			} else {
				echo '<ul class="commentlist"><li><p>' . esc_html( $message ) . '</p></li></ul>';
			}
		}

		return ob_get_clean();
	}
}