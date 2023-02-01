( function ( $ ) {
	function GlobalPaymentsBNPLWooCommerce() {
		this.attachEventHandlers();
	};

	GlobalPaymentsBNPLWooCommerce.prototype = {
		/**
		 * Add important event handlers for controlling the payment experience during checkout
		 *
		 * @returns
		 */
		attachEventHandlers: function () {
			var self = this;

			// Fix `Checkout` and `Order Pay` pages after back button
			$( window ).on( 'pageshow' , function( e ) {
				if ( e.originalEvent.persisted ) {
					$( document.body ).on( 'wc_fragments_ajax_error', function () {
						$( document.body ).trigger( 'wc_fragment_refresh' );
					} );
					$( document.body ).on( 'wc_fragments_refreshed', function () {
						$( self.getForm() ).unblock();
					} );
				}
			} );
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
				'form#add_payment_method',
				// Admin Order Pay
				'form#wc-globalpayments-pay-order-form',
			];
			var forms = document.querySelectorAll( checkoutForms.join( ',' ) );

			return forms.item( 0 );
		},
	};

	if ( ! window.GlobalPaymentsBNPLWooCommerce ) {
		window.GlobalPaymentsBNPLWooCommerce = new GlobalPaymentsBNPLWooCommerce();
	}
} (
	( window ).jQuery
) );
