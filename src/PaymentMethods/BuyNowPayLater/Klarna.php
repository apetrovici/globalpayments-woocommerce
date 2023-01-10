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
		$this->method_description = __( 'Connect to Klarna via Unified Payments Gateway',
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
	public function get_method_availability() {
		return array(
			'AUD' => array( 'AU', 'NZ' ),
			'NZD' => array( 'AU', 'NZ' ),
			'EUR' => array( 'AT', 'BE', 'DK', 'FI', 'FR', 'DE', 'IT', 'NL', 'NO', 'PL', 'ES', 'SE', 'CH', 'GB' ),
			'DKK' => array( 'AT', 'BE', 'DK', 'FI', 'FR', 'DE', 'IT', 'NL', 'NO', 'PL', 'ES', 'SE', 'CH', 'GB' ),
			'NOK' => array( 'AT', 'BE', 'DK', 'FI', 'FR', 'DE', 'IT', 'NL', 'NO', 'PL', 'ES', 'SE', 'CH', 'GB' ),
			'PLN' => array( 'AT', 'BE', 'DK', 'FI', 'FR', 'DE', 'IT', 'NL', 'NO', 'PL', 'ES', 'SE', 'CH', 'GB' ),
			'SEK' => array( 'AT', 'BE', 'DK', 'FI', 'FR', 'DE', 'IT', 'NL', 'NO', 'PL', 'ES', 'SE', 'CH', 'GB' ),
			'CHF' => array( 'AT', 'BE', 'DK', 'FI', 'FR', 'DE', 'IT', 'NL', 'NO', 'PL', 'ES', 'SE', 'CH', 'GB' ),
			'GBP' => array( 'AT', 'BE', 'DK', 'FI', 'FR', 'DE', 'IT', 'NL', 'NO', 'PL', 'ES', 'SE', 'CH', 'GB' ),
			'CAD' => array( 'CA', 'US' ),
			'USD' => array( 'CA', 'US' ),
		);
	}
}
