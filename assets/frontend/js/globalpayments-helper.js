(function (
	$
) {
	function Helper() {};

	Helper.prototype = {

		/**
		 * Convenience function to get CSS selector for the built-in 'Place Order' button
		 *
		 * @returns {string}
		 */
		getPlaceOrderButtonSelector: function () {
			return '#place_order';
		},

		/**
		 * Convenience function to get CSS selector for the custom 'Place Order' button's parent element
		 *
		 * @param {string} id
		 * @returns {string}
		 */
		getSubmitButtonTargetSelector: function ( id ) {
			return '#' + id + '-card-submit';
		},

		/**
		 * Convenience function to get CSS selector for the radio input associated with our payment method
		 *
		 * @returns {string}
		 */
		getPaymentMethodRadioSelector: function ( id ) {
			return '.payment_methods input.input-radio[value="' + id + '"]';
		},

		/**
		 * Convenience function to get CSS selector for stored card radio inputs
		 *
		 * @returns {string}
		 */
		getStoredPaymentMethodsRadioSelector: function ( id ) {
			return '.payment_method_' + id + ' .wc-saved-payment-methods input';
		},

		/**
		 * Swaps the default WooCommerce 'Place Order' button for our iframe-d button
		 * or digital wallet buttons when one of our gateways is selected.
		 *
		 * @returns
		 */
		toggleSubmitButtons: function ( id ) {
			// 1. GlobalPayments gateway selected
			var paymentGatewaySelected = $( this.getPaymentMethodRadioSelector( id ) ).is( ':checked' );
			// 2. Stored Cards available (registered user selects stored card as payment method)
			var savedCardsAvailable    = $( this.getStoredPaymentMethodsRadioSelector( id ) + '[value!="new"]' ).length > 0;
			// 3. User selects (new) card as payment method
			var newSavedCardSelected   = 'new' === $( this.getStoredPaymentMethodsRadioSelector( id ) + ':checked' ).val();
			// 4. Place Order button already hidden by another GlobalPayments gateway
			var placeOrderBtnIsHidden = $( this.getPlaceOrderButtonSelector() ).hasClass( 'woocommerce-globalpayments-hidden' );

			if ( paymentGatewaySelected ) {
				// our gateway was selected
				// selected payment method is card or digital wallet
				if ( ! savedCardsAvailable  || savedCardsAvailable && newSavedCardSelected ) {
					$( this.getSubmitButtonTargetSelector( id ) ).show();
					$( this.getPlaceOrderButtonSelector() ).addClass( 'woocommerce-globalpayments-hidden' ).hide();
				} else {
					// selected payment method is stored card
					$( this.getSubmitButtonTargetSelector( id ) ).hide();
					// show platform `Place Order` button
					$( this.getPlaceOrderButtonSelector() ).removeClass( 'woocommerce-globalpayments-hidden' ).show();
				}
			} else {
				$( this.getSubmitButtonTargetSelector( id ) ).hide();
				// show platform `Place Order` button if it wasn't hidden by one of our gateways
				if ( ! placeOrderBtnIsHidden ) {
					$( this.getPlaceOrderButtonSelector() ).show();
				}
			}
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

		createInputElement: function ( id, name, value ) {
			var inputElement = ( document.getElementById( id + '-' + name ) );

			if ( ! inputElement ) {
				inputElement = document.createElement( 'input' );
				inputElement.id = id + '-' + name;
				inputElement.name = id + '[' + name + ']';
				inputElement.type = 'hidden';
				this.getForm().appendChild( inputElement );
			}

			inputElement.value = value;
		},

		/**
		 * Creates the parent for the submit button
		 *
		 * @returns
		 */
		createSubmitButtonTarget: function ( id ) {
			var el = document.createElement( 'div' );
			el.id = this.getSubmitButtonTargetSelector( id ).replace( '#', '' );
			el.className = 'globalpayments ' + id + ' card-submit';
			$( this.getPlaceOrderButtonSelector() ).after( el );
			// match the visibility of our payment form
			this.toggleSubmitButtons( id );
		},

		/**
		 * Places/submits the order to Woocommerce
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
	};

	if ( ! window.GlobalPaymentsHelper ) {
		window.GlobalPaymentsHelper = new Helper();
	}
} (
	( window ).jQuery
) );