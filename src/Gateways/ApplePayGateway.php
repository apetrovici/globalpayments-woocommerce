<?php

namespace GlobalPayments\WooCommercePaymentGatewayProvider\Gateways;

use GlobalPayments\WooCommercePaymentGatewayProvider\Plugin;
use GlobalPayments\Api\Entities\Enums\Environment;
use GlobalPayments\Api\Entities\Enums\GatewayProvider;
use GlobalPayments\Api\Entities\Enums\Channel;

defined( 'ABSPATH' ) || exit;

class ApplePayGateway extends AbstractGateway {

	/**
	 * Gateway ID
	*/
	const GATEWAY_ID = 'globalpayments_applepay';

	/**
	 * SDK gateway provider
	 *
	 * @var string
	 */
	public $gateway_provider = GatewayProvider::GP_API;

	/**
	 * @inheritdoc
	 */
	public $is_digital_wallet = true;

	/**
	 * Accepted Cards
	 *
	 * @var array
	 */
	public $accepted_cards;

	/**
	 * Payment Action
	 *
	 * @var string
	*/
	public $payment_action;

	/**
	 * Apple Merchant Id
	 *
	 * @var string
	*/
	public $apple_merchant_id;

	/**
	 * Apple Merchant Cert Path
	 *
	 * @var string
	*/
	public $apple_merchant_cert_path;

	/**
	 * Apple Merchant Key Path
	 *
	 * @var string
	*/
	public $apple_merchant_key_path;

	/**
	 * Apple Merchant Key PassPhrase
	 *
	 * @var string
	*/
	public $apple_merchant_key_passphrase;

	/**
	 * Apple Merchant Domain
	 *
	 * @var string
	*/
	public $apple_merchant_domain;

	/**
	 * Apple Merchant Display Name
	 *
	 * @var string
	*/
	public $apple_merchant_display_name;

	public function configure_method_settings () {
		$this->id					=  self::GATEWAY_ID;
		$this->method_title			= __( 'GlobalPayments - Apple Pay', 'globalpayments-gateway-provider-for-woocommerce' );
		$this->method_description	= __( 'Connect to the Apple Pay gateway via UCP', 'globalpayments-gateway-provider-for-woocommerce' );
	}

	public function get_frontend_gateway_options () : array {
		return array(
			'accepted_cards'				=> $this->accepted_cards,
			'apple_merchant_display_name'	=> $this->apple_merchant_display_name,
			'currency'						=> get_woocommerce_currency(),
			'grand_total_amount'			=> ( string ) $this->get_session_amount(),
			'country_code'					=> wc_get_base_location()['country'],
			'validate_merchant_url'			=> WC()->api_request_url( 'globalpayments_validate_merchant' ),
			'googlepay_gateway_id'			=> GooglePayGateway::GATEWAY_ID
		);
	}

	public function get_backend_gateway_options () : array {
		$gpApiGateway = new GpApiGateway();
		return $gpApiGateway->get_backend_gateway_options();
	}

    public function get_gateway_form_fields () : array {
        return array(
			'apple_merchant_id' => array(
				'title'				=> __( 'Apple Merchant ID', 'globalpayments-gateway-provider-for-woocommerce' ),
				'type'				=> 'text',
				'default'			=> '',
				'custom_attributes'	=> array( 'required' => 'required' ),
			),
			'apple_merchant_cert_path' => array(
				'title'				=> __( 'Apple Merchant Cert Path', 'globalpayments-gateway-provider-for-woocommerce' ),
				'type'				=> 'text',
				'default'			=> '',
				'custom_attributes'	=> array( 'required' => 'required' ),
			),
			'apple_merchant_key_path' => array(
				'title'				=> __( 'Apple Merchant Key Path', 'globalpayments-gateway-provider-for-woocommerce' ),
				'type'				=> 'text',
				'default'			=> '',
				'custom_attributes'	=> array( 'required' => 'required' ),
			),
			'apple_merchant_key_passphrase' => array(
				'title'			=> __( 'Apple Merchant Key Passphrase', 'globalpayments-gateway-provider-for-woocommerce' ),
				'type'			=> 'password',
				'default'		=> '',
			),
			'apple_merchant_domain' => array(
				'title'			=> __( 'Apple Merchant Domain', 'globalpayments-gateway-provider-for-woocommerce' ),
				'type'			=> 'text',
				'default'		=> '',
				'custom_attributes'	=> array( 'required' => 'required' ),
			),
			'apple_merchant_display_name' => array(
				'title'			=> __( 'Apple Merchant Display Name', 'globalpayments-gateway-provider-for-woocommerce' ),
				'type'			=> 'text',
				'default'		=> '',
				'custom_attributes'	=> array( 'required' => 'required' ),
			),
			'accepted_cards'	=> array(
				'title'			=> __( 'Accepted Cards', 'globalpayments-gateway-provider-for-woocommerce' ),
				'type'			=> 'multiselect',
				'class'			=> 'accepted_cards',
				'css'			=> 'width: 450px',
				'options'		=> array (
                    'VISA'			=> 'Visa',
                    'MASTERCARD'	=> 'MasterCard',
                    'AMEX'			=> 'AMEX',
                    'DISCOVER'		=> 'Discover',
				),
				'default'		=> array( 'VISA' ),
				'custom_attributes'	=> array( 'required' => 'required' ),
			)
		);
	}

	public function init_form_fields() {
		$this->form_fields = array_merge(
			array(
				'enabled' => array(
					'title'			=> __( 'Enable/Disable', 'globalpayments-gateway-provider-for-woocommerce' ),
					'type'			=> 'checkbox',
					'label'			=> __( 'Enable Gateway', 'globalpayments-gateway-provider-for-woocommerce' ),
					'default'		=> 'no',
				),
				'title'	=> array(
					'title'			=> __( 'Title', 'globalpayments-gateway-provider-for-woocommerce' ),
					'type'			=> 'text',
					'description'	=> __( 'This controls the title which the user sees during checkout.', 'globalpayments-gateway-provider-for-woocommerce' ),
					'default'		=> __( 'Apple Pay', 'globalpayments-gateway-provider-for-woocommerce' ),
					'desc_tip'		=> true,
				),
			),
			$this->get_gateway_form_fields(),
			array(
				'payment_action'	=> array(
					'title'			=> __( 'Payment Action', 'globalpayments-gateway-provider-for-woocommerce' ),
					'type'			=> 'select',
					'description'	=> __( 'Choose whether you wish to capture funds immediately or authorize payment only for a delayed capture.', 'globalpayments-gateway-provider-for-woocommerce' ),
					'default'		=> self::TXN_TYPE_SALE,
					'desc_tip'		=> true,
					'options'		=> array(
						self::TXN_TYPE_SALE			=> __( 'Charge', 'globalpayments-gateway-provider-for-woocommerce' ),
						self::TXN_TYPE_AUTHORIZE	=> __( 'Authorize only', 'globalpayments-gateway-provider-for-woocommerce' ),
					),
				),
			)
		);
	}



	public function payment_fields() {
		echo '<div>' . __( 'Pay with Apple Pay', 'globalpayments-gateway-provider-for-woocommerce' ) . '</div>';
	}

	protected function get_session_amount() : float {
		$cart_totals = WC()->session->get( 'cart_totals' );

		return round( $cart_totals['total'], 2 );
	}

	public function get_first_line_support_email () : string {
		return 'api.integrations@globalpay.com';
	}

	public function validate_merchant () {
		$validationUrl = wc_clean( $_POST['validationUrl'] );
		$activeGateway = new ApplePayGateway();
		if (
			! $this->apple_merchant_id ||
			! $this->apple_merchant_cert_path ||
			! $this->apple_merchant_key_path ||
			! $this->apple_merchant_domain ||
			! $this->apple_merchant_display_name
		) {
			return null;
		}
		$pemCrtPath =  ABSPATH  . '/' . $this->apple_merchant_cert_path;
		$pemKeyPath = ABSPATH  . '/' . $this->apple_merchant_key_path;

		$validationPayload 	= array();
		$validationPayload['merchantIdentifier']	= $this->apple_merchant_id;
		$validationPayload['displayName']			= $this->apple_merchant_display_name;
		$validationPayload['initiative'] 			= 'web';
		$validationPayload['initiativeContext'] 	= $this->apple_merchant_domain;

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $validationUrl );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Content-Type: application/json' ) );
		curl_setopt( $ch, CURLOPT_POST, 1 );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $validationPayload ) );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 300 );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
		curl_setopt( $ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2 );
		curl_setopt( $ch, CURLOPT_DNS_USE_GLOBAL_CACHE, false );
		curl_setopt( $ch, CURLOPT_SSLCERT, $pemCrtPath );
		curl_setopt( $ch, CURLOPT_SSLKEY, $pemKeyPath );

		if ( null !== $this->apple_merchant_key_passphrase ) {
			curl_setopt( $ch, CURLOPT_KEYPASSWD, $this->apple_merchant_key_passphrase );
		}

		$validationResponse = curl_exec( $ch );

		if ( false === $validationResponse ) {
			wp_send_json( [
				'error'    => true,
				'message'  => curl_error( $ch ),
			] );
		}

		curl_close( $ch );

		wp_send_json( [
			'error'    => false,
			'message'  => $validationResponse,
		] );
	}

	protected function add_hooks() {
		parent::add_hooks();
		add_action( 'woocommerce_api_globalpayments_validate_merchant', array( $this, 'validate_merchant' ) );
	}

	public function mapResponseCodeToFriendlyMessage( $responseCode ) {
		if ( 'DECLINED' === $responseCode ) {
			return __( 'Your card has been declined by the bank.', 'globalpayments-gateway-provider-for-woocommerce' );
		}

		return __( 'An error occurred while processing the card.', 'globalpayments-gateway-provider-for-woocommerce' );
	}

	/**
	 * Enqueues tokenization scripts from Global Payments and WooCommerce
	 *
	 * @return
	 */
	public function tokenization_script() {
		wp_enqueue_script(
			'globalpayments-helper',
			Plugin::get_url( '/assets/frontend/js/globalpayments-helper.js' ),
			array( 'jquery' ),
			WC()->version,
			true
		);

		wp_enqueue_script(
			'globalpayments-applepay',
			Plugin::get_url( '/assets/frontend/js/applepay.js' ),
			array( 'wc-checkout', 'globalpayments-helper' ),
			WC()->version,
			true
		);

		wp_localize_script(
			'globalpayments-applepay',
			'globalpayments_applepay_params',
			array(
				'id'				=> $this->id,
				'gateway_options'	=> $this->get_frontend_gateway_options(),
			)
		);

		wp_enqueue_style(
			'globalpayments-applepay',
			Plugin::get_url( '/assets/frontend/css/globalpayments-applepay.css' ),
			array(),
			WC()->version
		);
	}
}
