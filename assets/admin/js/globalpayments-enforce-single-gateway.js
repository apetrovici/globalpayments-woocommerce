( function( $ ) {
    $( function() {
        // Toggle GlobalPayments gateway on/off.
        $( '[data-gateway_id^="globalpayments_"]' ).on( 'click', '.wc-payment-gateway-method-toggle-enabled', function() {
            var toggle = true;
console.log( $( this ).closest('tr').data('gateway_id')    );

            if(  $( this ).closest('tr').data('gateway_id') == 'globalpayments_googlepay' ){
                return ;
            }

            // Toggle off
            if ( ! $( this ).find( 'span.woocommerce-input-toggle--disabled' ).length > 0 ) {
                return true;
            }

            // Toggle on
            var clicked = $( this ).closest('tr').data('gateway_id');
            $( '[data-gateway_id^="globalpayments_"]' ).each( function() {
                if ( ! $( this ).find( 'span.woocommerce-input-toggle--disabled' ).length > 0 ) {
                    if( $( this ).data('gateway_id') != 'globalpayments_googlepay' && clicked != 'globalpayments_googlepay' ) {
                        var gateway_title = $( this ).closest( 'tr' ).find( 'a.wc-payment-gateway-method-title' ).text();
                        window.alert( 'You can enable only one GlobalPayments gateway at a time. ' +
                            'Please disable ' + gateway_title + ' first!' );
                        toggle = false;
                        return;
                    }
                    
                }
            });

            return toggle;
        });
    });

})( jQuery );
