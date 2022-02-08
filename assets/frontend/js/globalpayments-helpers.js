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

 *  @copyright 2022 GlobalPayments

 *  @license   LICENSE


 */

(function (
    $
) {
    function Helpers() {};

    Helpers.prototype = {

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
        getSubmitButtonTargetSelector: function (id) {
            return '#' + id + '-card-submit';
        },

        /**
         * Places/submits the order to PrestaShop
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
                var originalSubmit = $(this.getPlaceOrderButtonSelector());
                if (originalSubmit) {
                    originalSubmit.click();
                    return;
                }
            } catch (e) {
            }
        },

    };
    if(!window.GlobalPaymentsHelpers) {
        window.GlobalPaymentsHelpers = new Helpers();
    }
}(
    (window).jQuery,
));