<?php

namespace GlobalPayments\WooCommercePaymentGatewayProvider\Gateways;

use GlobalPayments\Api\Entities\Enums\Environment;
use GlobalPayments\Api\Entities\Enums\GatewayProvider;
use GlobalPayments\Api\Entities\Enums\TransactionStatus;
use GlobalPayments\WooCommercePaymentGatewayProvider\Plugin;
use GlobalPayments\WooCommercePaymentGatewayProvider\Gateways\Traits\MulticheckboxTrait;

defined( 'ABSPATH' ) || exit;

class ClickToPayGateway extends AbstractGateway {

	use MulticheckboxTrait;

	/**
	 * Gateway ID
	 *
	 * @var string
	 */
	const GATEWAY_ID = 'globalpayments_clicktopay';

	/**
	 * SDK gateway provider
	 *
	 * @var string
	 */
	public $gateway_provider = GatewayProvider::GP_API;

	public $js_lib_config;

	public function __construct() {
		parent::__construct();

		$this->gateway = new GpApiGateway( true );
	}

	public function get_first_line_support_email() {
		return 'api.integrations@globalpay.com';
	}

	public function configure_method_settings() {
		$this->id                 = self::GATEWAY_ID;
		$this->method_title       = __( 'GlobalPayments - Click To Pay', 'googlepay-gateway-provider-for-woocommerce' );
		$this->method_description = __( 'Connect to the Click To Pay gateway via Unified Payments Gateway', 'globalpayments-gateway-provider-for-woocommerce' );
	}

	public function get_frontend_gateway_options() {
		return array();
	}

	public function get_backend_gateway_options() {
		return $this->gateway->get_backend_gateway_options();
	}

	public function get_gateway_form_fields() {
		return array();
	}

	public function init_form_fields() {
		$this->form_fields = array(
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
				'default'           => __( 'Pay with Click To Pay Standalone', 'globalpayments-gateway-provider-for-woocommerce' ),
				'desc_tip'          => true,
				'custom_attributes' => array( 'required' => 'required' ),
			),
		);
	}

	public function tokenization_script() {

	}

	public function payment_fields() {
		if ( is_checkout() ) {
//			$this->tokenization_script();
			$this->form();
		}
	}

	/**
	 * The HTML template string for a secure payment field
	 *
	 * Format directives:
	 *
	 * 1) Gateway ID
	 * 2) Field CSS class
	 * 3) Field label
	 * 4) Field validation message
	 *
	 * @return string
	 */
	public function secure_payment_field_html_format() {
		return (
		'<div class="form-row form-row-wide globalpayments %1$s %2$s">
				<div id="%1$s-%2$s"></div>
			</div>'
		);
	}

	public function secure_payment_fields() {
		return array(
			'click-to-pay-field' => array(
				'class'       => 'click-to-pay',
				'label'       => '',
				'placeholder' => '',
				'messages'    => array(
					'validation' => '',
				),
			),
		);
	}

	public function mapResponseCodeToFriendlyMessage( $responseCode ) {
		if ( TransactionStatus::DECLINED === $responseCode ) {
			return __( 'Your payment was unsuccessful. Please try again or use a different payment method.', 'globalpayments-gateway-provider-for-woocommerce' );
		}

		return __( 'An error occurred while processing the card. Please try again or use a different payment method.', 'globalpayments-gateway-provider-for-woocommerce' );
	}
}
