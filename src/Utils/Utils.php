<?php

namespace GlobalPayments\WooCommercePaymentGatewayProvider\Utils;

class Utils {
	/**
	 * Validate BNPL notification request.
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public static function validate_bnpl_request() {
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) ) {
			throw new \Exception( __( 'The request method is missing.', 'globalpayments-gateway-provider-for-woocommerce' ) );
		}

		if ( 'GET' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) ) {
			throw new \Exception( __( 'This request method is not supported.', 'globalpayments-gateway-provider-for-woocommerce' ) );
		}

		if ( ! self::verify_xgp_signature() ) {
			throw new \Exception( __( 'Unknown signature.', 'globalpayments-gateway-provider-for-woocommerce' ) );
		}

		if ( empty( $_GET['id'] ) ) {
			throw new \Exception( __( 'Missing transaction id.', 'globalpayments-gateway-provider-for-woocommerce' ) );
		}

		return true;
	}

	public static function verify_xgp_signature() {
		if ( empty( $_GET['X-GP-Signature'] ) ) {
			return false;
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
