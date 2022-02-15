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
	 * @var array
	 */
	public $accepted_cards;

	/**
	 * @var string
	*/
	public $payment_action;
	
	/**
	 * @var string
	*/
	public $apple_merchant_id;

	/**
	 * @var string
	*/
	public $apple_merchant_cert_path;

	/**
	 * @var string
	*/
	public $apple_merchant_key_path;

	/**
	 * @var string
	*/
	public $apple_merchant_key_passphrase;

	/**
	 * @var string
	*/
	public $apple_merchant_domain;

	/**
	 * @var string
	*/
	public $apple_merchant_display_name;
	

	public function configure_method_settings () {
		$this->id					=  self::GATEWAY_ID;
		$this->method_title			= __( 'ApplePay', 'applepay-gateway-provider-for-woocommerce' );
		$this->method_description	= __( 'Connect to the Apple Pay gateway', 'globalpayments-gateway-provider-for-woocommerce' );
	}

	public function get_frontend_gateway_options () : array {
		return array(
			'publicApiKey' => $this->get_credential_setting( 'public_key' ),
		);
	}

	public function get_backend_gateway_options () : array {
		global $wp_version;
		$gpApiGateway = new GpApiGateway();

		return array(
			'appId'						=> $gpApiGateway->get_credential_setting( 'app_id' ),
			'appKey'					=> $gpApiGateway->get_credential_setting( 'app_key' ),
			'channel'					=> Channel::CardNotPresent,
			'country'					=> wc_get_base_location()['country'],
			'developerId'				=> '',
			'environment'				=> $gpApiGateway->is_production ? Environment::PRODUCTION : Environment::TEST,
			'methodNotificationUrl'		=> WC()->api_request_url( 'globalpayments_threedsecure_methodnotification' ),
			'challengeNotificationUrl'	=> WC()->api_request_url( 'globalpayments_threedsecure_challengenotification' ),
			'merchantContactUrl'		=> $gpApiGateway->merchant_contact_url,
			'dynamicHeaders'			=> [
				'x-gp-platform'		=> 'wordpress;version=' . $wp_version . ';woocommerce;version=' . WC()->version,
			'x-gp-extension'		=> 'globalpayments-woocommerce;version=' . Plugin::VERSION,
			],
			'debug'						=> $gpApiGateway->debug,
		);
	}

    public function get_gateway_form_fields () : array {
        return array(
			'apple_merchant_id' => array(
				'title'			=> __( 'Apple Merchant ID', 'apple_merchant_id' ),
				'type'			=> 'text',
				'default'		=> '',
			),
			'apple_merchant_cert_path' => array(
				'title'			=> __( 'Apple Merchant Cert Path', 'apple_merchant_cert_path' ),
				'type'			=> 'text',
				'default'		=> '',
			),
			'apple_merchant_key_path' => array(
				'title'			=> __( 'Apple Merchant Key Path', 'apple_merchant_key_path' ),
				'type'			=> 'text',
				'default'		=> '',
			),
			'apple_merchant_key_passphrase' => array(
				'title'			=> __( 'Apple Merchant Key Passphrase', 'apple_merchant_key_passphrase' ),
				'type'			=> 'password',
				'default'		=> '',
			),
			'apple_merchant_domain' => array(
				'title'			=> __( 'Apple Merchant Domain', 'apple_merchant_domain' ),
				'type'			=> 'text',
				'default'		=> '',
			),
			'apple_merchant_display_name' => array(
				'title'			=> __( 'Apple Merchant Display Name', 'apple_merchant_display_name' ),
				'type'			=> 'text',
				'default'		=> '',
			),
			'accepted_cards'	=> array(
				'title'			=> __( 'Accepted Cards', 'accepted_cards' ),
				'type'			=> 'multiselect',
				'class'			=> 'accepted_cards',
				'css'			=> 'width: 450px',
				'description'	=> __( 'Choose for which AVS result codes, the transaction must be auto reversed.'),
				'options'		=> $this->acceptedCardsOptions(),
				'default'		=> array('VISA'),
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
				'title'	=> array(
					'title'			=> __( 'Title', 'globalpayments-gateway-provider-for-woocommerce' ),
					'type'			=> 'text',
					'description'	=> __( 'This controls the title which the user sees during checkout.', 'globalpayments-gateway-provider-for-woocommerce' ),
					'default'		=> __( 'Apple Pay', 'globalpayments-gateway-provider-for-woocommerce' ),
					'desc_tip'		=> true,
				),
		), $this->get_gateway_form_fields() );
	}

	public function acceptedCardsOptions() : array {

		return array (
			'VISA'			=> 'Visa',
			'MASTERCARD'	=> 'MasterCard',
			'AMEX'			=> 'AMEX',
			'DISCOVER'		=>  'Discover',
		);
	}

	public function payment_fields() {
		echo '<div> Pay with Apple </div>';

		wp_enqueue_script(
			'applepay',
			Plugin::get_url( '/assets/frontend/js/applepay.js' ),
			array(),
			WC()->version,
			true
		);

		wp_localize_script(
			'applepay',
			'globalpayments_apple_pay_params',
			array(
				'id'				=> $this->id,
				'gateway_options'	=> $this->secure_payment_fields_config(),
			)
		);

		wp_enqueue_style(
			'globalpayments-applepay',
			Plugin::get_url( '/assets/frontend/css/globalpayments-applepay.css' ),
			array(),
			WC()->version
		);

	}

	protected function get_session_amount() : float {
		$cart_totals = WC()->session->get('cart_totals');

		return round($cart_totals['total'], 2);
	}

	public function secure_payment_fields_config() : array {
		return array(
			'id' 					=>$this->id,
			'accepted_cards' 		=> $this->accepted_cards,
			'apple_merchant_display_name' => $this->apple_merchant_display_name,
			'currency'				=> get_woocommerce_currency(),
			'grandTotalAmount'		=> (string)$this->get_session_amount(),
			'merchantDisplayName'	=> $this->apple_merchant_display_name,
			'countryCode'			=> wc_get_base_location()['country'],
			'validateMerchantUrl'	=> WC()->api_request_url('globalpayments_validate_merchant'),
		);
	}

	public function get_first_line_support_email () : string {
		return 'TBD';
	}

	public function validate_merchant () {

		$validationUrl = $_POST['validationUrl'];
		$activeGateway = new ApplePayGateway();
		if (
			!$this->apple_merchant_id ||
			!$this->apple_merchant_cert_path ||
			!$this->apple_merchant_key_path ||
			!$this->apple_merchant_domain ||
			!$this->apple_merchant_display_name
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
		curl_setopt($ch, CURLOPT_URL, $validationUrl);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($validationPayload));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 300);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
		curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, false );
		curl_setopt($ch, CURLOPT_SSLCERT, $pemCrtPath);
		curl_setopt($ch, CURLOPT_SSLKEY, $pemKeyPath);

		if ($this->apple_merchant_key_passphrase !== null) {
			curl_setopt($ch, CURLOPT_KEYPASSWD, $this->apple_merchant_key_passphrase);
		}

		$validationResponse = curl_exec($ch);

		if (false == $validationResponse) {
			wp_send_json( [
				'error'    => true,
				'message'  => curl_error($ch),
			] );
		}

		curl_close($ch);

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
}