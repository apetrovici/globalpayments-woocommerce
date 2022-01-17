(function(
    $,
    globalpayments_google_pay_params
) {
    function GooglePayWoocommerce(options)
    {
        var google_merchant_id = options.google_merchant_id;
        
        var global_payments_merchant_id = options.global_payments_merchant_id;
        
        var accepted_cards = options.accepted_cards;
        
        var button_color = options.button_color;

        var currencyCode = options.currency;

        var self = this;
        // Checkout
        if ( 1 == wc_checkout_params.is_checkout ) {
            $( document.body ).on(
                'updated_checkout',
                function () {
                    self.initialize(options);
                }
            );
        }
    };

    GooglePayWoocommerce.prototype = {
        
        initialize: function(data) {

            var self = this;

            self.configData = data;
            self.paymentsClient = null;

            self.setGooglePaymentsClient();

            self.paymentsClient.isReadyToPay({
                allowedPaymentMethods: ['CARD']
            }).then(function (response) {
                if (response.result) {
                    self.addGooglePayButton('globalpayments_googlepay');
                }
            }).catch(function (err) {
                console.error(err);
            });

            return self;
        },

        getBaseRequest: function () {
            return {
                apiVersion: 2,
                apiVersionMinor: 0
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
            return ["PAN_ONLY", "CRYPTOGRAM_3DS"];
        },

        getTokenizationSpecification: function () {
            return {
                type: 'PAYMENT_GATEWAY',
                parameters: {
                    'gateway': 'globalpayments',
                    'gatewayMerchantId': 'gpapiqa1'
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
                    allowedPaymentMethods: [this.getBaseCardPaymentMethod()]
                }
            );
        },

        getGooglePaymentDataRequest: function () {
            var paymentDataRequest = Object.assign({}, this.getBaseRequest());
            paymentDataRequest.allowedPaymentMethods = [this.getCardPaymentMethod()];
            paymentDataRequest.transactionInfo = this.getGoogleTransactionInfo();
            paymentDataRequest.merchantInfo = {
                merchantId: this.getGoogleMerchantId()
            };
            return paymentDataRequest;
        },

        getGoogleTransactionInfo: function () {
            return {
                totalPriceStatus: 'FINAL',
                totalPrice: this.configData.grandTotalAmount,
                currencyCode: this.configData.currency
            };
        },

        /**
         * Init google pay client
         */
        setGooglePaymentsClient: function () {
            var self = this;
            if (null === this.paymentsClient) {
                this.paymentsClient = new google.payments.api.PaymentsClient({
                    environment: self.getEnvironment()
                });
            }
        },

        /**
         * Add the google pay button to the DOM
         */
        addGooglePayButton: function(element) {
            var self = this;
            var button = this.paymentsClient.createButton(
                {
                    buttonColor: self.getBtnColor(),
                    onClick: function () { self.onGooglePaymentButtonClicked() }
                }
            );
            
            var el       = document.createElement( 'div' );
			el.id        = element;
			el.className = 'payment_box payment_method_globalpayments_googlepay'; //'globalpayments ' + this.id + ' card-submit';
			$( self.getPlaceOrderButtonSelector() ).after( el );

            $('#' + element).append(button);

            $('input[type=radio][name=payment_method]').change(function() {
                self.toggleGoodlePayButton(this.id, element);
            });

            if ( $('#payment_method_globalpayments_googlepay').is(':checked') ) {
                $( self.getPlaceOrderButtonSelector() ).addClass( 'woocommerce-globalpayments-hidden' ).hide();
            }
            
        },

        toggleGoodlePayButton: function(radioButtonId, googlepayButtonId){
            if( radioButtonId == 'payment_method_globalpayments_googlepay' ) {
                $('#' + googlepayButtonId ).show();
                $(this.getPlaceOrderButtonSelector()).hide();
            } else {
                $('#' + googlepayButtonId ).hide();
                $(this.getPlaceOrderButtonSelector()).show();
            }
        },

        /**
		 * Convenience function to get CSS selector for the built-in 'Place Order' button
		 *
		 * @returns {string}
		 */
		getPlaceOrderButtonSelector: function () { return '#place_order'; },

        /**
		 * Places/submits the order to WooCommerce
		 *
		 * Attempts to click the default 'Place Order' button that is used by payment methods.
		 * This is to account for other plugins taking action based on that click event, even
		 * though there are usually better options. If anything fails during that process,
		 * we fall back to calling `this.placeOrder` manually.
		 *
		 * @returns
		 */
		placeOrder: function () {
			try {
				var originalSubmit = $( this.getPlaceOrderButtonSelector() );
				if ( originalSubmit ) {
					originalSubmit.click();
					return;
				}
			} catch ( e ) {
				/* om nom nom */
			}

			$( this.getForm() ).submit();
		},

        /**
		 * Gets the current checkout form
		 *
		 * @returns {Element}
		 */
		getForm: function () {
			var checkoutForms = [
				// Order Pay
				'form#order_review',
				// Checkout
				'form[name="checkout"]',
				// Add payment method
				'form#add_payment_method'
			];
			var forms = document.querySelectorAll( checkoutForms.join( ',' ) );
			return forms.item( 0 );
		},

        onGooglePaymentButtonClicked: function() {
            var self = this;
            var paymentDataRequest = this.getGooglePaymentDataRequest();
            
            paymentDataRequest.transactionInfo = this.getGoogleTransactionInfo();

            this.paymentsClient.loadPaymentData(paymentDataRequest).then(function (paymentData) {
                var token = paymentData.paymentMethodData.tokenizationData.token;
                /**
                 * Get hidden
                 *
                 * @type {HTMLInputElement}
                 */

                var tokenResponseElement =

                (document.getElementById('gp_googlepay_digital_wallet_token_response'));

                if ( ! tokenResponseElement) {
                    tokenResponseElement      = document.createElement('input');
                    tokenResponseElement.id   = self.configData.id + '_digital_wallet_token_response';
                    tokenResponseElement.name = self.configData.id +  '[digital_wallet_token_response]';
                    tokenResponseElement.type = 'hidden';
                    $('form[name="checkout"]').append(tokenResponseElement);
                }
                tokenResponseElement.value = JSON.stringify(JSON.parse(token));

                //$('#gp_googlepay_token_response').val(JSON.stringify(JSON.parse(token)));

                return self.placeOrder();

            }).catch(function (err) {
                // Handle errors
                console.error(err);
            });
        },
    };

    new GooglePayWoocommerce(globalpayments_google_pay_params.gateway_options);

}(
    (window).jQuery,
    (window).globalpayments_google_pay_params,
));