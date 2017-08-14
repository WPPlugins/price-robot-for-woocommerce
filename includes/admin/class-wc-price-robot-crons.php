<?php
/**
 * WooCommerce Price Robot - Crons
 *
 * @version 1.0.0
 * @since   1.0.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_Price_Robot_Crons' ) ) :

class Alg_WC_Price_Robot_Crons {

	/**
	 * Constructor.
	 */
	public function __construct() {

		$this->event_hook = 'alg_price_robot_get_data_hook';

		add_action( 'wp',              array( $this, 'schedule_the_events' ) );
		add_action( $this->event_hook, array( $this, 'get_orders' ) );
		add_filter( 'cron_schedules',  array( $this, 'cron_add_custom_intervals' ) );

		add_action( 'init', array( $this, 'get_orders_manual' ) );

		add_action( 'template_redirect', array( $this, 'count_product_pageviews' ), PHP_INT_MAX );
	}

	/*
	 * returns IP on success, null on error
	 */
	function get_the_ip() {
		$ip = null;
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		return $ip;
	}

	/**
	 * count_product_pageviews
	 */
	function count_product_pageviews() {

		if ( is_product() ) {

			global $wpdb;
			$table_name = $wpdb->prefix . 'alg_product_pageviews';
			$wpdb->insert(
				$table_name,
				array(
					'post_id' => get_the_ID(),
					'time'    => current_time( 'mysql' ),
					'ip'      => $this->get_the_ip(),
				)
			);

			// Total
			$prev_pageviews = get_post_meta( get_the_ID(), '_pageviews_total', true );
			$pageviews = ( '' == $prev_pageviews ) ? 1 : $prev_pageviews + 1;
			update_post_meta( get_the_ID(), '_pageviews_total', $pageviews );
		}
	}

	/**
	 * On an early action hook, check if the hook is scheduled - if not, schedule it
	 */
	function schedule_the_events() {
		$selected_interval = get_option( 'alg_price_robot_general_admin_crons_update', 'daily' );
		$update_intervals = alg_get_cron_update_intervals();
		foreach ( $update_intervals as $interval => $desc ) {
			$event_timestamp = wp_next_scheduled( $this->event_hook, array( $interval ) );
			if ( ! $event_timestamp && $selected_interval === $interval ) {
				wp_schedule_event( time(), $selected_interval, $this->event_hook, array( $selected_interval ) );
			} elseif ( $event_timestamp && $selected_interval !== $interval ) {
				wp_unschedule_event( $event_timestamp, $this->event_hook, array( $interval ) );
			}
		}
	}

	/**
	 * cron_add_custom_intervals
	 */
	function cron_add_custom_intervals( $schedules ) {
		$schedules['weekly'] = array(
			'interval' => 604800,
			'display' => __( 'Once Weekly', 'alg-woocommerce-price-robot' )
		);
		$schedules['minutely'] = array(
			'interval' => 60,
			'display' => __( 'Once a Minute', 'alg-woocommerce-price-robot' )
		);
		return $schedules;
	}

	/**
	 * get_orders_manual
	 */
	public function get_orders_manual() {
		if ( ! isset( $_GET['get_orders_manual'] ) || ! is_super_admin() ) return;
		$this->get_orders();
		echo '<div id="message" class="updated"><p><strong>' . __( 'Price robot data have been updated.', 'alg-woocommerce-price-robot' ) . '</strong></p></div>';
	}

	/**
	 * get_order
	 */
	function get_order( $products_data, $item, $product_id, $order_id ) {

		if ( ! isset( $products_data[ $product_id ][ 'timeframe_sales' ] ) ) {
			$products_data[ $product_id ][ 'timeframe_sales' ] = 0;
		}
		$the_order_date = get_post_time( 'U', false, $order_id );
		$current_time = current_time( 'timestamp' );
		if ( $the_order_date > ( $current_time - get_option( 'alg_price_robot_last_sale_discount_timeframe_days', 30 ) * 24 * 60 * 60 ) ) {
			$products_data[ $product_id ][ 'timeframe_sales' ] += $item['qty'];
		}

		if ( ! isset( $products_data[ $product_id ][ 'last_sale' ] ) ) {
			$products_data[ $product_id ][ 'last_sale' ] =
				get_the_time( 'U' );
		}

		if ( ! isset( $products_data[ $product_id ][ 'price_last_sale' ] ) ) {
			$line_subtotal = wc_prices_include_tax() ? ( $item['line_subtotal'] + $item['line_subtotal_tax'] ) : $item['line_subtotal'];
			$products_data[ $product_id ][ 'price_last_sale' ] =
				round(
					( $line_subtotal / $item['qty'] ),
					get_option( 'woocommerce_price_num_decimals', 2 )
				);
		}

		return $products_data;
	}

	/**
	 * get_orders
	 */
	public function get_orders() {

		update_option( 'get_orders_cron_started', current_time( 'timestamp' ) );

		$block_size = 96;
		$offset = 0;
		$products_data = array();
		while( true ) {
			$args_orders = array(
				'post_type'      => 'shop_order',
				'post_status'    => 'wc-completed',
				'posts_per_page' => $block_size,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'offset'         => $offset,
			);
			$loop_orders = new WP_Query( $args_orders );
			if ( ! $loop_orders->have_posts() ) break;
			while ( $loop_orders->have_posts() ) {
				$loop_orders->the_post();
				$order_id = $loop_orders->post->ID;
				$order = wc_get_order( $order_id );
				$items = $order->get_items();
				foreach ( $items as $item ) {
					$products_data = $this->get_order( $products_data, $item, $item['product_id'], $order_id );
					if ( isset( $item['variation_id'] ) && 0 != $item['variation_id'] ) {
						$products_data = $this->get_order( $products_data, $item, $item['variation_id'], $order_id );
					}
				}
			}
			$offset += $block_size;
		}

		$offset = 0;
		$timeframe_sales_average = $timeframe_sales_average_counter = 0;
		while( true ) {
			$args_products = array(
				'post_type'      => 'product',
//				'post_status'    => 'publish',
				'posts_per_page' => $block_size,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'offset'         => $offset,
			);
			$loop_products = new WP_Query( $args_products );
			if ( ! $loop_products->have_posts() ) break;
			while ( $loop_products->have_posts() ) {
				$loop_products->the_post();
				$product_id = $loop_products->post->ID;

				$timeframe_sales = isset( $products_data[ $product_id ]['timeframe_sales'] ) ? $products_data[ $product_id ]['timeframe_sales'] : 0;
				$last_sale       = isset( $products_data[ $product_id ]['last_sale'] ) ? $products_data[ $product_id ]['last_sale'] : '';
				$price_last_sale = isset( $products_data[ $product_id ]['price_last_sale'] ) ? $products_data[ $product_id ]['price_last_sale'] : '';

				update_post_meta( $product_id, '_timeframe_sales', $timeframe_sales );
				update_post_meta( $product_id, '_last_sale',       $last_sale );
				update_post_meta( $product_id, '_price_last_sale', $price_last_sale );

				if ( $timeframe_sales > 0 ) {
					$timeframe_sales_average += intval( $timeframe_sales );
					$timeframe_sales_average_counter++;
				}
			}
			$offset += $block_size;
		}
		$timeframe_sales_average = ( 0 != $timeframe_sales_average_counter ) ? /* round */( $timeframe_sales_average / $timeframe_sales_average_counter ) : 0;
		update_option( 'timeframe_sales_average', $timeframe_sales_average );
//		update_option( 'timeframe_sales_average', $timeframe_sales_average . ' / ' . $timeframe_sales_average_counter );

		update_option( 'get_orders_cron_finished', current_time( 'timestamp' ) );
	}
}

endif;

return new Alg_WC_Price_Robot_Crons();
