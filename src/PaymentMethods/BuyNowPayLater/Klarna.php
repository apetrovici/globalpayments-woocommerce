<?php

namespace GlobalPayments\WooCommercePaymentGatewayProvider\PaymentMethods\BuyNowPayLater;

use GlobalPayments\Api\Entities\Enums\BNPLType;

defined( 'ABSPATH' ) || exit;

class Klarna extends AbstractBuyNowPayLater {
	public const PAYMENT_METHOD_ID = 'globalpayments_klarna';

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	public $payment_method_BNPL_provider = BNPLType::KLARNA;

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	public $default_title = 'Pay with Klarna';

	/**
	 * @inheritDoc
	 */
	public function configure_method_settings() {
		$this->id                 = self::PAYMENT_METHOD_ID;
		$this->method_title       = __( 'GlobalPayments - Klarna', 'globalpayments-gateway-provider-for-woocommerce' );
		$this->method_description = __( 'Connect to Klarna via Unified Payments Gateway', 'globalpayments-gateway-provider-for-woocommerce' );
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
		return array(
			'AU',
			'NZ',
			'AT',
			'BE',
			'DK',
			'FI',
			'FR',
			'DE',
			'IT',
			'NL',
			'NO',
			'PL',
			'ES',
			'SE',
			'CH',
			'GB',
			'US',
			'CA'
		);
	}

	/**
	 * @inheritDoc
	 */
	public function get_available_currencies() {
		return array( 'AUD', 'NZD', 'EUR', 'DKK', 'NOK', 'PLN', 'SEK', 'CHF', 'GBP', 'USD', 'CAD' );
	}
}
