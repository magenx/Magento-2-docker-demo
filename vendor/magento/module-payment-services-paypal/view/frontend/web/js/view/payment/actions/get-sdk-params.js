/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/* eslint-disable no-undef */
define([
    'jquery',
    'underscore',
    'Magento_Customer/js/customer-data',
    'jquery/jquery-storageapi'
], function ($, _, customerData) {
    'use strict';

    var promise = null,
        timeoutKey = 'payments-sdk-params-timeout',
        paymentsKey = 'payments',
        sdkParamsKey = 'sdkParams';

    return function (cacheTtl) {
        if (!promise) {
            promise = new Promise(function (resolve, reject) {
                var dateNow = Date.now(),
                    dateTo = dateNow + cacheTtl,
                    timeout = $.localStorage.get(timeoutKey);
                if (timeout < dateNow || !timeout) {
                    return customerData.reload([paymentsKey]).done(function () {
                        $.localStorage.set(timeoutKey, dateTo);
                        resolve(customerData.get(paymentsKey)()[sdkParamsKey]);
                    }).fail(reject.bind(this, []));
                }

                if (!_.isEmpty(customerData.get(paymentsKey)())) {
                    return resolve(customerData.get(paymentsKey)()[sdkParamsKey]);
                }

                customerData.get(paymentsKey).subscribe(function (payments) {
                    resolve(payments[sdkParamsKey]);
                });

            });
        }

        return promise;
    };
});
