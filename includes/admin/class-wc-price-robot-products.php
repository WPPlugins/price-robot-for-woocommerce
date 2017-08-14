<?php
/**
 * WooCommerce Price Robot - Products
 *
 * @version 1.0.0
 * @since   1.0.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_Price_Robot_Products' ) ) :

class Alg_WC_Price_Robot_Products {

	/**
	 * Constructor.
	 */
	public function __construct() {

		add_filter( 'manage_edit-product_columns',        array( $this, 'add_product_column_price_robot' ),    PHP_INT_MAX );
		add_action( 'manage_product_posts_custom_column', array( $this, 'render_product_column_price_robot' ), PHP_INT_MAX );

		add_action( 'restrict_manage_posts', array( $this, 'restrict_manage_posts' ) );
		add_filter( 'parse_query',           array( $this, 'products_by_price_robot_admin_filter_query' ) );

		add_action( 'init', array( $this, 'enable_price_robot_for_product' ), PHP_INT_MAX );

	}

	/**
	 * Filter the products in admin based on options
	 *
	 * @access public
	 * @param mixed $query
	 * @return void
	 */
	function products_by_price_robot_admin_filter_query( $query ) {
		global $typenow, $wp_query;
		if ( $typenow == 'product' && isset( $_GET['price_robot'] ) && 'all' != $_GET['price_robot'] ) {
			$query->query_vars['meta_value'] = $_GET['price_robot'];
			$query->query_vars['meta_key']   = '_price_robot_enabled';
		}
	}

	/**
	 * get_x
	 */
	function get_x() {
		$x = 0;
		$offset = 0;
		$block_size = 96;
		while( true ) {
			$args = array(
				'post_type'      => 'product',
				'posts_per_page' => $block_size,
				'offset'         => $offset,
			);
			$loop_products = new WP_Query( $args );
			if ( ! $loop_products->have_posts() ) break;
			while ( $loop_products->have_posts() ) {
				$loop_products->the_post();
				$y = get_post_meta( $loop_products->post->ID, '_price_robot_enabled', true );
				$x = ( 'yes' === $y ) ? ( $x + 1 ) : $x;
			}
			$offset += $block_size;
		}
		return $x;
	}

	/**
	 * enable_price_robot_for_product
	 */
	function enable_price_robot_for_product() {
		if ( ! is_super_admin() ) return;
		if ( isset( $_GET['enable_price_robot'] ) && 0 != $_GET['enable_price_robot'] ) {
			if ( $this->get_x() < 10 ) {
				update_post_meta( $_GET['enable_price_robot'], '_price_robot_enabled', 'yes' );
			} else {
				echo '<div id="message" class="error"><p><strong>' . __( 'Free version limitation.', 'alg-woocommerce-price-robot' ) . '</strong></p></div>';
			}
		}
		if ( isset( $_GET['disable_price_robot'] ) && 0 != $_GET['disable_price_robot'] ) {
			update_post_meta( $_GET['disable_price_robot'], '_price_robot_enabled', 'no' );
		}
	}

	/**
	 * Filters for post types
	 */
	public function restrict_manage_posts() {
		global $typenow, $wp_query;

		if ( 'product' === $typenow ) {

			$selected_value = isset( $_GET['price_robot'] ) ? $_GET['price_robot'] : 'all';

			$values = array(
				'all' => __( 'Price Robot: All Products', 'alg-woocommerce-price-robot' ),
				'yes' => __( 'Price Robot: Enabled', 'alg-woocommerce-price-robot' ),
				'no'  => __( 'Price Robot: Disabled', 'alg-woocommerce-price-robot' ),
			);

			echo '<select id="price_robot" name="price_robot">';
			foreach ( $values as $code => $name ) {
				echo '<option value="' . $code . '" ' . selected( $code, $selected_value, false ) . '>' . $name . '</option>';
			}
			echo '</select>';
		}
	}

	/**
	 * Add price robot column to products list
	 */
	function add_product_column_price_robot( $columns ) {
		$columns['price_robot'] = __( 'Price Robot', 'alg-woocommerce-price-robot' );
		if ( 'yes' === get_option( 'alg_price_robot_general_admin_products_debug_enabled' ) ) {
			$columns['price_robot_debug'] = __( 'Price Robot Debug', 'alg-woocommerce-price-robot' )
				. ' [' . date( 'Y-m-d H:i:s', get_option( 'get_orders_cron_started' ) ) . ']';
		}
		return $columns;
	}

	/**
	 * render_product_column_price_robot
	 */
	function render_product_column_price_robot( $column ) {

		if ( 'price_robot' != $column && 'price_robot_debug' != $column ) {
			return;
		}
		if ( 'price_robot_debug' === $column && 'yes' != get_option( 'alg_price_robot_general_admin_products_debug_enabled' ) ) {
			return;
		}

		$column_content = '';

		$the_product_id = get_the_ID();
		$the_product = wc_get_product( $the_product_id );

		if ( 'price_robot' === $column ) {
			$enabled = get_post_meta( $the_product_id, '_price_robot_enabled', true );
			if ( 'yes' === $enabled ) {
				$column_content .= '<a href="' . add_query_arg( 'disable_price_robot', $the_product_id, remove_query_arg( 'enable_price_robot' ) ) . '">'
					. '<img style="margin: -3px 3px;" width="16" height="16" title="' . __( 'Disable Price Robot', 'alg-woocommerce-price-robot' ) . '" src="' . alg_wc_price_robot()->plugin_url() . '/assets/images/price-robot-green.png">' . '</a>';
			}
			else {
				$column_content .= '<a href="' . add_query_arg( 'enable_price_robot', $the_product_id, remove_query_arg( 'disable_price_robot' ) ) . '">'
					. '<img style="margin: -3px 3px;" width="16" height="16" title="' . __( 'Enable Price Robot', 'alg-woocommerce-price-robot' ) . '" src="' . alg_wc_price_robot()->plugin_url() . '/assets/images/price-robot-gray.png">' . '</a>';
			}
		}

		$is_debug = 'price_robot_debug' === $column ? true : false;
		$separator = 'price_robot_debug' === $column ? '<br>' : ' ';

		if ( 'no' === get_option( 'alg_price_robot_general_variable_as_single', 'no' ) && $the_product->is_type( 'variable' ) ) {
			$variations = $the_product->get_available_variations();
			foreach ( $variations as $variation ) {
				$variation_product = wc_get_product( $variation['variation_id'] );
				$column_content .= $this->get_price_robot_admin_html( $variation_product, $is_debug );
				$column_content .= $separator;
			}
		} else {
			$column_content .= $this->get_price_robot_admin_html( $the_product, $is_debug );
		}

		echo $column_content;
	}

	/**
	 * get_price_robot_admin_html
	 */
	function get_price_robot_admin_html( $_product, $debug_html = false ) {

		if ( 'no' === get_option( 'alg_price_robot_general_variable_as_single', 'no' ) ) {
			$the_product_id = ( isset( $_product->variation_id ) ) ? $_product->variation_id : $_product->id;
		} else {
			$the_product_id = $_product->id;
		}

		$price_robot_html = '';

		remove_filter( 'woocommerce_get_price', 'alg_price_robot', PHP_INT_MAX, 2 );
		$the_original_price = $_product->get_price();
		add_filter( 'woocommerce_get_price', 'alg_price_robot', PHP_INT_MAX, 2 );

		$robot_price = alg_price_robot( $the_original_price, $_product, true );

		$color = ( $robot_price < $the_original_price ) ? 'red'   : 'gray';
		$color = ( $robot_price > $the_original_price ) ? 'green' : $color;

		$price_robot_html .= '<span style="color:' . $color . ';">' . wc_price( $robot_price ) . '</span>';

		if ( $debug_html ) {

			$last_sale       = get_post_meta( $the_product_id, '_last_sale',       true );
			$price_last_sale = get_post_meta( $the_product_id, '_price_last_sale', true );
			$no_sales = ( '' == $last_sale ) ? true : false;
			if ( $no_sales ) {
				$last_sale       = get_post_time( 'U', false, $the_product_id ); // product creation time
				$price_last_sale = $the_original_price;
			}
			$price_last_sale = apply_filters( 'alg_price_robot_last_sale_price_last_sale', $price_last_sale, $last_sale, $no_sales );

			$price_robot_html .= ' [' . date( 'Y-m-d H:i:s', $last_sale ) . ' @ ' . wc_price( $price_last_sale ) . ']';
//			$price_robot_html .= ' [' . get_post_meta( $the_product_id, '_pageviews_total', true ) . ']';
//			$price_robot_html .= ' [' . get_post_meta( $the_product_id, '_timeframe_sales', true ) . ']';
		}

		return $price_robot_html;
	}

}

endif;

return new Alg_WC_Price_Robot_Products();
