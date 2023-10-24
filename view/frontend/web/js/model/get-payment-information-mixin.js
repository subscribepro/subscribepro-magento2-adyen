define([
    'jquery',
    'mage/utils/wrapper'
], function ($,
             wrapper
) {
    'use strict';

    return function (paymentInformationAction) {
        return wrapper.wrap(paymentInformationAction, function (originalAction) {
            return originalAction().then(function (result) {
                let stateData = JSON.parse(window.sessionStorage.getItem('adyen.stateData'));
                let storeCc = false;
                window.checkoutConfig.totalsData.items.forEach(function (item) {
                    JSON.parse(item.options).forEach(function (option) {
                        if (option.label === 'Regular Delivery') {
                            storeCc = true;
                        }
                    });
                });
                if (storeCc && stateData) {
                    stateData.storePaymentMethod = true;
                    window.sessionStorage.setItem('adyen.stateData', JSON.stringify(stateData));
                }
                return result;
            });
        });
    };
});
