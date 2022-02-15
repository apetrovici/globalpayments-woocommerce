<?php

namespace GlobalPayments\WooCommercePaymentGatewayProvider\Gateways\Helper;

defined( 'ABSPATH' ) || exit;

class Helper {

	public static function remove_slashes_from_token( string $token ): string {
		$replace = str_replace( '\\"', '"', $token ) ;
		$replace = str_replace( '\\"', '"',$replace ) ;
		$replace = str_replace( '\\\\\\\\', '\\',$replace ) ;

		return $replace;
	}
}