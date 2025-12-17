<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Database helper for SIP Reviews Shortcode for WooCommerce.
 *
 * Handles all direct database queries related to product reviews.
 *
 * @package SIP_Reviews_Shortcode_WooCommerce
 * @since 1.3.0
 */

/**
 * Class SIP_RSWC_DB_Helper
 *
 * Provides static methods for review-related database operations.
 */
class SIP_RSWC_DB_Helper {

	/**
	 * Get total approved top-level reviews for a given product.
	 *
	 * @param int $product_id Product ID.
	 * @return int Total number of approved reviews.
	 */
	public static function get_total_reviews( $product_id ) {
		global $wpdb;

		$cache_key = 'sip_rswc_review_count_' . absint( $product_id );
		$cache_grp = 'sip_rswc';

		$cached = wp_cache_get( $cache_key, $cache_grp );
		if ( false !== $cached ) {
			return (int) $cached;
		}

		/**
		 * We must use a direct query here for performance.
		 * Cached aggressively to comply with WP coding standards.
		 */
		/* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching */
		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->comments} c
				 INNER JOIN {$wpdb->posts} p ON p.ID = c.comment_post_ID
				 WHERE p.ID = %d
					AND c.comment_approved > 0
					AND p.post_type = 'product'
					AND p.post_status = 'publish'
					AND c.comment_parent = 0",
				$product_id
			)
		);

		wp_cache_set( $cache_key, $count, $cache_grp, 6 * HOUR_IN_SECONDS );

		return $count;
	}

	/**
	 * Get the initial batch of product reviews.
	 *
	 * @param int $product_id Product ID.
	 * @param int $limit Number of reviews to fetch.
	 * @return array List of comment objects.
	 */
	public static function get_initial_reviews( $product_id, $limit = 5 ) {
		global $wpdb;

		$cache_group = 'sip_rswc_reviews';
		$cache_key   = sprintf( 'initial_%d_%d', absint( $product_id ), absint( $limit ) );

		$cached = wp_cache_get( $cache_key, $cache_group );
		if ( false !== $cached ) {
			return $cached;
		}

		/* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching */
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT c.*
				 FROM {$wpdb->comments} c
				 INNER JOIN {$wpdb->posts} p ON p.ID = c.comment_post_ID
				 WHERE p.ID = %d
				   AND c.comment_approved > 0
				   AND p.post_type = 'product'
				   AND p.post_status = 'publish'
				   AND c.comment_parent = 0
				 ORDER BY c.comment_date DESC
				 LIMIT %d",
				$product_id,
				$limit
			)
		);

		wp_cache_set( $cache_key, $results, $cache_group, 10 * MINUTE_IN_SECONDS );

		return $results;
	}

	/**
	 * Get paginated reviews for a product.
	 *
	 * @param int $product_id Product ID.
	 * @param int $limit      Number of reviews per page.
	 * @param int $offset     Starting offset for pagination.
	 * @return array List of comment objects.
	 */
	public static function get_paginated_reviews( $product_id, $limit = 5, $offset = 0 ) {
		global $wpdb;

		$cache_group = 'sip_rswc_reviews';
		$cache_key   = sprintf(
			'page_%d_%d_%d',
			absint( $product_id ),
			absint( $limit ),
			absint( $offset )
		);

		$cached = wp_cache_get( $cache_key, $cache_group );
		if ( false !== $cached ) {
			return $cached;
		}

		/* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching */
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT c.*
				 FROM {$wpdb->comments} c
				 INNER JOIN {$wpdb->posts} p ON p.ID = c.comment_post_ID
				 WHERE p.ID = %d
				   AND c.comment_approved > 0
				   AND p.post_type = 'product'
				   AND p.post_status = 'publish'
				   AND c.comment_parent = 0
				 ORDER BY c.comment_date DESC
				 LIMIT %d OFFSET %d",
				$product_id,
				$limit,
				$offset
			)
		);

		wp_cache_set( $cache_key, $results, $cache_group, 10 * MINUTE_IN_SECONDS );

		return $results;
	}

	/**
	 * Get reviews filtered by star rating.
	 *
	 * @param int $product_id Product ID.
	 * @param int $rating     Star rating (1–5).
	 * @return array List of comment objects.
	 */
	public static function get_reviews_by_rating( $product_id, $rating ) {
		global $wpdb;

		$cache_group = 'sip_rswc_reviews';
		$cache_key   = sprintf(
			'rating_%d_%d',
			absint( $product_id ),
			absint( $rating )
		);

		$cached = wp_cache_get( $cache_key, $cache_group );
		if ( false !== $cached ) {
			return $cached;
		}

		/* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching */
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT c.*
				 FROM {$wpdb->comments} c
				 INNER JOIN {$wpdb->commentmeta} m ON c.comment_ID = m.comment_id
				 WHERE c.comment_post_ID = %d
					AND c.comment_approved = 1
					AND m.meta_key = 'rating'
					AND m.meta_value = %d
				 ORDER BY c.comment_date DESC",
				$product_id,
				$rating
			)
		);

		wp_cache_set( $cache_key, $results, $cache_group, 10 * MINUTE_IN_SECONDS );

		return $results;
	}

	/**
     * Get total review count for a WooCommerce product.
     *
     * Retrieves the number of approved product reviews stored in `_wc_review_count`.
     *
     * @since 1.0.0
     *
     * @param int $product_id The product ID.
     * @return int Total number of reviews. Returns 0 if none found.
     */
    public static function sip_get_review_count( $product_id = 0 ) {
        return (int) get_post_meta( $product_id, '_wc_review_count', true );
    }

    /**
     * Get average rating for a WooCommerce product.
     *
     * Retrieves the product's average rating value stored in `_wc_average_rating`.
     *
     * @since 1.0.0
     *
     * @param int $product_id The product ID.
     * @return float Average rating (e.g., 4.5). Returns 0 if not rated yet.
     */
    public static function sip_get_avg_rating( $product_id = 0 ) {
        return (float) get_post_meta( $product_id, '_wc_average_rating', true );
    }

    /**
     * Get per-star rating breakdown for a WooCommerce product.
     *
     * Retrieves the rating distribution array stored in `_wc_rating_count`, where
     * keys are star values (1–5) and values are the number of reviews with that rating.
     *
     * Example:
     * [
     *     5 => 12,
     *     4 => 3,
     *     3 => 0,
     *     2 => 1,
     *     1 => 0
     * ]
     *
     * @since 1.0.0
     *
     * @param int $product_id The product ID.
     * @return array Associative array of star rating counts.
     */
    public static function sip_get_rating_count( $product_id = 0 ) {
        $counts = get_post_meta( $product_id, '_wc_rating_count', true );
        return is_array( $counts ) ? $counts : [];
    }

    /**
     * Get product price.
     *
     * Retrieves the current product price stored in `_price`.
     *
     * @since 1.0.0
     *
     * @param int $product_id The product ID.
     * @return string|float Product price. Returns an empty string if not set.
     */
    public static function sip_get_price( $product_id = 0 ) {
        return get_post_meta( $product_id, '_price', true );
    }
}