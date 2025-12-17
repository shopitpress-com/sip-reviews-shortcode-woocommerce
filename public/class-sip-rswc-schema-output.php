<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Handles generation and output of Product JSON-LD schema.
 *
 * This class builds schema.org/Product structured data for the
 * current WooCommerce product or a product provided by other
 * plugin components (e.g., widgets or shortcodes).
 *
 * The schema can be filtered or disabled via:
 * - sip_rswc_enable_schema
 * - sip_rswc_product_schema_data
 *
 * @since 1.3.0
 */
class SIP_RSWC_Schema_Output {

    /**
     * Hook schema output into wp_head.
     *
     * @since 1.3.0
     * @return void
     */
    public static function init() {
        add_action( 'wp_footer', array( __CLASS__, 'output_schema' ), 200 );
    }

    /**
     * Build and print the Product JSON-LD schema (including offers, reviews, and rating).
     *
     * @since 1.3.0
     * @return void
     */
    public static function output_schema() {

        $product = function_exists( 'wc_get_product' ) && is_product()
            ? wc_get_product( get_the_ID() )
            : false;

        if ( ! $product ) {
            $custom_id = SIP_RSWC_Schema_Helper::get_product_id();
            if ( $custom_id ) {
                $product = wc_get_product( $custom_id );
            }
        }

        if ( ! $product ) {
            return;
        }

        $enabled = SIP_RSWC_Schema_Helper::get_enabled(); // get_option( 'sip_rswc_enable_schema', true );
        $enable_schema = wp_kses_post( apply_filters( 'sip_rswc_enable_schema', $enabled, $product ));
        if ( ! $enable_schema ) {
            return;
        }

        $id        = $product->get_id();
        $currency  = get_woocommerce_currency();
        $price     = $product->get_price();
        $sku       = $product->get_sku();
        $title     = get_the_title( $id );
        $permalink = get_permalink( $id );
        $image     = wp_get_attachment_image_src( get_post_thumbnail_id( $id ), 'full' );
        $image_url = isset( $image[0] ) ? esc_url( $image[0] ) : '';

        $average_rating = $product->get_average_rating();
        $review_count   = $product->get_review_count();

        // 🔹 Base schema
        $schema = [
            '@context'    => 'https://schema.org/',
            '@type'       => 'Product',
            'name'        => $title,
            'image'       => $image_url,
            'description' => wp_strip_all_tags( $product->get_description() ),
            'sku'         => $sku,
            'url'         => $permalink,
            'offers'      => [
                '@type'           => 'Offer',
                'priceCurrency'   => $currency,
                'price'           => $price,
                'availability'    => 'https://schema.org/InStock',
                'url'             => $permalink,
                'priceValidUntil' => apply_filters(
                    'sip_rswc_price_valid_until',
                    $product->get_date_on_sale_to()
                        ? $product->get_date_on_sale_to()->date( 'Y-m-d' )
                        : gmdate( 'Y-m-d', strtotime( '+1 year' ) ),
                    $product
                ),


                // ✅ NEW: shippingDetails (recommended)
                'shippingDetails' => [
                    '@type'              => 'OfferShippingDetails',
                    'shippingRate'       => [
                        '@type'    => 'MonetaryAmount',
                        'value'    => 0,
                        'currency' => $currency,
                    ],
                    'shippingDestination' => [
                        '@type'          => 'DefinedRegion',
                        'addressCountry' => get_option( 'woocommerce_default_country', 'US' ),
                    ],
                    'deliveryTime' => [
                        '@type'        => 'ShippingDeliveryTime',
                        'handlingTime' => [
                            '@type'  => 'QuantitativeValue',
                            'minValue' => 1,
                            'maxValue' => 2,
                            'unitCode' => 'DAY',
                        ],
                        'transitTime' => [
                            '@type'  => 'QuantitativeValue',
                            'minValue' => 3,
                            'maxValue' => 7,
                            'unitCode' => 'DAY',
                        ],
                    ],
                ],

                // ✅ NEW: hasMerchantReturnPolicy (recommended)
                'hasMerchantReturnPolicy' => [
                    '@type'                  => 'MerchantReturnPolicy',
                    'applicableCountry'      => get_option( 'woocommerce_default_country', 'US' ),
                    'returnPolicyCategory'   => 'https://schema.org/MerchantReturnFiniteReturnWindow',
                    'merchantReturnDays'     => 30,
                    'returnMethod'           => 'https://schema.org/ReturnByMail',
                    'returnFees'             => 'https://schema.org/FreeReturn',
                ],
            ],
        ];

        // 🔹 Aggregate Rating
        if ( $review_count > 0 ) {
            $schema['aggregateRating'] = [
                '@type'       => 'AggregateRating',
                'ratingValue' => (float) $average_rating,
                'reviewCount' => (int) $review_count,
            ];
        }

        // 🔹 Reviews
        $comments = get_comments([
            'post_id' => $id,
            'status'  => 'approve',
            'number'  => 20,
        ]);

        if ( ! empty( $comments ) ) {
            $review_schemas = [];

            foreach ( $comments as $comment ) {
                $rating = intval( get_comment_meta( $comment->comment_ID, 'rating', true ) );
                if ( ! $rating ) continue;

                $review_schemas[] = [
                    '@type'         => 'Review',
                    'author'        => [
                        '@type' => 'Person',
                        'name'  => esc_html( $comment->comment_author ),
                    ],
                    'datePublished' => get_comment_date( 'c', $comment ),
                    'reviewBody'    => wp_strip_all_tags( $comment->comment_content ),
                    'reviewRating'  => [
                        '@type'       => 'Rating',
                        'ratingValue' => $rating,
                        'bestRating'  => '5',
                        'worstRating' => '1',
                    ],
                ];
            }

            if ( $review_schemas ) {
                $schema['review'] = $review_schemas;
            }
        }

        // 🔹 Filters for flexibility
        $schema = apply_filters( 'sip_rswc_product_schema_data', $schema, $product );


        echo '<!-- This site is using the SIP Reviews Shortcode for WooCommerce plugin - https://wordpress.org/plugins/sip-reviews-shortcode-woocommerce/ -->
        <script type="application/ld+json">'
            . wp_json_encode( $schema ) .
        '</script>
        <!-- / SIP Reviews Shortcode for WooCommerce plugin. -->'. "\n";



    }
}
SIP_RSWC_Schema_Output::init();