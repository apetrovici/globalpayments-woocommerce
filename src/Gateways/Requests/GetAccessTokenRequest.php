<?php

namespace GlobalPayments\WooCommercePaymentGatewayProvider\Gateways\Requests;

use GlobalPayments\WooCommercePaymentGatewayProvider\Gateways\AbstractGateway;

defined( 'ABSPATH' ) || exit;

class GetAccessTokenRequest extends AbstractRequest {
	public function get_transaction_type() {
		return AbstractGateway::TXN_TYPE_GET_ACCESS_TOKEN;
	}

	public function get_args() {
		return array();

		/**
		 * @TODO: add `PMT_POST_Create_Single` for App Id: sz3hGj7KFsWb6A4JeP9jgIG8GgHXL2gT
		 * BE Requests are performed on QA env
		 */
		return array(
			RequestArg::PERMISSIONS => array(
				'PMT_POST_Create_Single',
			)
		);
	}
}
