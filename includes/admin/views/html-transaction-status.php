<p class="form-field form-field-wide">
    <button class="button button-secondary wc-globalpayments-transaction-status"><?php esc_html_e( 'View Transaction Status', 'woo-payment-gateway' ); ?></button>
    <?php echo wc_help_tip( __( 'Admins can process customer orders over the phone using this functionality.', 'woo-payment-gateway' ) ); ?>
</p>
<script type="text/template" id="tmpl-wc-globalpayments-transaction-status-modal">
    <div class="wc-backbone-modal">
        <div class="wc-backbone-modal-content">
            <section class="wc-backbone-modal-main" role="main">
                <header class="wc-backbone-modal-header">
                    <h1><?php esc_html_e( 'View Transaction Status', 'globalpayments-gateway-provider-for-woocommerce' ); ?></h1>
                    <button
                            class="modal-close modal-close-link dashicons dashicons-no-alt">
                        <span class="screen-reader-text">Close modal panel</span>
                    </button>
                </header>
                <article>
                    <div class="container">
                        <div class="row">
                            <div class="col">
                                Actions
                            </div>
                        </div>
                        <div class="row">
                            <div class="col">
                                General Info
                            </div>
                            <div class="col">
                                Payment Data
                            </div>
                        </div>
                        <div class="row">
                            <div class="col">
                                Response Data
                            </div>
                        </div>
                    </div>

                </article>
                <footer>
                    <div class="inner">
                        <button type="submit" class="button button-primary button-large" id="transaction_status" value="View" data-value="View">
                            <?php esc_html_e( 'View', 'globalpayments-gateway-provider-for-woocommerce' ); ?>
                        </button>
                    </div>
                </footer>
            </section>
        </div>
    </div>
    <div class="wc-backbone-modal-backdrop modal-close"></div>
</script>
