 ( function (
	$,
	globalpayments_applepay_params,
	globalpayments_order,
	helper
) {
	function ApplePayWoocommerce( options, order ) {
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

		//@TODO PrestaShop: rename configData to gatewayOptions
		/**
		 * Payment gateway options
		 *
		 * @type {object}
		 */
		this.gatewayOptions = options.gateway_options;

		this.attachEventHandlers();

		//@TODO PrestaShop: remove options param from initialize
	};

	ApplePayWoocommerce.prototype = {
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
			if ( false === this.deviceSupported() ) {
				$( '.payment_method_' + this.id ).hide();
				return;
			}

			this.addApplePayButton();
		},

		/**
		 * Add the apple pay button to the DOM
		 */
		addApplePayButton: function () {
			var self				= this;
			var paymentButton		= document.createElement( 'div' );
			paymentButton.className	= 'apple-pay-button apple-pay-button-white-with-line';
			paymentButton.title		= 'Pay with Apple Pay';
			paymentButton.alt		= 'Pay with Apple Pay';
			//@TODO PrestaShop: missing id?
			paymentButton.id		= self.id;
//@TODO check the helper
			paymentButton.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				var applePaySession = self.createApplePaySession();
				applePaySession.begin();
			});

			$( helper.getPlaceOrderButtonSelector() ).after( paymentButton );

			$( 'input[type=radio][name=payment_method]' ).change( function () {
				self.toggleApplePayButton( this.id, self.id );
			});

			if ( $( '#payment_method_' + self.id ).is( ':checked' ) ) {
				$( helper.getPlaceOrderButtonSelector() ).addClass( 'woocommerce-globalpayments-hidden' ).hide();
			} else {
				$( '#' + self.id ).hide();
			}
		},

		toggleApplePayButton: function ( radioButtonId, applepayButtonId ){
			if ( 'payment_method_' + this.id == radioButtonId ) {
				$( '#' + applepayButtonId ).show();
				$( helper.getPlaceOrderButtonSelector() ).hide();
			} else if ( 'payment_method_' + this.googlepay_gateway_id == radioButtonId ) {
				$( '#' + applepayButtonId ).hide();
				$( helper.getPlaceOrderButtonSelector() ).show();
			}  else {
				$( '#' + applepayButtonId ).hide();
				$( helper.getPlaceOrderButtonSelector() ).show();
			}
		},

		createApplePaySession: function () {
			var self = this;
			//@TODO should this be here?
			self.onApplePayValidateMerchant();
			try {
				var applePaySession = new ApplePaySession( 1, self.context.getPaymentRequest() );
			} catch ( err ) {
				console.error( 'Unable to create ApplePaySession', err );
				alert( "We're unable to take your payment through Apple Pay. Please try again or use an alternative payment method." );
				return false;
			}

			// Handle validate merchant event
			applePaySession.onvalidatemerchant = function ( event ) {
				self.onApplePayValidateMerchant( event, applePaySession );
			}

			// Attach payment auth event
			applePaySession.onpaymentauthorized = function ( event ) {
				self.onApplePayPaymentAuthorize( event, applePaySession );
			}

			applePaySession.oncancel = function ( event ) {
				alert( "We're unable to take your payment through Apple Pay. Please try again or use an alternative payment method." )
			}.bind( this );

			return applePaySession;
		},

		onApplePayValidateMerchant: function ( event, session ) {
			//@TODO PrestahShop: remove var self
			//@TODO PrestahShop: adapt url
			$.ajax({
				cache: false,
				url: this.gatewayOptions.validate_merchant_url,
				data: {'validationUrl': event.validationURL},
				dataType: 'json',
			}).done( function ( response ) {
				if ( response.error ) {
					console.log( 'response', response );
					session.abort();
					alert( $t( "We're unable to take your payment through Apple Pay. Please try again or use an alternative payment method." ) );
				} else {
					//@TODO what should be parse response.message(WC) or response (PrestaShop)
					session.completeMerchantValidation( JSON.parse( response.message ) );
				}
			}).fail( function ( response ) {
				console.log( 'response', response );
				session.abort();
				alert( $t( "We're unable to take your payment through Apple Pay. Please try again or use an alternative payment method." ) );
			});
		},

		//@TODO check diffs
		onApplePayPaymentAuthorize: function ( event, session ) {
			try {
				var tokenResponseElement = ( document.getElementById( 'gp_googlepay_digital_wallet_token_response' ) );
				if ( ! tokenResponseElement ) {
					tokenResponseElement		= document.createElement('input');
					tokenResponseElement.id		= self.id + '_digital_wallet_token_response';
					tokenResponseElement.name	= self.id + '[digital_wallet_token_response]';
					tokenResponseElement.type	= 'hidden';
					$( 'form[name="checkout"]' ).append( tokenResponseElement );
				}
				tokenResponseElement.value = JSON.stringify( event.payment.token.paymentData );

				return helper.placeOrder();
			} catch ( e ) {
				session.completePayment( ApplePaySession.STATUS_FAILURE );
			}
		},

		getPaymentRequest: function () {
			return {
				countryCode: this.getCountryId(),
				currencyCode: this.configData.currency,
				merchantCapabilities: [
					'supports3DS'
				],
				total: {
					label: this.getDisplayName(),
					//@TODO check this.order.amount.toString??
					amount: this.order.amount
				},
			}
		},

		//@TODO PrestahShop: adapt
		getCountryId: function () {
			return this.gatewayOptions.country_code;
		},

		getDisplayName: function () {
			return this.gatewayOptions.apple_merchant_display_name;
		},

		deviceSupported: function () {
			if ( 'https:' !== location.protocol ) {
				console.warn( 'Apple Pay requires your checkout be served over HTTPS' );
				return false;
			}

			if ( true !== ( window.ApplePaySession && ApplePaySession.canMakePayments() ) ) {
				console.warn( 'Apple Pay is not supported on this device/browser' );
				return false;
			}

			return true;
		},
	};

	new ApplePayWoocommerce( globalpayments_applepay_params, globalpayments_order );
	 //@TODO var or {} in PrestaShop
}(
	( window ).jQuery,
	( window ).globalpayments_applepay_params || {},
	( window ).globalpayments_order || {},
	( window ).GlobalPaymentsHelper
));