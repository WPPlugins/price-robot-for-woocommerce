<?php
/*
Plugin Name: Price Robot for WooCommerce
Plugin URI: http://coder.fm/items/woocommerce-price-robot-plugin
Description: The plugin calculates optimal price for products in WooCommerce.
Version: 1.0.0
Author: Algoritmika Ltd
Author URI: http://www.algoritmika.com
Copyright: © 2015 Algoritmika Ltd.
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Check if Pro is active, if so then return
if ( in_array( 'price-robot-for-woocommerce-pro/woocommerce-price-robot-pro.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) return;

// Check if WooCommerce is active
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) return;

if ( ! class_exists( 'Alg_Woocommerce_Price_Robot' ) ) :

/**
 * Main Alg_Woocommerce_Price_Robot Class
 *
 * @class Alg_Woocommerce_Price_Robot
 */

final class Alg_Woocommerce_Price_Robot {

	/**
	 * @var Alg_Woocommerce_Price_Robot The single instance of the class
	 */
	protected static $_instance = null;

	/**
	 * Main Alg_Woocommerce_Price_Robot Instance
	 *
	 * Ensures only one instance of Alg_Woocommerce_Price_Robot is loaded or can be loaded.
	 *
	 * @static
	 * @return Alg_Woocommerce_Price_Robot - Main instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	}

	/**
	 * Alg_Woocommerce_Price_Robot Constructor.
	 * @access public
	 */
	public function __construct() {

		// Include required files
		$this->includes();

		add_action( 'init', array( $this, 'init' ), 0 );

		register_activation_hook( __FILE__, array( $this, 'product_pageviews_table_install' ) );

		// Settings
		if ( is_admin() ) {
			add_filter( 'woocommerce_get_settings_pages', array( $this, 'add_woocommerce_settings_tab' ) );
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'action_links' ) );
		}
	}

	/**
	 * Show action links on the plugin screen
	 *
	 * @param mixed $links
	 * @return array
	 */
	public function action_links( $links ) {
		return array_merge(
			array( '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=alg_price_robot' ) . '">' . __( 'Settings', 'woocommerce' ) . '</a>', ),
			$links
		);
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 */
	private function includes() {

		require_once( 'includes/price-robot-functions.php' );
		require_once( 'includes/robots/class-wc-price-robot-abstract.php' );

		$settings = array();

		// Settings
		$settings[] = require_once( 'includes/admin/class-wc-price-robot-settings-general.php' );

		// Robots
		$settings[] = require_once( 'includes/robots/class-wc-price-robot-last-sale.php' );
		$settings[] = require_once( 'includes/robots/class-wc-price-robot-pretty-price.php' );

		if ( is_admin() ) {
			foreach ( $settings as $section ) {
				foreach ( $section->get_settings() as $value ) {
					if ( isset( $value['default'] ) && isset( $value['id'] ) ) {
						if ( isset ( $_GET['alg_woocommerce_price_robot_admin_options_reset'] ) ) {
							require_once( ABSPATH . 'wp-includes/pluggable.php' );
							if ( is_super_admin() ) {
								delete_option( $value['id'] );
							}
						}
						$autoload = isset( $value['autoload'] ) ? ( bool ) $value['autoload'] : true;
						add_option( $value['id'], $value['default'], '', ( $autoload ? 'yes' : 'no' ) );
					}
				}
			}
		}

		require_once( 'includes/admin/class-wc-price-robot-products.php' );
		require_once( 'includes/admin/class-wc-price-robot-crons.php' );
		require_once( 'includes/class-wc-price-robot.php' );
	}

	/**
	 * Add Woocommerce settings tab to WooCommerce settings.
	 */
	public function add_woocommerce_settings_tab( $settings ) {
		$settings[] = include( 'includes/admin/class-wc-settings-price-robot.php' );
		return $settings;
	}

	/**
	 * Init Alg_Woocommerce_Price_Robot when WordPress initialises.
	 */
	public function init() {
		// Set up localisation
		load_plugin_textdomain( 'alg-woocommerce-price-robot', false, dirname( plugin_basename( __FILE__ ) ) . '/langs/' );
	}

	/**
	 * Get the plugin url.
	 *
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugin_dir_url( __FILE__ ) );
	}

	/**
	 * Get the plugin path.
	 *
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}

	/**
	 * product_pageviews_table_install.
	 */
	function product_pageviews_table_install() {

		$pageviews_db_version = '1.0';

		global $wpdb;

		$table_name = $wpdb->prefix . 'alg_product_pageviews';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			post_id mediumint(9) NOT NULL,
			time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			ip text NOT NULL,
			UNIQUE KEY id (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

		update_option( 'alg_pageviews_db_version', $pageviews_db_version );
	}
}

endif;

/**
 * Returns the main instance of Alg_Woocommerce_Price_Robot to prevent the need to use globals.
 *
 * @return Alg_Woocommerce_Price_Robot
 */
if ( ! function_exists( 'alg_wc_price_robot' ) ) {
	function alg_wc_price_robot() {
		return Alg_Woocommerce_Price_Robot::instance();
	}
}

alg_wc_price_robot();
