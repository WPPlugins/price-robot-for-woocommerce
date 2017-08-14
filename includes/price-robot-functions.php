<?php
/**
 * WooCommerce Price Robot - Functions
 *
 * @version 1.0.0
 * @since   1.0.0
 * @author  Algoritmika Ltd.
 */

if ( ! function_exists( 'alg_get_cron_update_intervals' ) ) {
	/**
	 * alg_get_cron_update_intervals.
	 */
	function alg_get_cron_update_intervals() {
		return array(
			'minutely'   => __( 'Update Every Minute', 'alg-woocommerce-price-robot' ),
			'hourly'     => __( 'Update Hourly', 'alg-woocommerce-price-robot' ),
			'twicedaily' => __( 'Update Twice Daily', 'alg-woocommerce-price-robot' ),
			'daily'      => __( 'Update Daily', 'alg-woocommerce-price-robot' ),
			'weekly'     => __( 'Update Weekly', 'alg-woocommerce-price-robot' ),
		);
	}
}

if ( ! function_exists( 'alg_is_frontend' ) ) {
	/**
	 * alg_is_frontend.
	 */
	function alg_is_frontend() {
		return ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) ? true : false;
	}
}

if ( ! function_exists( 'alg_price_robot' ) ) {
	/**
	 * alg_price_robot.
	 */
	function alg_price_robot( $price, $_product, $advisor_mode = false ) {

//		if ( ! alg_is_frontend() ) return $price;

//		if ( $_product->is_type( 'variable' ) && ! isset( $_product->variation_id ) ) return $price;

		/* if ( is_numeric( $_product ) ) {
			$the_product_id = $_product;
			$the_parent_product_id = $_product;
		} else { */
			if ( 'no' === get_option( 'alg_price_robot_general_variable_as_single', 'no' ) ) {
				$the_product_id = ( isset( $_product->variation_id ) ) ? $_product->variation_id : $_product->id;
			} else {
				$the_product_id = $_product->id;
			}
			$the_parent_product_id = $_product->id;
		/* } */

		if ( $advisor_mode || 'yes' === get_post_meta( $the_parent_product_id, '_price_robot_enabled', true ) ) {
			$original_price = $price;
			$modified_price = apply_filters( 'alg_woocommerce_price_robot', $price, $the_product_id, $original_price );
			$price = ( $modified_price > 0 ) ? round( $modified_price, get_option( 'woocommerce_price_num_decimals', 2 ) ) : 0;
			$price = ( abs( $price - $original_price ) < 1 ) ? $original_price : $price;
		}

		return $price;
	}
}
