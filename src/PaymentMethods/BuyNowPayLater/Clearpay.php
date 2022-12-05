<?php

namespace GlobalPayments\WooCommercePaymentGatewayProvider\PaymentMethods\BuyNowPayLater;

use GlobalPayments\Api\Entities\Enums\BNPLType;

defined( 'ABSPATH' ) || exit;

class Clearpay extends AbstractBuyNowPayLater {
	public const PAYMENT_METHOD_ID = 'globalpayments_clearpay';

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	public $payment_method_BNPL_provider = BNPLType::CLEARPAY;

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	public $default_title = 'Pay with Clearpay';

	/**
	 * @inheritDoc
	 */
	public function configure_method_settings() {
		$this->id                 = self::PAYMENT_METHOD_ID;
		$this->method_title       = __( 'GlobalPayments - Clearpay',
			'globalpayments-gateway-provider-for-woocommerce' );
		$this->method_description = __( 'Connect to Clearpay via Unified Payments Gateway',
			'globalpayments-gateway-provider-for-woocommerce' );
	}

	/**
	 * @inheritDoc
	 */
	public function get_gateway_form_fields() {
		return array();
	}

	/**
	 * @inheritDoc
	 */
	public function get_available_countries() {
		return array( 'GB', 'AU', 'NZ', 'CA', 'US' );
	}

	/**
	 * @inheritDoc
	 */
	public function get_available_currencies() {
		return array( 'GBP', 'AUD', 'NZD', 'CAD', 'USD' );
	}
}
