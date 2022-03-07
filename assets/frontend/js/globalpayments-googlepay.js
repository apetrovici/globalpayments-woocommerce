( function (
	$,
	globalpayments_googlepay_params,
	globalpayments_order,
	helper
) {
	function GooglePayWoocommerce ( options, order ) {
		/**
		 * Payment gateway id
		 *
		 * @type {string}
		 */
		this.id = options.id;

		/**
		 * The current order
		 *
		 * @type {object}
		 */
		this.order = order;

		/**
		 * Payment gateway options
		 *
		 * @type {object}
		 */
		this.gatewayOptions = options.gateway_options;

		/**
		 *
		 * @type {null}
		 */
		this.paymentsClient = null;

		this.attachEventHandlers();
	};

	GooglePayWoocommerce.prototype = {
		/**
		 * Add important event handlers for controlling the payment experience during checkout
		 *
		 * @returns
		 */
		attachEventHandlers: function () {
			// Checkout
			if ( 1 == wc_checkout_params.is_checkout ) {
				$( document.body ).on( 'updated_checkout', this.initialize.bind( this ) );
			}
		},

		initialize: function () {
			var self = this

			self.setGooglePaymentsClient();

			self.paymentsClient.isReadyToPay( {
				allowedPaymentMethods: ['CARD']
			} ).then( function ( response ) {
				if ( response.result ) {
					self.addGooglePayButton( self.id );
				}
			} ).catch( function ( err ) {
				console.error( err );
			} );
		},

		getBaseRequest: function () {
			return {
				apiVersion: 2,
				apiVersionMinor: 0
			};
		},

		/**
		 * Merchant display name
		 */
		getGoogleMerchantId: function () {
			return this.gatewayOptions.google_merchant_id;
		},

		/**
		 * Environment
		 */
		getEnvironment: function () {
			return this.gatewayOptions.env;
		},

		/**
		 * BTN Color
		 */
		getBtnColor: function () {
			return this.gatewayOptions.button_color;
		},

		getAllowedCardNetworks: function () {
			return this.gatewayOptions.cc_types;
		},

		getAllowedCardAuthMethods: function () {
			return ['PAN_ONLY', 'CRYPTOGRAM_3DS'];
		},

		getTokenizationSpecification: function () {
			return {
				type: 'PAYMENT_GATEWAY',
				parameters: {
					'gateway': 'globalpayments',
					'gatewayMerchantId': this.gatewayOptions.global_payments_merchant_id
				}
			}
		},

		getBaseCardPaymentMethod: function () {
			return {
				type: 'CARD',
				parameters: {
					allowedAuthMethods: this.getAllowedCardAuthMethods(),
					allowedCardNetworks: this.getAllowedCardNetworks()
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
			var paymentDataRequest = Object.assign({}, this.getBaseRequest() );
			paymentDataRequest.allowedPaymentMethods = [ this.getCardPaymentMethod() ];
			paymentDataRequest.transactionInfo = this.getGoogleTransactionInfo();
			paymentDataRequest.merchantInfo = {
				merchantId: this.getGoogleMerchantId()
			}

			return paymentDataRequest;
		},

		getGoogleTransactionInfo: function () {
			return {
				totalPriceStatus: 'FINAL',
				totalPrice: this.order.amount,
				currencyCode: this.order.currency
			};
		},

		/**
		 * Init google pay client
		 */
		setGooglePaymentsClient: function () {
			var self = this;
			if ( null === this.paymentsClient ) {
				this.paymentsClient = new google.payments.api.PaymentsClient( {
					environment: self.getEnvironment()
				} );
			}
		},

		/**
		 * Add the google pay button to the DOM
		 */
		addGooglePayButton: function (element) {
			var self = this
			var button = this.paymentsClient.createButton( {
					buttonColor: self.getBtnColor(),
					onClick: function () {
						self.onGooglePaymentButtonClicked();
					}
				} );

			var el = document.createElement('div');
			el.id = element;
			el.className = 'payment_box payment_method_' + self.id;
			$( helper.getPlaceOrderButtonSelector() ).after( el );

			$( '#' + element ).append( button );

			$( 'input[type=radio][name=payment_method]' ).change( function () {
				self.toggleGooglePayButton( this.id, element );
			} );

			if ( $( '#payment_method_' + self.id ).is( ':checked' ) ) {
				$( helper.getPlaceOrderButtonSelector() ).addClass( 'woocommerce-globalpayments-hidden' ).hide();
			} else {
				$( '#' + self.id ).hide();
			}
		},

		onGooglePaymentButtonClicked: function () {
			var self = this
			var paymentDataRequest = this.getGooglePaymentDataRequest();
			paymentDataRequest.transactionInfo = this.getGoogleTransactionInfo();

			this.paymentsClient.loadPaymentData( paymentDataRequest )
				.then( function ( paymentData ) {
					helper.createInputElement(
						self.id,
						'digital_wallet_token_response',
						JSON.stringify( JSON.parse( paymentData.paymentMethodData.tokenizationData.token ) )
					)

					return helper.placeOrder();
				} )
				.catch( function ( err ) {
					console.error( err );
				})
		},

		toggleGooglePayButton: function ( radioButtonId, googlepayButtonId ) {
			if ( 'payment_method_' + this.id == radioButtonId ) {
				$( '#' + googlepayButtonId ).show();
				$( helper.getPlaceOrderButtonSelector() ).hide();
			} else if ( 'payment_method_' + this.applepay_gateway_id == radioButtonId ) {
				$( '#' + googlepayButtonId ).hide();
				$( helper.getPlaceOrderButtonSelector() ).hide();
			} else {
				$( '#' + googlepayButtonId ).hide();
				$( helper.getPlaceOrderButtonSelector() ).show();
			}
		},
	}

	new GooglePayWoocommerce( globalpayments_googlepay_params, globalpayments_order );
}(
	( window ).jQuery,
	( window ).globalpayments_googlepay_params || {},
	( window ).globalpayments_order || {},
	( window ).GlobalPaymentsHelper
) );
