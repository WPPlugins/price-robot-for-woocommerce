<?php
/**
 * WooCommerce Price Robot - Settings
 *
 * @version 1.0.0
 * @since   1.0.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WC_Settings_Price_Robot' ) ) :

class WC_Settings_Price_Robot extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	function __construct() {

		$this->id    = 'alg_price_robot';
		$this->label = __( 'Price Robot', 'alg-woocommerce-price-robot' );

		parent::__construct();
	}

	/**
	 * get_settings.
	 */
	public function get_settings() {
		global $current_section;
		if ( '' == $current_section ) $current_section = 'general';
		return apply_filters( 'woocommerce_get_settings_' . $this->id . '_' . $current_section, array() );
	}

}

endif;

return new WC_Settings_Price_Robot();
