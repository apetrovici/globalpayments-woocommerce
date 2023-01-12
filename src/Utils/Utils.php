<?php

namespace GlobalPayments\WooCommercePaymentGatewayProvider\Utils;

class Utils {
	/**
	 * Validate BNPL notification request.
	 *
	 * @return array|bool
	 * @throws \Exception
	 */
	public static function validate_bnpl_request() {
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) ) {
			throw new \Exception( __( 'The request method is missing.', 'globalpayments-gateway-provider-for-woocommerce' ) );
		}

		switch ( $_SERVER['REQUEST_METHOD'] ) {
			case 'GET':
				return self::validate_bnpl_get_request();
			case 'POST':
				return self::validate_bnpl_post_request();
			default:
				throw new \Exception( __( 'This request method is not supported.', 'globalpayments-gateway-provider-for-woocommerce' ) );
		}
	}

	/**
	 * Validate BNPL get request.
	 *
	 * @throws \Exception
	 */
	private static function validate_bnpl_get_request(): bool {
		self::verify_xgp_signature($_GET['X-GP-Signature']);

		if ( empty( $_GET['id'] ) ) {
			throw new \Exception( __( 'Missing transaction id.', 'globalpayments-gateway-provider-for-woocommerce' ) );
		}

		return true;
	}

	/**
	 * Validate BNPL post request. If True, return request body.
	 *
	 * @return array
	 * @throws \Exception
	 */
	private static function validate_bnpl_post_request() {
		$headers = getallheaders();
		if (false === $headers) {
			throw new \Exception( __( 'This request has invalid headers.', 'globalpayments-gateway-provider-for-woocommerce' ) );
		}

		self::verify_xgp_signature($headers['X-Gp-Signature']);

		$body = json_decode(file_get_contents('php://input'));
		if ( empty( $body->id ) ) {
			throw new \Exception( __( 'Missing transaction id.', 'globalpayments-gateway-provider-for-woocommerce' ) );
		}

		return $body;
	}

	/**
	 * @param $xgp_signature
	 *
	 * @return bool
	 * @throws \Exception
	 */
	private static function verify_xgp_signature($xgp_signature): bool {
		if ( empty( $xgp_signature ) ) {
			throw new \Exception( __( 'Unknown signature.', 'globalpayments-gateway-provider-for-woocommerce' ) );
		}

		// @TODO: validate X-GP-Signature
		return true;
	}

	/**
	 * Converts all accent characters to ASCII characters and removes non-supported chars.
	 *
	 * @param $string
	 *
	 * @return mixed
	 */
	public static function sanitize_string( $string ) {
		$string = remove_accents( $string );

		return preg_replace( "/[^a-zA-Z-_.]/", "", $string );
	}
}
