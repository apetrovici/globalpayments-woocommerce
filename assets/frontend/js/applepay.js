/**

 * NOTICE OF LICENSE

 *

 * This file is licenced under the Software License Agreement.

 * With the purchase or the installation of the software in your application

 * you accept the licence agreement.

 *

 * You must not modify, adapt or create derivative works of this source code

 *

 *  @author    GlobalPayments

 *  @copyright 2021 GlobalPayments

 *  @license   LICENSE


 */

 (function (
    $,
    globalpayments_applepay_params,
    helpers
) {
    function ApplePayWoocommerce(options) {

       var self = this;
       
       if ( 1 == wc_checkout_params.is_checkout ) {
            $( document.body ).on(
                'updated_checkout',
                function () {
                    self.initialize(options);
                }
            );
        }
       
    };

    ApplePayWoocommerce.prototype = {

        initialize: function (data) {
            if (this.deviceSupported() === false) {
                $('.payment_method_globalpayments_applepay').hide();
                return;
            }

           
            this.configData = data;
            this.addApplePayButton('globalpayments_applepay');
        },

        /**
         * Add the apple pay button to the DOM
         */
        addApplePayButton: function (element) {

            var self                = this;
            var paymentButton       = document.createElement('div');
            paymentButton.className = 'apple-pay-button apple-pay-button-white-with-line';
            paymentButton.title     = 'Pay with Apple Pay';
            paymentButton.alt       = 'Pay with Apple Pay';
            paymentButton.id        = 'globalpayments_applepay';

            paymentButton.addEventListener('click', function (e) {
                e.preventDefault();
                var applePaySession = self.createApplePaySession();
                applePaySession.begin();
            });

            $( helpers.getPlaceOrderButtonSelector() ).after( paymentButton );

            $('input[type=radio][name=payment_method]').change(function() {
                self.toggleApplePayButton(this.id, 'globalpayments_applepay');
            });

            if ( $('#payment_method_globalpayments_applepay').is(':checked') ) {
                $( helpers.getPlaceOrderButtonSelector() ).addClass( 'woocommerce-globalpayments-hidden' ).hide();
            } else {
                $('#globalpayments_applepay' ).hide();
            }
            
        },

        toggleApplePayButton: function(radioButtonId, applepayButtonId){
            if( radioButtonId == 'payment_method_globalpayments_applepay' ) {
                $('#' + applepayButtonId ).show();
                $(helpers.getPlaceOrderButtonSelector()).hide();
            } else if ( radioButtonId == 'payment_method_globalpayments_googlepay' ) {
                $('#' + applepayButtonId ).hide();
                $(helpers.getPlaceOrderButtonSelector()).show();
            }  else {
                $('#' + applepayButtonId ).hide();
                $(helpers.getPlaceOrderButtonSelector()).show();
            }
        },

        createApplePaySession: function() {
            var self = this;
            self.onApplePayValidateMerchant();
            try {
                var applePaySession = new ApplePaySession(1, self.context.getPaymentRequest());
            } catch (err) {
                console.error('Unable to create ApplePaySession', err);
                alert("We're unable to take your payment through Apple Pay. Please try an again or use an alternative payment method.");
                return false;
            }

            // Handle validate merchant event
            applePaySession.onvalidatemerchant = function (event) {
                self.onApplePayValidateMerchant(event, applePaySession);
            }

            // Attach payment auth event
            applePaySession.onpaymentauthorized = function (event) {
                self.onApplePayPaymentAuthorize(event, applePaySession, options);
            }

            applePaySession.oncancel = function (event) {
                alert("We're unable to take your payment through Apple Pay. Please try an again or use an alternative payment method.")
            }.bind(this);

            return applePaySession;
        },

        onApplePayValidateMerchant: function(event, session) {
            var self = this;

            $.ajax({
                cache: false,
                url: this.configData.validateMerchantUrl,
                data: {'validationUrl': 'event.validationURL'},
                dataType: 'json',
            }).done( function (response) {
                console.log('response', response);
                    if(response.error) {
                        session.abort();
                        alert($t("We're unable to take your payment through Apple Pay. Please try an again or use an alternative payment method."));
                    } else {
                        session.completeMerchantValidation(JSON.parse(response.message));
                    }
                    
            }).fail(function (response) {
                session.abort();
                alert($t("We're unable to take your payment through Apple Pay. Please try an again or use an alternative payment method."));
            });
        },

        onApplePayPaymentAuthorize: function(event, session) {
            try {
                var tokenResponseElement =
                ( document.getElementById( 'gp_googlepay_digital_wallet_token_response' ) );
                if ( ! tokenResponseElement ) {
                    tokenResponseElement      = document.createElement('input');
                    tokenResponseElement.id   = self.configData.id + '_digital_wallet_token_response';
                    tokenResponseElement.name = self.configData.id +  '[digital_wallet_token_response]';
                    tokenResponseElement.type = 'hidden';
                    $( 'form[name="checkout"]' ).append( tokenResponseElement );
                }
                tokenResponseElement.value = JSON.stringify(event.payment.token.paymentData);

                return helpers.placeOrder();
                
            } catch (e) {
                session.completePayment(ApplePaySession.STATUS_FAILURE);
            }
        },

        getPaymentRequest: function () {
            return {
                countryCode:    this.getCountryId(),
                currencyCode:   this.configData.currency,
                merchantCapabilities: [
                    'supports3DS'
                ],
                total: {
                    label:  this.getDisplayName(),
                    amount: this.configData.grandTotalAmount
                },
            }
        },

        getCountryId: function () {
            return this.configData.countryCode;
        },

        getDisplayName: function () {
            return this.configData.merchantDisplayName;
        },

        deviceSupported: function () {
            if (location.protocol != 'https:') {
                console.warn('Apple Pay requires your checkout be served over HTTPS');
                return false;
            }

            if ((window.ApplePaySession && ApplePaySession.canMakePayments()) !== true) {
                console.warn('Apple Pay is not supported on this device/browser');
                return false;
            }

            return true;
        },  
    };

    new ApplePayWoocommerce(globalpayments_applepay_params);
}(
    (window).jQuery,
    (window).globalpayments_applepay_params,
    (window).GlobalPaymentsHelpers
));