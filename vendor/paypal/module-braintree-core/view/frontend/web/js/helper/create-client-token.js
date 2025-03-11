define(['jquery'], function ($) {
    'use strict';

    return function () {
        return $.ajax({
            method: 'POST',
            url: '/graphql',
            contentType: 'application/json',
            data: JSON.stringify({
                query: `
                    mutation {
                      createBraintreeClientToken
                    }`
            })
        });
    };
});
