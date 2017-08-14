<?php
/**
 * WooCommerce Price Robot - Pretty Price Robot
 *
 * @version 1.0.0
 * @since   1.0.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_Price_Robot_Pretty_Price' ) ) :

class Alg_WC_Price_Robot_Pretty_Price extends Alg_WC_Price_Robot_Abstract {

	/**
	 * Constructor.
	 */
	public function __construct() {

		$this->id       = 'pretty_price';
		$this->title    = __( 'Pretty Price Robot', 'alg-woocommerce-price-robot' );
		$this->desc     = __( 'Make prices pretty :) That is adds 99 cents to the price.', 'alg-woocommerce-price-robot' );
		$this->priority = 100;

		parent::__construct();
	}

	/**
	 * get_price.
	 */
	function get_price( $price, $product_id, $original_price ) {
		$price = ceil( $price );
		/* if ( $price < 99 ) */$price = $price - 0.01;
		return $price;
	}

	/**
	 * get_settings.
	 */
	function get_settings() {
		$settings = array();
		return array_merge( parent::get_settings(), $settings );
	}

}

endif;

return new Alg_WC_Price_Robot_Pretty_Price();
