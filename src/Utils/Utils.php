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
		$req_details = self::get_request_details();
		$xgp_signature = $req_details->headers['X_GP_SIGNATURE'];
		if ( empty( $xgp_signature ) ) {
			throw new \Exception( __( 'This request has invalid headers.', 'globalpayments-gateway-provider-for-woocommerce' ) );
		}

		self::verify_xgp_signature($xgp_signature);

		$content = $req_details->content;
		if ( empty( $content->id ) ) {
			throw new \Exception( __( 'Missing transaction id.', 'globalpayments-gateway-provider-for-woocommerce' ) );
		}

		return $content;
	}

	/**
	 * Get request headers and request content
	 *
	 * @return object
	 */
	private static function get_request_details() {
		$server = rest_get_server();
		$headers = $server->get_headers($_SERVER);

		$raw_content = $server::get_raw_data();

		if ( isset( $headers['CONTENT_ENCODING'] ) && false !== strpos( $headers['CONTENT_ENCODING'], 'gzip' ) ) {
			$raw_content = gzdecode($raw_content);
		}

		if ( isset( $headers['CONTENT_TYPE'] ) && 'application/json' === $_SERVER['CONTENT_TYPE'] ) {
			$raw_content = json_decode($raw_content);
		}

		return (object) array(
			'headers' => $headers,
			'content' => $raw_content
		);
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
