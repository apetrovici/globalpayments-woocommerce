<?php

namespace GlobalPayments\WooCommercePaymentGatewayProvider\Gateways\Helper;

defined( 'ABSPATH' ) || exit;

class Helper {

    public static function removeSlashesFromToken( string $token ): string {
        $replace = str_replace('\\"', '"', $token ) ;
		$replace = str_replace('\\"', '"',$replace ) ;
		$replace = str_replace('\\\\\\\\', '\\',$replace ) ;

        return $replace;
    }
}