<?php
/**
 * WC GlobalPayments Admin View Transaction Status Trait
 */

namespace GlobalPayments\WooCommercePaymentGatewayProvider\Gateways\Traits;

use GlobalPayments\WooCommercePaymentGatewayProvider\Plugin;
use mysql_xdevapi\Exception;

defined( 'ABSPATH' ) || exit;

trait TransactionStatusTrait {
	/**
	 * @param $order
	 */
	public function transaction_status_modal( $order ) {
		if ( $order->get_type() !== 'shop_order' || ! $order->has_status( array( 'pending', 'failed' ) ) ) {
			return;
		}

		// The HTML needed for the `View Transaction Status` modal
		include_once( Plugin::get_path() . '/includes/admin/views/html-transaction-status.php' );

		$this->tokenization_script();

		wp_enqueue_script(
			'globalpayments-modal',
			Plugin::get_url( '/assets/admin/js/globalpayments-modal.js' ),
			array(
				'jquery',
				'wc-backbone-modal',
				'jquery-blockui'
			),
			WC()->version,
			true
		);
		wp_enqueue_script(
			'globalpayments-admin',
			Plugin::get_url( '/assets/admin/js/globalpayments-admin.js' ),
			array(
				'jquery',
				'jquery-blockui',
				'globalpayments-modal'
			),
			WC()->version,
			true
		);
		wp_localize_script(
			'globalpayments-admin',
			'globalpayments_admin_params',
			array(
				'_wpnonce'            => wp_create_nonce( 'woocommerce-globalpayments-pay' ),
				'gateway_id'          => $this->id,
				'payorder_url'        => WC()->api_request_url( 'globalpayments_pay_order' ),
				'payment_methods'     => $this->get_payment_methods( $order->get_customer_id() ),
				'payment_methods_url' => WC()->api_request_url( 'globalpayments_get_payment_methods' ),
			)
		);
		wp_enqueue_style(
			'globalpayments-admin',
			Plugin::get_url( '/assets/admin/css/globalpayments-admin.css' ),
			array(),
			WC()->version
		);
	}

	/**
	 * Endpoint for retrieving Customer payment methods.
	 */
	public function transaction_status_modal_get_payment_methods() {
		$payment_methods = array();
		$nonce_value     = wc_get_var( $_REQUEST['_wpnonce'], '' ); // @codingStandardsIgnoreLine.
		if ( ! wp_verify_nonce( $nonce_value, 'woocommerce-globalpayments-pay' ) ) {
			wp_send_json( $payment_methods );
		}
		$customer_id = absint( $_GET['customer_id'] );
		wp_send_json( $this->get_payment_methods( $customer_id ) );
	}

	/**
	 * Retrieve Customer payment methods.
	 *
	 * @param int $customer_id
	 *
	 * @return array
	 */
	private function get_transaction_details( int $customer_id ) {
		$payment_methods = array();
		if ( empty( $customer_id ) ) {
			return $payment_methods;
		}
		$tokens = \WC_Payment_Tokens::get_customer_tokens( $customer_id, $this->id );
		foreach ( $tokens as $token ) {
			$payment_methods[] = array(
				'id'           => $token->get_id(),
				'display_name' => $token->get_display_name(),
				'is_default'   => $token->is_default(),
			);
		}

		return $payment_methods;
	}
}
