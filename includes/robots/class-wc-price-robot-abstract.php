<?php
/**
 * WooCommerce Price Robot - Abstract
 *
 * @version 1.0.0
 * @since   1.0.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_Price_Robot_Abstract' ) ) :

class Alg_WC_Price_Robot_Abstract {

	public $id;
	public $title;
	public $desc;
	public $short_desc;
	public $priority;

	/**
	 * Constructor.
	 */
	public function __construct() {

		add_filter( 'woocommerce_get_sections_alg_price_robot',              array( $this, 'settings_section' ), $this->priority );
		add_filter( 'woocommerce_get_settings_alg_price_robot_' . $this->id, array( $this, 'get_settings' ), $this->priority );

		if ( 'yes' === get_option( 'alg_price_robot_' . $this->id . '_enabled' ) ) {
			add_filter( 'alg_woocommerce_price_robot', array( $this, 'get_price' ), $this->priority, 3 );
		}
	}

	/**
	 * get_price.
	 */
	function get_price( $price, $product_id, $original_price ) {
		return $price;
	}

	/**
	 * settings_section.
	 */
	function settings_section( $sections ) {
		$sections[ $this->id ] = $this->title;
		return $sections;
	}

	/**
	 * get_settings.
	 */
	function get_settings() {

		$settings = array(

			array(
				'title'     => $this->title . ' ' . __( 'Options', 'alg-woocommerce-price-robot' ),
				'desc'      => $this->desc,
				'type'      => 'title',
				'id'        => 'alg_price_robot_' . $this->id . '_enable_options',
			),

			array(
				'title'     => $this->title,
				'desc'      => '<strong>' . __( 'Enable', 'alg-woocommerce-price-robot' ) . '</strong>',
				'desc_tip'  => '',//$this->short_desc,
				'id'        => 'alg_price_robot_' . $this->id . '_enabled',
				'default'   => 'yes',
				'type'      => 'checkbox',
			),

			array(
				'type'      => 'sectionend',
				'id'        => 'alg_price_robot_' . $this->id . '_enable_options',
			),

		);

		return $settings;
	}

}

endif;
