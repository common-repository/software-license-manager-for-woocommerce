<?php
/**
 * Software License Manager for WooCommerce
 *
 * @package    Software License Manager for WooCommerce
 * @subpackage SlmForWooCommerce Main Functions
/*  Copyright (c) 2019- Katsushi Kawamori (email : dodesyoswift312@gmail.com)
	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; version 2 of the License.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

$slmforwoocommerce = new SlmForWooCommerce();

/** ==================================================
 * Class Main function
 *
 * @since 1.00
 */
class SlmForWooCommerce {

	/** ==================================================
	 * Construct
	 *
	 * @since 1.00
	 */
	public function __construct() {

		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'slmwoo_create_custom_field' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'slmwoo_save_custom_field' ) );
		add_filter( 'woocommerce_add_cart_item_data', array( $this, 'slmwoo_add_custom_field_item_data' ), 10, 4 );
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'slmwoo_before_calculate_totals' ), 10, 1 );
		add_filter( 'woocommerce_cart_item_name', array( $this, 'slmwoo_cart_item_name' ), 10, 3 );
		add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'slmwoo_add_custom_data_to_order' ), 10, 4 );
		add_action( 'woocommerce_thankyou', array( $this, 'custom_woocommerce_auto_complete_order' ) );

	}

	/** ==================================================
	 * Main
	 *
	 * @param  object $order  order.
	 * @param  int    $max_allowed_domains  max_allowed_domains.
	 * @param  int    $expiry_days  expiry_days.
	 * @since 1.00
	 */
	public function slmforwoocommerce_func( $order, $max_allowed_domains, $expiry_days ) {

		$settings_tbl = get_option( 'slmforwoocommerce' );

		/* License server URL */
		$license_server_url = $settings_tbl['license_server_url'];
		/* The secret key */
		$secretkey = $settings_tbl['secretkey'];
		/* Expiry secound */
		$expiry_second = $expiry_days * 3600 * 24;

		/* Optional Data */
		$firstname   = $order->get_billing_first_name();
		$lastname    = $order->get_billing_last_name();
		$email       = $order->get_billing_email();
		if ( function_exists( 'wp_date' ) ) {
			$txn_id      = wp_date( 'YmdHis' );
			$create_date = wp_date( 'Y-m-d' );
			$expiry_date = wp_date( 'Y-m-d', time() + $expiry_second );
		} else {
			$txn_id      = date_i18n( 'YmdHis' );
			$create_date = date_i18n( 'Y-m-d' );
			$expiry_date = date_i18n( 'Y-m-d', time() + $expiry_second );
		}
		$api_params = array(
			'slm_action' => 'slm_create_new',
			'secret_key' => $secretkey,
			'first_name' => $firstname,
			'last_name' => $lastname,
			'email' => $email,
			'txn_id' => $txn_id,
			'max_allowed_domains' => $max_allowed_domains,
			'date_created' => $create_date,
			'date_expiry' => $expiry_date,
		);

		/* Send query to the license manager server */
		$response = wp_remote_get(
			add_query_arg( $api_params, $license_server_url ),
			array(
				'timeout' => 20,
				'sslverify' => false,
			)
		);

		/* Check for error in the response */
		if ( is_wp_error( $response ) ) {
			echo 'Unexpected Error! The query returned with an error.';
		}

		/* License data. */
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		if ( is_object( $license_data ) ) {
			if ( property_exists( $license_data, 'key' ) ) {
				return $license_data->key;
			}
		}

	}

	/** ==================================================
	 * Display the custom checkbox
	 *
	 * @since 1.10
	 */
	public function slmwoo_create_custom_field() {

		woocommerce_wp_checkbox(
			array(
				'id'    => 'slmwoo_addlicense',
				'label' => __( 'Add License Key', 'software-license-manager-for-woocommerce' ),
			)
		);

		woocommerce_wp_text_input(
			array(
				'id'                => 'slmwoo_domain',
				'label'             => __( 'Max allowed domains', 'software-license-manager-for-woocommerce' ),
				'type'              => 'number',
				'custom_attributes' => array(
					'step'  => '1',
					'min'   => '1',
				),
				'desc_tip' => true,
				'description' => __( 'Number of domains/installs in which this license can be used.', 'software-license-manager-for-woocommerce' ),
			)
		);

		woocommerce_wp_text_input(
			array(
				'id'                => 'slmwoo_expiry',
				'label'             => __( 'Expiry days', 'software-license-manager-for-woocommerce' ),
				'type'              => 'number',
				'type'              => 'number',
				'custom_attributes' => array(
					'step'  => '1',
					'min'   => '1',
				),
				'desc_tip' => true,
				'description' => __( 'Expiry date of license. This is not the expiration date of the license itself, but the expiration date of the certification. If you have been authenticated before the deadline, the license will continue after the deadline.', 'software-license-manager-for-woocommerce' ),
			)
		);
	}

	/** ==================================================
	 * Save the custom field
	 *
	 * @param int $post_id  post_id.
	 * @since 1.10
	 */
	public function slmwoo_save_custom_field( $post_id ) {

		if ( ! ( isset( $_POST['woocommerce_meta_nonce'] ) || wp_verify_nonce( sanitize_key( $_POST['woocommerce_meta_nonce'] ), 'woocommerce_save_data' ) ) ) {
			return false;
		}
		$product = wc_get_product( $post_id );
		if ( isset( $_POST['slmwoo_addlicense'] ) && ! empty( $_POST['slmwoo_addlicense'] ) ) {
			$slmwoo_addlicense = sanitize_text_field( wp_unslash( $_POST['slmwoo_addlicense'] ) );
		} else {
			$slmwoo_addlicense = null;
		}
		$product->update_meta_data( 'slmwoo_addlicense', $slmwoo_addlicense );
		if ( isset( $_POST['slmwoo_domain'] ) && ! empty( $_POST['slmwoo_domain'] ) ) {
			$product->update_meta_data( 'slmwoo_domain', intval( $_POST['slmwoo_domain'] ) );
		}
		if ( isset( $_POST['slmwoo_expiry'] ) && ! empty( $_POST['slmwoo_expiry'] ) ) {
			$product->update_meta_data( 'slmwoo_expiry', intval( $_POST['slmwoo_expiry'] ) );
		}
		$product->save();

	}

	/** ==================================================
	 * Add the text field as item data to the cart object
	 *
	 * @param array $cart_item_data  Cart item meta data.
	 * @param int   $product_id  Product ID.
	 * @param int   $variation_id  Variation ID.
	 * @param bool  $quantity  Quantity.
	 * @since 1.10
	 */
	public function slmwoo_add_custom_field_item_data( $cart_item_data, $product_id, $variation_id, $quantity ) {

		if ( get_post_meta( $product_id, 'slmwoo_addlicense', true ) &&
				get_post_meta( $product_id, 'slmwoo_domain', true ) &&
				get_post_meta( $product_id, 'slmwoo_expiry', true ) ) {
			$cart_item_data['slmwoo_license_key'] = __( 'Add License Key', 'software-license-manager-for-woocommerce' );
			$cart_item_data['slmwoo_domain'] = get_post_meta( $product_id, 'slmwoo_domain', true );
			$cart_item_data['slmwoo_expiry'] = get_post_meta( $product_id, 'slmwoo_expiry', true );
		}
		return $cart_item_data;
	}

	/** ==================================================
	 * Update the price in the cart
	 *
	 * @param object $cart_obj  cart_obj.
	 * @since 1.10
	 */
	public function slmwoo_before_calculate_totals( $cart_obj ) {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}
		/* Iterate through each cart item */
		foreach ( $cart_obj->get_cart() as $key => $value ) {
			if ( isset( $value['total_price'] ) ) {
				$price = $value['total_price'];
				$value['data']->set_price( ( $price ) );
			}
		}
	}

	/** ==================================================
	 * Display the custom field value in the cart
	 *
	 * @param string $name  name.
	 * @param array  $cart_item  cart_item.
	 * @param object $cart_item_key  cart_item_key.
	 * @since 1.10
	 */
	public function slmwoo_cart_item_name( $name, $cart_item, $cart_item_key ) {
		if ( isset( $cart_item['slmwoo_license_key'] ) ) {
			/* translators: %1$s: Add license key %2$s,%3$d: Allowed domains %4$s,%5$d: Expiry days */
			$name .= sprintf(
				'<div>%1$s</div><div>%2$s: %3$d</div><div>%4$s: %5$d</div>',
				esc_html( $cart_item['slmwoo_license_key'] ),
				esc_html( __( 'Max allowed domains', 'software-license-manager-for-woocommerce' ) ),
				esc_html( $cart_item['slmwoo_domain'] ),
				esc_html( __( 'Expiry days', 'software-license-manager-for-woocommerce' ) ),
				esc_html( $cart_item['slmwoo_expiry'] )
			);
		}
		return $name;
	}

	/** ==================================================
	 * Add custom field to order object
	 *
	 * @param array  $item  item.
	 * @param object $cart_item_key  cart_item_key.
	 * @param array  $values  values.
	 * @param object $order  order.
	 * @since 1.10
	 */
	public function slmwoo_add_custom_data_to_order( $item, $cart_item_key, $values, $order ) {
		foreach ( $item as $cart_item_key => $values ) {
			if ( isset( $values['slmwoo_license_key'] ) ) {
				$license_key = $this->slmforwoocommerce_func( $order, $values['slmwoo_domain'], $values['slmwoo_expiry'] );
				if ( $license_key ) {
					$item->add_meta_data( __( 'License Key', 'software-license-manager-for-woocommerce' ), $license_key, true );
					$item->add_meta_data( __( 'Max allowed domains', 'software-license-manager-for-woocommerce' ), $values['slmwoo_domain'], true );
					$item->add_meta_data( __( 'Expiry days', 'software-license-manager-for-woocommerce' ), $values['slmwoo_expiry'], true );
				}
			}
		}
	}

	/** ==================================================
	 * Auto Complete all WooCommerce orders
	 *
	 * @param int $order_id  order_id.
	 * @since 1.10
	 */
	public function custom_woocommerce_auto_complete_order( $order_id ) {

		if ( ! $order_id ) {
			return;
		}

		$order = wc_get_order( $order_id );

		/* No updated status for orders delivered with Bank wire, Cash on delivery and Cheque payment methods. */
		if ( in_array( $order->get_payment_method(), array( 'bacs', 'cod', 'cheque', '' ) ) ) {
			return;
		} else if ( $order->has_status( 'processing' ) ) {
			/* For paid Orders with all others payment methods (paid order status "processing") */
			$order->update_status( 'completed' );
		}

	}

}


