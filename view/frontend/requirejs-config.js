/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*jshint browser:true jquery:true*/
/*global alert*/
var config = {
    config: {
        mixins: {
            'Magento_Checkout/js/action/get-payment-information': {
                'Swarming_SubscribeProAdyen/js/model/get-payment-information-mixin': true
            },
            'Adyen_Payment/js/view/payment/method-renderer/adyen-cc-method': {
                'Swarming_SubscribeProAdyen/js/view/payment/method-renderer/adyen-cc-method-mixin': true
            }
        }
    }
};
