( function (
	$,
	globalpayments_google_pay_params,
	helpers
) {
	function GooglePayWoocommerce ( options )
	{
		var google_merchant_id  = options.google_merchant_id;
		var global_payments_merchant_id = options.global_payments_merchant_id;
		var accepted_cards		= options.accepted_cards;
		var button_color		= options.button_color;
		var currencyCode		= options.currency;
		var self				= this;
		// Checkout
		if ( 1 == wc_checkout_params.is_checkout ) {
			$( document.body ).on (
				'updated_checkout',
				function () {
					self.initialize( options );
				}
			);
		}
	};

	GooglePayWoocommerce.prototype = {
		
		initialize: function( data ) {
			var self			= this;
			self.configData		= data;
			self.paymentsClient	= null;

			self.setGooglePaymentsClient();

			self.paymentsClient.isReadyToPay ( {
				allowedPaymentMethods: [ 'CARD' ]
			}).then( function ( response ) {
				if ( response.result ) {
					self.addGooglePayButton( 'globalpayments_googlepay' );
				}
			}).catch( function ( err ) {
				console.error( err );
			});

			return self;
		},

		getBaseRequest: function () {
			return {
				apiVersion		: 2,
				apiVersionMinor	: 0
			}
		},

		/**
		 * Merchant display name
		 */
		getGoogleMerchantId: function () {
			return this.google_merchant_id;
		},

		/**
		 * Environment
		 */
		getEnvironment: function () {
			return this.configData.env;
		},

		/**
		 * BTN Color
		 */
		getBtnColor: function () {
			return this.button_color ;
		},

		getAllowedCardNetworks: function () {
			return this.configData.accepted_cards;
		},

		getAllowedCardAuthMethods: function () {
			return [ 'PAN_ONLY', 'CRYPTOGRAM_3DS' ];
		},

		getTokenizationSpecification: function () {
			return {
				type:	'PAYMENT_GATEWAY',
				parameters: {
					'gateway'			: 'globalpayments',
					'gatewayMerchantId'	: 'gpapiqa1'
				}
			}
		},

		getBaseCardPaymentMethod: function () {
			return {
				type : 'CARD',
				parameters: {
					allowedAuthMethods	: this.getAllowedCardAuthMethods(),
					allowedCardNetworks	: this.getAllowedCardNetworks()
				}
			}
		},

		getCardPaymentMethod: function () {
			return Object.assign(
				{},
				this.getBaseCardPaymentMethod(),
				{
					tokenizationSpecification: this.getTokenizationSpecification()
				}
			);
		},

		getGoogleIsReadyToPayRequest: function () {
			return Object.assign(
				{},
				this.getBaseRequest(),
				{
					allowedPaymentMethods: [ this.getBaseCardPaymentMethod() ]
				}
			);
		},

		getGooglePaymentDataRequest: function () {
			var paymentDataRequest						= Object.assign({}, this.getBaseRequest() );
			paymentDataRequest.allowedPaymentMethods	= [ this.getCardPaymentMethod() ];
			paymentDataRequest.transactionInfo			= this.getGoogleTransactionInfo();
			paymentDataRequest.merchantInfo				= { merchantId: this.getGoogleMerchantId() };

			return paymentDataRequest;
		},

		getGoogleTransactionInfo: function () {
			return {
				totalPriceStatus	: 'FINAL',
				totalPrice			: this.configData.grandTotalAmount,
				currencyCode		: this.configData.currency
			};
		},

		/**
		 * Init google pay client
		 */
		setGooglePaymentsClient: function () {
			var self = this;
			if ( null === this.paymentsClient ) {
				this.paymentsClient = new google.payments.api.PaymentsClient ( {
					environment: self.getEnvironment()
				});
			}
		},

		/**
		 * Add the google pay button to the DOM
		 */
		addGooglePayButton: function ( element ) {
			var self	= this;
			var button	= this.paymentsClient.createButton (
				{
					buttonColor	: self.getBtnColor(),
					onClick		: function() { self.onGooglePaymentButtonClicked() }
				}
			);
			
			var el			= document.createElement( 'div' );
			el.id			= element;
			el.className	= 'payment_box payment_method_globalpayments_googlepay'; 
			$( helpers.getPlaceOrderButtonSelector() ).after( el );

			$( '#' + element ).append(button);

			$( 'input[type=radio][name=payment_method]' ).change( function () {
				self.toggleGooglePayButton( this.id, element );
			});

			if ( $( '#payment_method_globalpayments_googlepay' ).is( ':checked' ) ) {
				$( helpers.getPlaceOrderButtonSelector() ).addClass( 'woocommerce-globalpayments-hidden' ).hide();
			} else {
				$( '#globalpayments_googlepay' ).hide();
			}
		},

		toggleGooglePayButton : function ( radioButtonId, googlepayButtonId ){
			if ( 'payment_method_globalpayments_googlepay' == radioButtonId ) {
				$( '#' + googlepayButtonId ).show();
				$( helpers.getPlaceOrderButtonSelector() ).hide();
			} else if ( 'payment_method_globalpayments_applepay' == radioButtonId ) {
				$( '#' + googlepayButtonId ).hide();
				$( helpers.getPlaceOrderButtonSelector() ).hide();
			} else  {
				$( '#' + googlepayButtonId ).hide();
				$( helpers.getPlaceOrderButtonSelector() ).show();
			}
		},

		onGooglePaymentButtonClicked : function () {
			var self = this;
			var paymentDataRequest				= this.getGooglePaymentDataRequest();
			paymentDataRequest.transactionInfo	= this.getGoogleTransactionInfo();

			this.paymentsClient.loadPaymentData( paymentDataRequest ).then( function ( paymentData ) {
				var token = paymentData.paymentMethodData.tokenizationData.token;
				/**
				 * Get hidden
				 * @type {HTMLInputElement}
				 */
				var tokenResponseElement = ( document.getElementById( 'gp_googlepay_digital_wallet_token_response' ) );
				if ( ! tokenResponseElement ) {
					tokenResponseElement		= document.createElement( 'input' );
					tokenResponseElement.id		= self.configData.id + '_digital_wallet_token_response';
					tokenResponseElement.name	= self.configData.id + '[digital_wallet_token_response]';
					tokenResponseElement.type	= 'hidden';
					$( 'form[name="checkout"]' ).append( tokenResponseElement );
				}
				tokenResponseElement.value = JSON.stringify( JSON.parse( token ) );

				return helpers.placeOrder();
			}).catch( function ( err ) {
				console.error( err );
			});
		},
	};

	new GooglePayWoocommerce ( globalpayments_google_pay_params.gateway_options );

} (
	( window ).jQuery,
	( window ).globalpayments_google_pay_params,
	( window ).GlobalPaymentsHelpers
) );