<?php

namespace GlobalPayments\WooCommercePaymentGatewayProvider\Gateways\Requests;

use GlobalPayments\WooCommercePaymentGatewayProvider\Data\PaymentTokenData;
use GlobalPayments\WooCommercePaymentGatewayProvider\Gateways\AbstractGateway;
use GlobalPayments\Api\Entities\Enums\EncyptedMobileType;
use GlobalPayments\Api\Entities\Enums\TransactionModifier;
use GlobalPayments\WooCommercePaymentGatewayProvider\Gateways\Helper\Helper;
use Requests;

defined( 'ABSPATH' ) || exit;

class AuthorizationRequest extends AbstractRequest {
	public function get_transaction_type() {
		return AbstractGateway::TXN_TYPE_AUTHORIZE;
	}

	public function get_args() {
		$token = ( new PaymentTokenData( $this ) )->get_token();
		$replacedTokenResponse = Helper::removeSlashesFromToken($this->data[$this->gateway_id]["digital_wallet_token_response"] );
		return array(
			RequestArg::AMOUNT          => null !== $this->order ? $this->order->get_total() : null,
			RequestArg::CURRENCY        => null !== $this->order ? $this->order->get_currency() : null,
			RequestArg::CARD_DATA       => $token,
			RequestArg::SERVER_TRANS_ID => $this->data[ $this->gateway_id ]['serverTransId'] ?? null,
			RequestArg::PARES           => ! empty( $this->data[ $this->gateway_id ]['PaRes'] ) ? $this->data[ $this->gateway_id ]['PaRes'] : null,
			RequestArg::DIGITAL_WALLET_TOKEN => ! empty( $this->data[$this->gateway_id]['digital_wallet_token_response'] ) ? $replacedTokenResponse  : null,
			RequestArg::MOBILE_TYPE     => EncyptedMobileType::GOOGLE_PAY,
			RequestArg::TRANSACTION_MODIFIER => TransactionModifier::ENCRYPTED_MOBILE,
		);
	}
}
