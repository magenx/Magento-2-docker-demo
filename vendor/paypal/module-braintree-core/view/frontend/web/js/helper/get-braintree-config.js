define(['jquery'], function ($) {
    'use strict';

    return function () {
        return $.ajax({
            method: 'POST',
            url: '/graphql',
            contentType: 'application/json',
            data: JSON.stringify({
                query: `{
                    storeConfig {
                        braintree_merchant_account_id,
                        braintree_3dsecure_verify_3dsecure,
                        braintree_3dsecure_always_request_3ds,
                        braintree_3dsecure_threshold_amount,
                        braintree_3dsecure_allowspecific,
                        braintree_3dsecure_specificcountry
                    }
                  }`
            })
        });
    };
});
