<?php
/**
 * WooCommerce Price Robot - Last Sale
 *
 * @version 1.0.0
 * @since   1.0.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_Price_Robot_Last_Sale' ) ) :

class Alg_WC_Price_Robot_Last_Sale extends Alg_WC_Price_Robot_Abstract {

	/**
	 * Constructor
	 */
	public function __construct() {

		$this->id          = 'last_sale';
		$this->title       = __( 'Last Sale Robot', 'alg-woocommerce-price-robot' );
		$this->desc        = __( 'Calculates product price based on last price and time since last sale.', 'alg-woocommerce-price-robot' );
		$this->priority    = 10;

		parent::__construct();
	}

	/**
	 * get_price
	 */
	function get_price( $price, $product_id, $original_price ) {

		$the_product = wc_get_product( $product_id );
		$min_stock = get_option( 'alg_price_robot_last_sale_min_stock' );
		if ( 0 != $min_stock && $the_product->get_total_stock() < $min_stock ) return $price;

		// Last sale data
		$last_sale       = get_post_meta( $product_id, '_last_sale',       true );
		$price_last_sale = get_post_meta( $product_id, '_price_last_sale', true );
		$no_sales = ( '' == $last_sale ) ? true : false;
		if ( $no_sales ) {
			$last_sale       = get_post_time( 'U', false, $product_id ); // product creation time
			$price_last_sale = $price;
		}
		$price_last_sale = apply_filters( 'alg_price_robot_last_sale_price_last_sale', $price_last_sale, $last_sale, $no_sales );

		// Discount koef
		$discount_step_percent     = get_option( 'alg_price_robot_last_sale_discount_step_percent', 5 );
		$min_no_sales_period_days  = get_option( 'alg_price_robot_last_sale_discount_timeframe_days', 30 );
		$min_no_sales_period       = $min_no_sales_period_days * 24 * 60 * 60;
		$time_no_sales             = ( current_time( 'timestamp' ) - $last_sale );
//		if ( $time_no_sales > $max_period ) $time_no_sales = $max_period;
		if ( $time_no_sales / $min_no_sales_period >= 1 || $no_sales ) {
			$discount_percent = floor( $time_no_sales / $min_no_sales_period ) * $discount_step_percent;
			$discount_koef    = ( 100 - $discount_percent ) / 100;
		} else {
			$goal_sale_rate = get_option( 'alg_price_robot_last_sale_goal_sale_rate' );
			if ( $goal_sale_rate > 0 ) {
				$real_sale_rate    = get_post_meta( $product_id, '_timeframe_sales', true );
				$discount_koef_min = ( 100 - $discount_step_percent ) / 100;
				$discount_koef_max = ( 100 + $discount_step_percent ) / 100;
				$discount_koef     = $real_sale_rate / $goal_sale_rate;
				if ( $discount_koef < $discount_koef_min ) $discount_koef = $discount_koef_min;
				if ( $discount_koef > $discount_koef_max ) $discount_koef = $discount_koef_max;
//				$price_last_sale = $price;
			} else {
				$discount_koef = 1;
			}
		}

		// Price
//		$modified_price = round( $price_last_sale * $discount_koef, get_option( 'woocommerce_price_num_decimals', 2 ) );
//		$modified_price = ceil( $price_last_sale * $discount_koef );
		$modified_price = $price_last_sale * $discount_koef;

		$max_discount_percent = get_option( 'alg_price_robot_last_sale_discount_max_percent', 25 );
		$min_price = $original_price * ( ( 100 - $max_discount_percent ) / 100 );
		if ( $modified_price < $min_price ) $modified_price = $min_price;

//		return $modified_price;
		return round( $modified_price, get_option( 'woocommerce_price_num_decimals', 2 ) );
	}

	/**
	 * get_settings
	 */
	function get_settings() {

		$settings = array(

			array(
				'title'     => __( 'Discount Options', 'alg-woocommerce-price-robot' ),
				'type'      => 'title',
				'id'        => 'alg_price_robot_last_sale_discount_options',
			),

			array(
				'title'     => __( 'Timeframe in Days', 'alg-woocommerce-price-robot' ),
//				'desc'      => __( 'Number of days for a single product you expect to sell at least one item', 'alg-woocommerce-price-robot' ),
				'desc_tip'  => __( 'You can enter a fraction of a day here, however we do recommend setting one full day at least.', 'alg-woocommerce-price-robot' ),
				'id'        => 'alg_price_robot_last_sale_discount_timeframe_days',
				'default'   => 30,
				'type'      => 'number',
				'custom_attributes' => array(
					'step' => '0.0001',
					'min'  => '0.0001',
				),
			),

			array(
				'title'     => __( 'Discount Step in Percent', 'alg-woocommerce-price-robot' ),
				'id'        => 'alg_price_robot_last_sale_discount_step_percent',
				'default'   => 5,
				'type'      => 'number',
				'custom_attributes' => array(
					'step' => '0.0001',
					'min'  => '0.0001',
					'max'  => '100',
				),
			),

			array(
				'title'     => __( 'Maximum Discount in Percent', 'alg-woocommerce-price-robot' ),
				'desc_tip'  => __( 'Maximum discount from original price in percent', 'alg-woocommerce-price-robot' ),
				'id'        => 'alg_price_robot_last_sale_discount_max_percent',
				'default'   => 25,
				'type'      => 'number',
				'custom_attributes' => array(
					'step' => '0.0001',
					'min'  => '0.0001',
					'max'  => '100',
				),
			),

			array(
				'title'     => __( 'Min Stock', 'alg-woocommerce-price-robot' ),
				'id'        => 'alg_price_robot_last_sale_min_stock',
				'default'   => 2,
				'type'      => 'number',
				'custom_attributes' => array(
					'step' => '1',
					'min'  => '0',
				),
			),

			array(
				'title'     => __( 'Goal Sale Rate', 'alg-woocommerce-price-robot' ),
				'desc'      => get_option( 'timeframe_sales_average' ),
				'id'        => 'alg_price_robot_last_sale_goal_sale_rate',
				'default'   => 0,
				'type'      => 'number',
				'custom_attributes' => array(
					'step' => '1',
					'min'  => '0',
				),
			),

			array(
				'type'      => 'sectionend',
				'id'        => 'alg_price_robot_last_sale_discount_options',
			),

		);

		return array_merge( parent::get_settings(), $settings );
	}

}

endif;

return new Alg_WC_Price_Robot_Last_Sale();
