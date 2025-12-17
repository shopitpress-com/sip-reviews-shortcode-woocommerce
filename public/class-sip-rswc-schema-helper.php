<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Helper class for managing Product Schema data across the plugin.
 *
 * This class provides static methods to set and retrieve the current product ID
 * used for generating Product JSON-LD (schema.org/Product) structured data.
 * 
 * It ensures that widgets, shortcodes, and other plugin components can share
 * a single product reference without relying on global variables. This approach
 * improves code organization, avoids naming conflicts, and provides a clean
 * interface for schema-related operations.
 *
 * Example usage:
 * 
 *     // Set the product ID from a shortcode or widget
 *     SIP_RSWC_Schema_Helper::set_product_id( $product_id );
 * 
 *     // Later, retrieve it inside the schema output function
 *     $product_id = SIP_RSWC_Schema_Helper::get_product_id();
 *
 * @since 1.3.0
 */
class SIP_RSWC_Schema_Helper {
    
    /**
     * Holds the current product ID to be used for schema generation.
     *
     * @since 1.3.0
     * @var int|null
     */
    private static $product_id = null;

    /**
     * Stores the boolean value that determines whether the schema feature is enabled.
     *
     * @since 1.3.0
     * @var bool
     */
    private static $enabled = false;

    /**
     * Set the product ID (typically from widget or shortcode context).
     *
     * @since 1.3.0
     * @param int $product_id WooCommerce product ID.
     * @return void
     */
    public static function set_product_id( $product_id ) {
        self::$product_id = (int) $product_id;
    }

    /**
     * Retrieve the product ID currently stored for schema output.
     *
     * @since 1.3.0
     * @return int|null Returns the product ID if set, otherwise null.
     */
    public static function get_product_id() {
        return self::$product_id;
    }

    /**
     * Set whether schema output is enabled.
     *
     * @since 1.3.0
     * @param bool $enabled True to enable schema, false to disable it.
     * @return void
     */
    public static function set_enabled( $enabled ) {
        self::$enabled = (bool) $enabled;
    }

    /**
     * Retrieve the current schema enabled state.
     *
     * @since 1.3.0
     * @return bool Returns true if schema is enabled, false otherwise.
     */
    public static function get_enabled() {
        return self::$enabled;
    }

}
