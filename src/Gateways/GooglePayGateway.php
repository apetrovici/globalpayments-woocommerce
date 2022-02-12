<?php

namespace GlobalPayments\WooCommercePaymentGatewayProvider\Gateways;


use GlobalPayments\Api\Entities\Enums\Environment;
use GlobalPayments\Api\Entities\Enums\GatewayProvider;
use GlobalPayments\Api\Entities\Enums\Channel;
use GlobalPayments\WooCommercePaymentGatewayProvider\Plugin;



defined( 'ABSPATH' ) || exit;

class GooglePayGateway extends AbstractGateway {
	/**
	 * Gateway ID
	 */
	const GATEWAY_ID = 'globalpayments_googlepay';

	/**
	 * SDK gateway provider
	 *
	 * @var string
	 */
	public $gateway_provider = GatewayProvider::GP_API;

	/**
	 * Live Merchant location public API key
	 *
	 * Used for single-use tokenization on frontend
	 *
	 * @var string
	 */
	public $public_key;

	/**
	 * Live Merchant location secret API key
	 *
	 * Used for gateway transactions on backend
	 *
	 * @var string
	 */
	public $secret_key;

	/**
	 * Sandbox Merchant location public API key
	 *
	 * Used for single-use tokenization on frontend
	 *
	 * @var string
	 */
	public $sandbox_public_key;

	/**
	 * Sandbox Merchant location secret API key
	 *
	 * Used for gateway transactions on backend
	 *
	 * @var string
	 */
	public $sandbox_secret_key;

	/**
	 * Should live payments be accepted
	 *
	 * @var bool
	 */
	public $is_production;

	/**
	 * Allows payment via Heartland Marketing Solutions (gift cards)
	 *
	 * @var bool
	 */
	public $allow_gift_cards;

	/**
	 * Should debug
	 *
	 * @var bool
	 */
	public $debug;
	
	/**
	 * @var string
	 */
	public $button_color;

	/**
	 * @var int
	 */
	public $google_merchant_id;
	
	/**
	 * @var string
	 */
	public $global_payments_merchant_id;

	/**
	 * @var array
	 */
	public $accepted_cards;

	/**
	 * 
	 */
	//public $CCTypes;

	public $payment_action;


	public function get_first_line_support_email() {
		return 'onlinepayments@googlepay.us';
	}


	public function configure_method_settings() {
		$this->id                 =  self::GATEWAY_ID;
		$this->method_title       = __( 'GooglePay', 'googlepay-gateway-provider-for-woocommerce' );
		$this->method_description = __( 'Connect to the Google Pay gateway', 'globalpayments-gateway-provider-for-woocommerce' );
	}

	public function get_frontend_gateway_options() {
		return array(
			'publicApiKey'	=> $this->get_credential_setting( 'public_key' ),
			'btnColor'		=> $this->button_color,
		);
	}

    public function get_backend_gateway_options() {
		global $wp_version;
		$gpApiGateway = new GpApiGateway();

		return array(
			'appId'                    => $gpApiGateway->get_credential_setting( 'app_id' ),
			'appKey'                   => $gpApiGateway->get_credential_setting( 'app_key' ),
			'channel'                  => Channel::CardNotPresent,
			'country'                  => wc_get_base_location()['country'],
			'developerId'              => '',
			'environment'              => $gpApiGateway->is_production ? Environment::PRODUCTION : Environment::TEST,
			'methodNotificationUrl'    => WC()->api_request_url('globalpayments_threedsecure_methodnotification'),
			'challengeNotificationUrl' => WC()->api_request_url('globalpayments_threedsecure_challengenotification'),
			'merchantContactUrl'       => $gpApiGateway->merchant_contact_url,
			'dynamicHeaders'           => [
				'x-gp-platform' => 'wordpress;version=' . $wp_version . ';woocommerce;version=' . WC()->version,
				'x-gp-extension' => 'globalpayments-woocommerce;version=' . Plugin::VERSION,
			],
			'debug'                    => $gpApiGateway->debug,
		);
	}

	public function get_gateway_form_fields()  {
		return array(
			'global_payments_merchant_id' => array(
				'title'       => __( 'Global Payments Merchant Id', 'global_payments_merchant_id' ),
				'type'        => 'text',
				'default'     => '',
			),
			'google_merchant_id' => array(
				'title'       => __( 'Google Merchant Id', 'google_merchant_id' ),
				'type'        => 'text',
				'default'     => '',
			),
			'accepted_cards'    => array(
				'title'       => __( 'Accepted Cards', 'accepted_cards' ),
				'type'        => 'multiselect',
				'class'       => 'accepted_cards',
				'css'         => 'width: 450px',
				'description' => __( 'Choose for which AVS result codes, the transaction must be auto reversed.'),
				'options'     => $this->accepted_cards_options(),
				'default'     => array('JCB'),
				'custom_attributes' => array( 'required' => 'required' ),
			),
			'button_color'    => array(
				'title'       => __( 'Button Color', 'button_color' ),
				'type'        => 'select',
				'description' => __( 'Choose the botton color.', 'button_color' ),
				'default'     => 'white',
				'desc_tip'    => true,
				'options'     => array(
					'WHITE'	=> __( 'White', 'WHITE' ),
					'BLACK' => __( 'Black', 'BLACK' ),
				),
			),
		);
	}

	 public function init_form_fields() {
		$this->form_fields = array_merge(
	 	 array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'globalpayments-gateway-provider-for-woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Gateway', 'globalpayments-gateway-provider-for-woocommerce' ),
				'default' => 'no',
			),
			'payment_action' => array(
				'title'			=> __( 'Payment Action', 'globalpayments-gateway-provider-for-woocommerce' ),
				'type'			=> 'select',
				'description'	=> __( 'Choose whether you wish to capture funds immediately or authorize payment only for a delayed capture.', 'globalpayments-gateway-provider-for-woocommerce' ),
				'default'		=> self::TXN_TYPE_SALE,
				'desc_tip'		=> true,
				'options'		=> array(
					self::TXN_TYPE_SALE			=> __( 'Authorize + Capture', 'globalpayments-gateway-provider-for-woocommerce' ),
					self::TXN_TYPE_AUTHORIZE	=> __( 'Authorize only', 'globalpayments-gateway-provider-for-woocommerce' ),
				),
			),
			'title'	=> array(
				'title'			=> __( 'Title', 'globalpayments-gateway-provider-for-woocommerce' ),
				'type'			=> 'text',
				'description'	=> __( 'This controls the title which the user sees during checkout.', 'globalpayments-gateway-provider-for-woocommerce' ),
				'default'		=> __( 'Credit Card', 'globalpayments-gateway-provider-for-woocommerce' ),
				'desc_tip'		=> true,
			),
		), $this->get_gateway_form_fields() );
	 }

	public function accepted_cards_options()
	{
		return array(
			'VISA'			=> 'Visa',
			'MASTERCARD'	=> 'MasterCard',
			'AMEX'			=> 'AMEX',
			'DISCOVER'		=>  'Discover',
			'JCB'			=> 'JCB'
		);
	}

	public function payment_fields() {
		echo '<div>Pay with Google </div>';
		
		wp_enqueue_script(
			'google',
			( 'https://pay.google.com/gp/p/js/pay.js' ),
			array(),
			WC()->version,
			true
		);
		
		wp_enqueue_script(
			'googlepay',
			Plugin::get_url( '/assets/frontend/js/googlepay.js' ),
			array( 'google' ),
			WC()->version,
			true
		);

		wp_localize_script(
			'googlepay',
			'globalpayments_google_pay_params',
			array(
				'id'              => $this->id,
				'gateway_options' => $this->secure_payment_fields_config(),
			)
		);

		
	}

	protected function get_session_amount() {
		$cart_totals = WC()->session->get('cart_totals');
		return round($cart_totals['total'], 2);
	}

	public function secure_payment_fields_config(){
		return array(
			'id' 							=>$this->id,
			'google_merchant_id' 			=> $this->google_merchant_id,
			'global_payments_merchant_id' 	=> $this->global_payments_merchant_id,
			'accepted_cards' 				=> $this->accepted_cards,
			'button_color' 					=> $this->button_color,
			'currency'        				=> get_woocommerce_currency(),
			'grandTotalAmount'				=> (string)$this->get_session_amount(),
		);
	}

}