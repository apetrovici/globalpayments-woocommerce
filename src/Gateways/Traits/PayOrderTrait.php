<?php
/**
 * WC GlobalPayments Admin Pay for Order Trait
 */

namespace GlobalPayments\WooCommercePaymentGatewayProvider\Gateways\Traits;

use GlobalPayments\WooCommercePaymentGatewayProvider\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Trait PayOrderTrait
 * @package GlobalPayments\WooCommercePaymentGatewayProvider\Gateways\Traits
 */
trait PayOrderTrait {
	/**
	 * @param $order
	 */
	public function pay_order_modal( $order ) {
		if ( $order->get_type() !== 'shop_order' || ! $order->has_status( array( 'pending', 'failed' ) ) ) {
			return;
		}

		// The HTML needed for the `Pay for Order` modal
		include_once( Plugin::get_path() . '/includes/admin/views/html-pay-order.php' );

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
				'_wpnonce'     => wp_create_nonce( 'woocommerce-globalpayments-pay' ),
				'gateway_id'   => $this->id,
				'payorder_url' => WC()->api_request_url( 'globalpayments_pay_order' ) . '&key=' . $order->get_order_key(),
			)
		);
	}

	/**
	 * Process the payment in Admin modal.
	 */
	public function pay_order_modal_process_payment() {
		try {
			// Validate modal request
			if ( ! isset( $_POST['woocommerce_globalpayments_pay'], $_GET['key'] ) ) {
				throw new Exception( __( 'Invalid payment request.', 'globalpayments-gateway-provider-for-woocommerce' ) );
			}

			wc_nocache_headers();

			$nonce_value = wc_get_var( $_REQUEST['woocommerce-globalpayments-pay-nonce'], wc_get_var( $_REQUEST['_wpnonce'], '' ) ); // @codingStandardsIgnoreLine.
			if ( ! wp_verify_nonce( $nonce_value, 'woocommerce-globalpayments-pay' ) ) {
				throw new \Exception( __( 'Invalid payment request.', 'globalpayments-gateway-provider-for-woocommerce' ) );
			}

			$order_key = wp_unslash( $_GET['key'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$order_id  = (int) wc_get_post_data_by_key( 'order_id' );
			$order     = wc_get_order( $order_id );
			if ( $order_id !== $order->get_id() || ! hash_equals( $order->get_order_key(), $order_key ) ) {
				throw new \Exception( __( 'Invalid payment request.', 'globalpayments-gateway-provider-for-woocommerce' ) );
			}

			// Validate
			if ( ! empty( $_POST['transaction_id'] ) && empty( $_POST['allow_order'] ) ) {
				throw new \Exception( __( 'This order has a transaction ID associated with it already. Please click the checkbox to proceed with payment.', 'globalpayments-gateway-provider-for-woocommerce' ) );
			}

			// Update payment method.
			$order->set_payment_method( $this->id );
			$order->save();

			// Process Payment
			$result = $this->process_payment( $order_id );
			if ( isset( $result['result'] ) && 'success' === $result['result'] ) {
				wp_send_json( [
					'success'   => true,
				] );
			} else {
				throw new Exception( __( 'Something went wrong while processing the payment.', 'globalpayments-gateway-provider-for-woocommerce' ) );
			}
		} catch ( \Exception $e ) {
			wp_send_json( [
				'error'   => true,
				'message' => $e->getMessage(),
			] );
		}
	}

	/**
	 * Secure payment fields styles for Admin modal.
	 *
	 * @param string $secure_payment_fields_styles CSS styles.
	 *
	 * @return false|string
	 */
	public function pay_order_modal_secure_payment_fields_styles( $secure_payment_fields_styles ) {
		$secure_payment_fields_styles = json_decode( $secure_payment_fields_styles, true );

		$secure_payment_fields_styles['#secure-payment-field[type=button]:focus'] = array(
			'color'      => '#fff',
			'background' => '#2271b1',
		);
		$secure_payment_fields_styles['#secure-payment-field[type=button]:hover'] = array(
			'color'      => '#fff',
			'background' => '#2271b1',
		);
		$secure_payment_fields_styles['button#secure-payment-field.submit'] = array(
			'border-width'             => '1px',
			'border-style'             => 'solid',
//			'border'             => '0',
			'border-radius'      => '3px',
			'background'         => '#2271b1',
//			'background-color'   => '#333333',
			'border-color'       => '#2271b1',
			'box-sizing'       => 'border-box',
			'color'              => '#fff',
			'cursor'             => 'pointer',
			'padding'            => '0',

			'text-decoration'    => 'none',
//			'font-weight'        => '600',
			'text-shadow'        => 'none',
			'display'            => 'inline-block',
			'-webkit-appearance' => 'none',
//			'height'             => 'initial',
//			'width'              => '100%',
			'flex'               => 'auto',
			'position'           => 'static',
//			'margin'             => '0',
//
			'white-space'   => 'nowrap',
			'margin-bottom' => '0',
//			'float'         => 'none',
//
			'font' => '400 1.41575em/1.618 -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif !important',
			'font-size' => '13px',
			'font-weight' => '400',
			'text-align'    => 'center',
		);

		return json_encode( $secure_payment_fields_styles );
	}
}