<?php

namespace GlobalPayments\WooCommercePaymentGatewayProvider\Utils;

class Utils {
	/**
	 * Get request headers and request content
	 *
	 * @return object
	 */
	public static function get_request() {
		$server = rest_get_server();
		$headers = $server->get_headers( wp_unslash( $_SERVER ) );

		$raw_content = $server::get_raw_data();

		if ( isset( $headers['CONTENT_ENCODING'] ) && false !== strpos( $headers['CONTENT_ENCODING'], 'gzip' ) ) {
			$raw_content = gzdecode( $raw_content );
		}

//		if ( isset( $headers['CONTENT_TYPE'] ) && 'application/json' === $_SERVER['CONTENT_TYPE'] ) {
//			$raw_content = json_decode( $raw_content );
//		}

		$request = new \WP_REST_Request( $_SERVER['REQUEST_METHOD'] );
		$request->set_query_params( wp_unslash( $_GET ) );
		$request->set_body_params( wp_unslash( $_POST ) );
		$request->set_headers( $headers );
		$request->set_body( $raw_content );

		return $request;
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
