<?php
/**
 * WooCommerce Price Robot - Products General Settings
 *
 * @version 1.0.0
 * @since   1.0.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_Price_Robot_Settings_General' ) ) :

class Alg_WC_Price_Robot_Settings_General {

	/**
	 * Constructor.
	 */
	public function __construct() {

		$this->id   = 'general';
		$this->desc = __( 'General', 'alg-woocommerce-price-robot' );

		add_filter( 'woocommerce_get_sections_alg_price_robot',              array( $this, 'settings_section' ), 1 );
		add_filter( 'woocommerce_get_settings_alg_price_robot_' . $this->id, array( $this, 'get_settings' ), 1 );
	}

	/**
	 * settings_section.
	 */
	function settings_section( $sections ) {
		$sections[ $this->id ] = $this->desc;
		return $sections;
	}

	/**
	 * get_settings.
	 */
	function get_settings() {

		$settings = array(

			array(
				'title'     => __( 'Price Robot General Options', 'alg-woocommerce-price-robot' ),
				'type'      => 'title',
				'id'        => 'alg_price_robot_general_options',
			),

			array(
				'title'     => __( 'Automatic Pricing', 'alg-woocommerce-price-robot' ),
				'desc'      => __( 'Enable', 'alg-woocommerce-price-robot' ),
//				'desc_tip'  => __( 'This will change products prices on frontend. If disabled, will work in Advisor mode.', 'alg-woocommerce-price-robot' ),
				'id'        => 'alg_price_robot_general_auto_pricing_enabled',
				'default'   => 'yes',
				'type'      => 'checkbox',
			),

			array(
				'title'     => __( 'Debug Column in Admin Products', 'alg-woocommerce-price-robot' ),
				'desc'      => __( 'Enable', 'alg-woocommerce-price-robot' ),
				'id'        => 'alg_price_robot_general_admin_products_debug_enabled',
				'default'   => 'no',
				'type'      => 'checkbox',
			),

			array(
				'title'     => __( 'Crons', 'alg-woocommerce-price-robot' ),
				'desc'      =>
					'<a href="' . add_query_arg( 'get_orders_manual', 'yes' ) . '">' . __( 'Update Data Manually', 'alg-woocommerce-price-robot' ) . '</a>' .
					' ' .
					date( 'Y-m-d H:i:s', get_option( 'get_orders_cron_started' ) ) .
					' - ' .
					date( 'Y-m-d H:i:s', get_option( 'get_orders_cron_finished' ) ),
				'id'        => 'alg_price_robot_general_admin_crons_update',
				'default'   => 'daily',
				'type'      => 'select',
				'options'   => alg_get_cron_update_intervals(),
			),

			array(
				'title'     => __( 'Display as Sale', 'alg-woocommerce-price-robot' ),
				'desc'      => __( 'Display as sale if robot price is lower than original price.', 'alg-woocommerce-price-robot' ),
				'id'        => 'alg_price_robot_general_display_as_sale',
				'default'   => 'yes',
				'type'      => 'checkbox',
			),

			array(
				'title'     => __( 'Variable Products', 'alg-woocommerce-price-robot' ),
				'desc'      => __( 'Treat variations as single product.', 'alg-woocommerce-price-robot' ),
				'desc_tip'  => __( 'Makes sense if you are going to have same (equal) prices for all variations of a variable product.', 'alg-woocommerce-price-robot' )
				       . ' ' . __( 'You may need to clear transients after changing this option.', 'alg-woocommerce-price-robot' ),
				'id'        => 'alg_price_robot_general_variable_as_single',
				'default'   => 'no',
				'type'      => 'checkbox',
			),

			array(
				'type'      => 'sectionend',
				'id'        => 'alg_price_robot_general_options',
			),

		);

		return $settings;
	}

}

endif;

return new Alg_WC_Price_Robot_Settings_General();
