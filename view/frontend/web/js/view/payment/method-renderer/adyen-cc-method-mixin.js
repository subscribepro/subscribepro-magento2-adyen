define(['jquery'], function ($) {
    'use strict';
    let mixin = {
        initialize: function () {
            this._super();
            let self = this;
            self.storeCc = true;
            let selector = 'input[name*="storeDetails"]';

            var waitForEl = function (selector, callback, maxTimes = false) {
                if ($(selector).length) {
                    callback();
                } else {
                    if (maxTimes === false || maxTimes > 0) {
                        maxTimes != false && maxTimes--;
                        setTimeout(function () {
                            waitForEl(selector, callback, maxTimes);
                        }, 100);
                    }
                }
            };

            waitForEl(selector, function () {
                let storeCc = false;
                window.checkoutConfig.totalsData.items.forEach(function (item) {
                    JSON.parse(item.options).forEach(function (option) {
                        if (option.label === 'Regular Delivery') {
                            $(selector).prop('checked', true);
                            storeCc = true;
                        }
                    });
                });
                $(selector).click(function(e) {
                    if (storeCc) {
                        e.preventDefault();
                        e.stopPropagation();
                        console.log('SubscribePro subscription active. Must save card.');
                    }
                });
            }, 100);
        }
    };
    return function (target) {
        return target.extend(mixin);
    };
});
