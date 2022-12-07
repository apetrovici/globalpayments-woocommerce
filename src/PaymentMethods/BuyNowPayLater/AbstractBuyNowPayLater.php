<?php

namespace GlobalPayments\WooCommercePaymentGatewayProvider\PaymentMethods\BuyNowPayLater;

use GlobalPayments\Api\Entities\Enums\TransactionStatus;
use GlobalPayments\Api\Entities\Reporting\TransactionSummary;
use GlobalPayments\Api\Entities\Transaction;
use GlobalPayments\WooCommercePaymentGatewayProvider\Gateways\AbstractGateway;
use GlobalPayments\WooCommercePaymentGatewayProvider\Gateways\GpApiGateway;
use GlobalPayments\WooCommercePaymentGatewayProvider\Gateways\Traits\TransactionInfoTrait;
use GlobalPayments\WooCommercePaymentGatewayProvider\Utils\Utils;
use WC_Order;
use WC_Payment_Gateway;

defined( 'ABSPATH' ) || exit;

abstract class AbstractBuyNowPayLater extends WC_Payment_Gateway {
	use TransactionInfoTrait;

	/**
	 * Payment method BNPL provider. Should be overridden by individual BNPL payment methods implementations.
	 *
	 * @var string
	 */
	public $payment_method_BNPL_provider;

	/**
	 * Payment method default title.
	 *
	 * @var string
	 */
	public $default_title;

	/**
	 * Action to perform on checkout
	 *
	 * Possible actions:
	 *
	 * - `authorize` - authorize the card without auto capturing
	 * - `sale` - authorize the card with auto capturing
	 * - `verify` - verify the card without authorizing
	 *
	 * @var string
	 */
	public $payment_action;

	public function __construct() {
		$this->gateway    = new GpApiGateway( true );
		$this->has_fields = true;
		$this->supports   = array(
			'refunds',
		);

		$this->configure_method_settings();
		$this->init_form_fields();
		$this->init_settings();
		$this->configure_merchant_settings();

		$this->add_hooks();
	}

	public function add_hooks() {
		/**
		 * The WooCommerce API allows plugins make a callback to a special URL that will then load the specified class (if it exists)
		 * and run an action. This is also useful for gateways that are not initialized.
		 */
		add_action( 'woocommerce_api_' . $this->id . '_return', array(
			$this,
			'process_bnpl_return'
		) );
		add_action( 'woocommerce_api_' . $this->id . '_status', array(
			$this,
			'process_bnpl_status'
		) );
		add_action( 'woocommerce_api_' . $this->id . '_cancel', array(
			$this,
			'process_bnpl_cancel'
		) );

		add_action( 'woocommerce_after_checkout_validation', function( $data, $wp_error ) {
			if ( $this->id != $data['payment_method'] ) {
				return;
			}

			if ( empty( $data['billing_postcode'] ) && ( empty( $wp_error->errors['billing_postcode_required'] ) || empty( $wp_error->errors['billing_postcode_validation'] ) ) ) {
				$wp_error->add ( 'billing_postcode', __( '<strong>Billing ZIP Code</strong> is a required field for this payment method.', 'globalpayments-gateway-provider-for-woocommerce' ) );
			}
			if ( empty( $data['shipping_postcode'] ) && ( empty( $wp_error->errors['shipping_postcode_required'] ) || empty( $wp_error->errors['shipping_postcode_validation'] ) ) ) {
				$wp_error->add ( 'shipping_postcode', __( '<strong>Shipping ZIP Code</strong> is a required field for this payment method.', 'globalpayments-gateway-provider-for-woocommerce' ) );
			}
			if ( empty( $data['billing_phone'] ) && ( empty( $wp_error->errors['billing_phone_required'] ) || empty( $wp_error->errors['billing_phone_validation'] ) ) ) {
				$wp_error->add ( 'billing_phone', __( '<strong>Phone</strong> is a required field for this payment method.', 'globalpayments-gateway-provider-for-woocommerce' ) );
			}
		}, 10, 2);

		// Admin View Transaction Info hooks
		add_action( 'woocommerce_admin_order_data_after_order_details', array( $this, 'transaction_info_modal' ), 99 );
		add_action( 'woocommerce_api_globalpayments_get_transaction_info', array( $this, 'get_transaction_info' ) );
	}

	/**
	 * Sets the necessary WooCommerce payment method settings for exposing the
	 * gateway in the WooCommerce Admin.
	 *
	 * @return
	 */
	abstract public function configure_method_settings();

	/**
	 * Custom admin options to configure the gateway-specific credentials, features, etc.
	 *
	 * @return array
	 */
	abstract public function get_gateway_form_fields();

	/**
	 * Countries this payment method is allowed for.
	 *
	 * @return array
	 */
	abstract public function get_available_countries();

	/**
	 * Currencies this payment method is allowed for.
	 *
	 * @return array
	 */
	abstract public function get_available_currencies();

	/**
	 * @inheritdoc
	 */
	public function is_available() {
		$currencies = $this->get_available_currencies();
		if ( ! empty( $currencies ) && ! in_array( get_woocommerce_currency(), $currencies ) ) {
			return false;
		}
		$countries = $this->get_available_countries();
		if ( ! empty( $countries ) && WC()->cart && ! in_array( WC()->cart->get_customer()->get_billing_country(), $countries ) ) {
			return false;
		}

		return parent::is_available();
	}

	/**
	 * @inheritdoc
	 */
	public function init_form_fields() {
		$this->form_fields = array_merge(
			array(
				'enabled' => array(
					'title'   => __( 'Enable/Disable', 'globalpayments-gateway-provider-for-woocommerce' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable Gateway', 'globalpayments-gateway-provider-for-woocommerce' ),
					'default' => 'no',
				),
				'title'   => array(
					'title'             => __( 'Title', 'globalpayments-gateway-provider-for-woocommerce' ),
					'type'              => 'text',
					'description'       => __( 'This controls the title which the user sees during checkout.', 'globalpayments-gateway-provider-for-woocommerce' ),
					'default'           => __( $this->default_title, 'globalpayments-gateway-provider-for-woocommerce' ),
					'desc_tip'          => true,
					'custom_attributes' => array( 'required' => 'required' ),
				),
			),
			$this->get_gateway_form_fields(),
			array(
				'payment_action' => array(
					'title'       => __( 'Payment Action', 'globalpayments-gateway-provider-for-woocommerce' ),
					'type'        => 'select',
					'description' => __( 'Choose whether you wish to capture funds immediately or authorize payment only for a delayed capture.', 'globalpayments-gateway-provider-for-woocommerce' ),
					'default'     => AbstractGateway::TXN_TYPE_SALE,
					'desc_tip'    => true,
					'options'     => array(
						AbstractGateway::TXN_TYPE_SALE      => __( 'Authorize + Capture', 'globalpayments-gateway-provider-for-woocommerce' ),
						AbstractGateway::TXN_TYPE_AUTHORIZE => __( 'Authorize only', 'globalpayments-gateway-provider-for-woocommerce' ),
					),
				),
			)
		);
	}

	/**
	 * Sets the configurable merchant settings for use elsewhere in the class.
	 *
	 * @return
	 */
	public function configure_merchant_settings() {
		$this->title             = $this->get_option( 'title' );
		$this->enabled           = $this->get_option( 'enabled' );
		$this->payment_action    = $this->get_option( 'payment_action' );

		foreach ( $this->get_gateway_form_fields() as $key => $options ) {
			if ( ! property_exists( $this, $key ) ) {
				continue;
			}

			$value = $this->get_option( $key );

			if ( 'checkbox' === $options['type'] ) {
				$value = 'yes' === $value;
			}

			$this->{$key} = $value;
		}
	}

	/**
	 * Returns provider and notifications endpoints.
	 *
	 * @return array
	 */
	public function get_provider_endpoints() {
		return array(
			'provider'  => $this->payment_method_BNPL_provider,
			'returnUrl' => WC()->api_request_url( $this->id . '_return', true ),
			'statusUrl' => WC()->api_request_url( $this->id . '_status', true ),
			'cancelUrl' => WC()->api_request_url( $this->id . '_cancel', true ),
		);
	}

	/**
	 * @inheritdoc
	 */
	public function process_payment( $order_id ) {
		// At this point, order should be placed in 'Pending Payment', but products should still be visible in the cart
		$order = wc_get_order( $order_id );

		// 1. Initiate the payment
		$gateway_response = $this->initiate_payment( $order );

		$this->validate_transaction_status( $gateway_response );
		$this->validate_provider_redirect_url( $gateway_response );

		// Add order note  prior to customer redirect
		$note_text = sprintf(
			'%1$s%2$s %3$s %5$s. Transaction ID: %4$s.',
			get_woocommerce_currency_symbol( $order->get_currency() ),
			$order->get_total(),
			__( 'payment initiated with', 'globalpayments-gateway-provider-for-woocommerce' ),
			$gateway_response->transactionId,
			$this->payment_method_BNPL_provider
		);
		$order->add_order_note( $note_text );
		$order->set_transaction_id( $gateway_response->transactionId );
		$order->save();

		// 2. Redirect the customer
		return array(
			'result'   => 'success',
			'redirect' => $gateway_response->transactionReference->bnplResponse->redirectUrl,
		);
	}

	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		return $this->gateway->process_refund( $order_id, $amount, $reason );
	}

	/**
	 * Initiate the payment.
	 *
	 * @param WC_Order $order
	 *
	 * @throws \GlobalPayments\Api\Entities\Exceptions\ApiException
	 */
	private function initiate_payment( WC_Order $order ) {
		$request = $this->gateway->prepare_request( AbstractGateway::TXN_TYPE_BNPL_AUTHORIZE, $order );
		$request->set_request_data( array(
			'globalpayments_bnpl' => $this->get_provider_endpoints(),
		) );

		$gateway_response = $this->gateway->client->submit_request( $request );

		$is_successful    = $this->gateway->handle_response( $request, $gateway_response );

		if ( ! $is_successful ) {
			// @TODO: display proper message for customer
			throw new \Exception('Something went wrong with Affirm - transaction failed');
		}

		return $gateway_response;
	}

	/**
	 * Validate the transaction is initiated after the Initiate call.
	 *
	 * @param Transaction $gateway_response
	 *
	 * @throws \Exception
	 */
	private function validate_transaction_status( Transaction $gateway_response ) {
		switch( $gateway_response->responseMessage ) {
			case TransactionStatus::INITIATED:
				break;
			default:
				// @TODO: display proper message for customer
				throw new \Exception('Something went wrong with Affirm - transaction ' . $gateway_response->responseMessage );
		}
	}

	/**
	 * Validate the provider redirect URL is returned in the Initiate response (after the Initiate call).
	 *
	 * @param Transaction $gateway_response
	 *
	 * @throws \Exception
	 */
	private function validate_provider_redirect_url( Transaction $gateway_response ) {
		if ( empty( $gateway_response->transactionReference->bnplResponse->redirectUrl ) ) {
			// @TODO: display proper message for customer
			throw new \Exception('Something went wrong with Affirm - no redirect url');
		}
	}

	/**
	 * Handle customer redirect URL.
	 */
	public function process_bnpl_return() {
		try {
			Utils::validate_bnpl_request();

			$gateway_response = $this->gateway->get_transaction_details_by_txn_id( wc_clean( $_GET['id'] ) );
			$order = $this->get_order( $gateway_response );

			if( TransactionStatus::PREAUTHORIZED !== $gateway_response->transactionStatus ) {
				if ( TransactionStatus::DECLINED == $gateway_response->transactionStatus ) {
					$note_text = sprintf(
						'%1$s%2$s %3$s. Transaction ID: %4$s.',
						get_woocommerce_currency_symbol( $order->get_currency() ),
						$order->get_total(),
						__( 'payment failed/declined', 'globalpayments-gateway-provider-for-woocommerce' ),
						$order->get_transaction_id()
					);
					$order->add_order_note( $note_text );
					$order->set_status( 'failed' );
					$order->save();
				}
				// @TODO: display proper message for customer
				throw new \Exception('Something went wrong with Affirm - transaction doesn\'t have the PREAUTHORIZED status. Current status: ' . $gateway_response->transactionStatus );
			}

			$note_text = sprintf(
				'%1$s%2$s %3$s. Transaction ID: %4$s.',
				get_woocommerce_currency_symbol( $order->get_currency() ),
				$order->get_total(),
				__( 'authorized', 'globalpayments-gateway-provider-for-woocommerce' ),
				$order->get_transaction_id()
			);
			$order->add_order_note( $note_text );
			$order->set_status( 'processing' );
			$order->save();

			if ( $this->payment_action == AbstractGateway::TXN_TYPE_SALE ) {
				AbstractGateway::capture_credit_card_authorization( $order );
				$order->payment_complete();
			}

			wp_redirect( $order->get_checkout_order_received_url() );
		} catch (\Exception $e) {
			$logger = wc_get_logger();
			$logger->error(
				sprintf(
					'Error completing order with ' . $this->payment_method_BNPL_provider . '. %s',
					$e->getMessage()
				)
			);
			wc_add_notice( $e->getMessage() );
			wp_redirect( wc_get_checkout_url() );
		}
		exit();
	}

	/**
	 * Handle status Update URL.
	 */
	public function process_bnpl_status() {
		// @TODO
		$this->bnpl_debug('process_bnpl_status');

//		try {
//			//Utils::validate_bnpl_request();
//
//			$gateway_response = $this->gateway->get_transaction_details_by_txn_id( wc_clean( $_GET['id'] ) );
//			$order = $this->get_order( $gateway_response );
//
//			if ( TransactionStatus::PREAUTHORIZED !== $gateway_response->transactionStatus ) {
//				if ( TransactionStatus::DECLINED == $gateway_response->transactionStatus ) {
//					$note_text = sprintf(
//						'%1$s%2$s %3$s. Transaction ID: %4$s.',
//						get_woocommerce_currency_symbol( $order->get_currency() ),
//						$order->get_total(),
//						__( 'payment failed/declined', 'globalpayments-gateway-provider-for-woocommerce' ),
//						$order->get_transaction_id()
//					);
//					$order->add_order_note( $note_text );
//					$order->set_status( 'failed' );
//					$order->save();
//				}
//				// @TODO: display proper message for customer
//				throw new \Exception('Something went wrong with Affirm - transaction doesn\'t have the PREAUTHORIZED status');
//			}
//
//			$note_text = sprintf(
//				'%1$s%2$s %3$s. Transaction ID: %4$s.',
//				get_woocommerce_currency_symbol( $order->get_currency() ),
//				$order->get_total(),
//				__( 'authorized', 'globalpayments-gateway-provider-for-woocommerce' ),
//				$order->get_transaction_id()
//			);
//			$order->add_order_note( $note_text );
//			$order->set_status( 'processing' );
//			$order->save();
//
//			if ($this->payment_action == AbstractGateway::TXN_TYPE_SALE) {
//				AbstractGateway::capture_credit_card_authorization( $order );
//				$order->payment_complete();
//			}
//
//			//wp_redirect( $order->get_checkout_order_received_url() );
//		} catch (\Exception $e) {
//			$logger = wc_get_logger();
//			$logger->error(
//				sprintf(
//					'Error completing order with ' . $this->payment_method_BNPL_provider . '. %s',
//					$e->getMessage()
//				)
//			);
//		}
		exit();
	}

	/**
	 * Handle customer cancel URL.
	 */
	public function process_bnpl_cancel() {
		try {
			Utils::validate_bnpl_request();

			$gateway_response = $this->gateway->get_transaction_details_by_txn_id( wc_clean( $_GET['id'] ) );
			$order = $this->get_order( $gateway_response );

			$note_text = sprintf(
				'%1$s%2$s %3$s. Transaction ID: %4$s.',
				get_woocommerce_currency_symbol( $order->get_currency() ),
				$order->get_total(),
				__( 'payment canceled by customer', 'globalpayments-gateway-provider-for-woocommerce' ),
				$order->get_transaction_id()
			);
			$order->add_order_note( $note_text );
			$order->set_status( 'cancelled' );
			$order->save();

			wp_redirect( wc_get_checkout_url() );
		} catch (\Exception $e) {
			$logger = wc_get_logger();
			$logger->error(
				sprintf(
					'Error completing order cancel with ' . $this->payment_method_BNPL_provider . '. %s',
					$e->getMessage()
				)
			);
			wp_redirect( wc_get_checkout_url() );
		}
		exit();
	}

	/**
	 * Get WooCommerce order associated with the order ID from Transaction Summary.
	 *
	 * @param TransactionSummary $gateway_response
	 *
	 * @return bool|WC_Order|\WC_Order_Refund
	 * @throws \Exception
	 */
	private function get_order( TransactionSummary $gateway_response ) {
		$order = wc_get_order( $gateway_response->orderId );
		if ( false === $order || ! ( $order instanceof WC_Order ) ) {
			throw new \Exception( __( 'Order ID: ' . $gateway_response->orderId . ' Order not found.', 'globalpayments-gateway-provider-for-woocommerce' ) );
		}
		if ( $this->id != $order->get_payment_method() ) {
			// @TODO: display proper message for customer
			throw new \Exception('Order ID: ' . $gateway_response->orderId . ' Something went wrong with Affirm - order has different payment code');
		}
		if ( $gateway_response->transactionId !== $order->get_transaction_id() ) {
			// @TODO: display proper message for customer
			throw new \Exception( __( 'Order ID: ' . $gateway_response->orderId . ' Invalid order transaction id.', 'globalpayments-gateway-provider-for-woocommerce' ) );
		}

		return $order;
	}

	private function bnpl_debug( $endpoint ) {
		$logger = wc_get_logger();
		$logger->error(
			sprintf(
				'Provider: %s: %s reached',
				$this->payment_method_BNPL_provider,
				$endpoint
			)
		);
		if ( 'application/json' === $_SERVER['CONTENT_TYPE'] ) {
			$body = file_get_contents( 'php://input' );
			if ( false === $body ) {
				$logger->error( 'failed to read php://input' );
			}
			$logger->error(
				sprintf(
					'body php://input: [%s]',
					print_r( $body, true )
				)
			);
			$decoded_body = json_decode( file_get_contents( 'php://input' ) );
			$logger->error(
				sprintf(
					'json decoded body php://input: [%s]',
					print_r( $decoded_body, true )
				)
			);
		}
	}
}
