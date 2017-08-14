<?php
/**
 * WooCommerce Price Robot
 *
 * @version 1.0.0
 * @since   1.0.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_Price_Robot' ) ) :

class Alg_WC_Price_Robot {

	/**
	 * Constructor
	 */
	public function __construct() {

		if ( 'yes' === get_option( 'alg_price_robot_general_auto_pricing_enabled' ) ) {

//			add_filter( 'woocommerce_get_sale_price',               'alg_price_robot',    PHP_INT_MAX, 2 );
			add_filter( 'woocommerce_get_price',                    'alg_price_robot',    PHP_INT_MAX, 2 );
//			add_filter( 'woocommerce_get_variation_sale_price',     'alg_price_robot',    PHP_INT_MAX, 2 );
//			add_filter( 'woocommerce_get_variation_price',          'alg_price_robot',    PHP_INT_MAX, 2 );

			/* if ( 'no' === get_option( 'alg_price_robot_general_variable_as_single', 'no' ) ) {
				add_filter( 'woocommerce_variation_prices', array( $this, 'change_price_variations' ), PHP_INT_MAX, 2 );
			} */

			if ( 'yes' === get_option( 'alg_price_robot_general_display_as_sale' ) ) {
				add_filter( 'woocommerce_get_sale_price', 'alg_price_robot', PHP_INT_MAX, 2 );
				add_filter( 'woocommerce_product_is_on_sale', array( $this, 'price_robot_on_sale' ), PHP_INT_MAX, 2 );
			}
		}
	}

	/**
	 * change_price_variations
	 *
	public function change_price_variations( $prices_array, $product ) {
		$modified_prices_array = $prices_array;
		foreach ( $prices_array as $price_type => $prices ) {
			if ( 'regular_price' === $price_type ) continue;
			foreach ( $prices as $variation_id => $price ) {
				$modified_prices_array[ $price_type ][ $variation_id ] = alg_price_robot( $price, wc_get_product( $variation_id ) );
			}
		}
		return $modified_prices_array;
	}

	/**
	 * price_robot
	 */
	function price_robot_on_sale( $is_on_sale, $_product ) {
		if ( 'yes' === get_post_meta( $_product->id, '_price_robot_enabled', true ) ) {

			remove_filter( 'woocommerce_get_price', 'alg_price_robot', PHP_INT_MAX, 2 );
			$the_original_price = $_product->get_price();
			add_filter(    'woocommerce_get_price', 'alg_price_robot', PHP_INT_MAX, 2 );

			$robot_price = alg_price_robot( $the_original_price, $_product, true );

			return ( $robot_price < $the_original_price ) ? true : $is_on_sale;
		}
		return $is_on_sale;
	}
}

endif;

return new Alg_WC_Price_Robot();
