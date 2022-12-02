<?php

namespace GlobalPayments\WooCommercePaymentGatewayProvider\Gateways\Requests\BuyNowPayLater;

use GlobalPayments\Api\Entities\Address;
use GlobalPayments\Api\Entities\Customer;
use GlobalPayments\Api\Entities\Enums\AddressType;
use GlobalPayments\Api\Entities\Enums\BNPLShippingMethod;
use GlobalPayments\Api\Entities\Enums\PhoneNumberType;
use GlobalPayments\Api\Entities\PhoneNumber;
use GlobalPayments\Api\Entities\Product;
use GlobalPayments\Api\PaymentMethods\BNPL;
use GlobalPayments\Api\Utils\CountryUtils;
use GlobalPayments\WooCommercePaymentGatewayProvider\Gateways\AbstractGateway;
use GlobalPayments\WooCommercePaymentGatewayProvider\Gateways\Requests\AbstractRequest;
use GlobalPayments\WooCommercePaymentGatewayProvider\Utils\Utils;

defined( 'ABSPATH' ) || exit;

class AuthorizationRequest extends AbstractRequest {
	public function get_transaction_type() {
		return AbstractGateway::TXN_TYPE_BNPL_AUTHORIZE;
	}

	public function do_request() {
		$requestData = $this->data;
		$paymentMethod = new BNPL( $requestData['globalpayments_bnpl']['provider'] );
		$paymentMethod->returnUrl       = $requestData['globalpayments_bnpl']['returnUrl'];
		$paymentMethod->statusUpdateUrl = $requestData['globalpayments_bnpl']['statusUrl'];
		$paymentMethod->cancelUrl       = $requestData['globalpayments_bnpl']['cancelUrl'];

		$shipping_method = $this->get_shipping_method();
		$billing_address = $this->get_billing_address();
		if ( BNPLShippingMethod::EMAIL == $shipping_method ) {
			$shipping_address = $billing_address;
		} else {
			$shipping_address = $this->get_shipping_address();
		}

		return $paymentMethod->authorize( $this->order->get_total() )
		                     ->withCurrency( $this->order->get_currency() )
		                     ->withOrderId( (string) $this->order->get_id() )
		                     ->withProductData( $this->get_products_data() )
		                     ->withAddress( $shipping_address, AddressType::SHIPPING )
		                     ->withAddress( $billing_address, AddressType::BILLING )
		                     ->withCustomerData( $this->get_customer_data() )
		                     ->withBNPLShippingMethod( $shipping_method )
		                     ->execute();
	}

	public function get_args() {
		return array();
	}

	private function get_products_data() {
		$products_data = array();
		$order_items   = $this->order->get_items();

		foreach ( $order_items as $item_id => $item ) {
			$order_product = $item->get_product();
			$image         = wp_get_attachment_image_url( $order_product->get_image_id() );
			$product = new Product();
			$product->productId      = $item->get_product_id();
			$product->productName    = $order_product->get_title();
			$product->description    = $order_product->get_short_description();
			$product->quantity       = $item->get_quantity();
//			$product->unitPrice      = wc_format_decimal( $this->order->get_item_total( $item, true ), 2 );
			$product->netUnitPrice   = wc_format_decimal( $this->order->get_item_total( $item, true ), 2 );
			$product->taxAmount      = wc_format_decimal( $this->order->get_item_tax( $item ), 2 );
//			$product->discountAmount = 0;
//			$product->taxPercentage  = 0;
			$product->url            = $order_product->get_permalink();
			$product->imageUrl       = ! empty( $image ) ? $image : wc_placeholder_img_src();

			$products_data[] = $product;
		}

		return $products_data;
	}

	private function get_billing_address() {
		$billingAddress = new Address();
		$billingAddress->streetAddress1 = $this->order->get_billing_address_1();
		$billingAddress->streetAddress2 = $this->order->get_billing_address_2();
		$billingAddress->city           = $this->order->get_billing_city();
		$billingAddress->state          = $this->order->get_billing_state();
		$billingAddress->postalCode     = $this->order->get_billing_postcode();
		$billingAddress->country        = $this->order->get_billing_country();

		return $billingAddress;
	}

	private function get_shipping_address() {
		$shippingAddress = new Address();
		$shippingAddress->streetAddress1 = $this->order->get_shipping_address_1();
		$shippingAddress->streetAddress2 = $this->order->get_shipping_address_2();
		$shippingAddress->city           = $this->order->get_shipping_city();
		$shippingAddress->state          = $this->order->get_shipping_state();
		$shippingAddress->postalCode     = $this->order->get_shipping_postcode();
		$shippingAddress->country        = $this->order->get_shipping_country();

		return $shippingAddress;
	}

	private function get_customer_data() {
		$customer = new Customer();
		$customer->id        = $this->order->get_customer_id();
		$customer->firstName = Utils::sanitize_string( $this->order->get_billing_first_name() );
		$customer->lastName  = Utils::sanitize_string( $this->order->get_billing_last_name() );
		$customer->email     = $this->order->get_billing_email();
		$phone_code = CountryUtils::getPhoneCodesByCountry( $this->order->get_billing_country() );
		$customer->phone     = new PhoneNumber( $phone_code[0], $this->order->get_billing_phone(), PhoneNumberType::HOME );

		return $customer;
	}

	private function get_shipping_method() {
		$order_items   = $this->order->get_items();

		$needs_shipping    = false;
		$has_virtual_items = false;
		foreach ( $order_items as $item_id => $item ) {
			$order_product = $item->get_product();
			if ( $order_product->needs_shipping() ) {
				$needs_shipping = true;
			} else {
				$has_virtual_items = true;
			}
		}
		if ( $needs_shipping && $has_virtual_items ) {
			return BNPLShippingMethod::COLLECTION;
		}
		if ( $needs_shipping ) {
			return BNPLShippingMethod::DELIVERY;
		}
		if ( $has_virtual_items ) {
			return BNPLShippingMethod::EMAIL;
		}
	}
}
