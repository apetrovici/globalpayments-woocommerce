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
	};

	if ( ! window.GlobalPaymentsHelper ) {
		window.GlobalPaymentsHelper = new Helper();
	}
} (
	( window ).jQuery,
) );