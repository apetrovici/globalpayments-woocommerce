<p class="form-field form-field-wide">
    <button class="button button-secondary wc-globalpayments-pay-order"><?php esc_html_e( 'Pay for Order', 'woo-payment-gateway' ); ?></button>
	<?php echo wc_help_tip( __( 'Admins can process customer orders over the phone using this functionality.', 'woo-payment-gateway' ) ); ?>
</p>
<script type="text/template" id="tmpl-wc-globalpayments-pay-order-modal">
    <div class="wc-backbone-modal">
        <div class="wc-backbone-modal-content">
            <section class="wc-backbone-modal-main" role="main">
                <header class="wc-backbone-modal-header">
                    <h1><?php esc_html_e( 'Pay for Order', 'globalpayments-gateway-provider-for-woocommerce' ); ?></h1>
                    <button
                            class="modal-close modal-close-link dashicons dashicons-no-alt">
                        <span class="screen-reader-text">Close modal panel</span>
                    </button>
                </header>
                <article>
                    <form id="wc-globalpayments-pay-order-form">
	                    <?php wp_nonce_field( 'woocommerce-globalpayments-pay', 'woocommerce-globalpayments-pay-nonce' ); ?>
                        <input type="hidden" name="woocommerce_globalpayments_pay" value="1" />
                        <input type="hidden" name="entry_mode" value="<?php echo \GlobalPayments\Api\Entities\Enums\ManualEntryMethod::MOTO; ?>"/>
                        <input type="hidden" name="order_id" value="<?php echo $order->get_id(); ?>"/>
                        <div class="payment_methods" style="display: none">
                            <input type="radio" class="input-radio" name="payment_method" value="<?php echo $this->id; ?>" checked="checked">
                        </div>
                        <div class="wc_payment_method payment_method_<?php echo $this->id; ?> payment_box">
	                        <?php
                            $this->environment_indicator();

	                        // Set current user to Customer to retrieve payment methods
	                        $current_user_id = get_current_user_id();
	                        wp_set_current_user( $order->get_customer_id() );

	                        $this->saved_payment_methods();
	                        $this->form();

	                        // Restore the current user
	                        wp_set_current_user( $current_user_id );
	                        ?>
                        </div>
                        <?php
                        if ( ! empty( $order->get_transaction_id() ) ) { ?>
                        <fieldset>
                            <p><?php esc_html_e( 'This order has a transaction ID associated with it already. Click the checkbox to proceed.', 'globalpayments-gateway-provider-for-woocommerce' ); ?></p>
                            <input type="hidden" name="transaction_id" value="<?php echo $order->get_transaction_id(); ?>"/>
                            <input type="checkbox" name="allow_order"/>
                            <label><?php esc_html_e( 'Ok to process order', 'globalpayments-gateway-provider-for-woocommerce' ); ?></label>
                        </fieldset>
                        <?php } ?>
                    </form>
                </article>
                <footer>
                    <div class="inner">
                        <button type="submit" class="button button-primary button-large" id="place_order" value="Pay" data-value="Pay" >
                            <?php esc_html_e( 'Pay', 'globalpayments-gateway-provider-for-woocommerce' ); ?>
                        </button>
                    </div>
                </footer>
            </section>
        </div>
    </div>
    <div class="wc-backbone-modal-backdrop modal-close"></div>
</script>
