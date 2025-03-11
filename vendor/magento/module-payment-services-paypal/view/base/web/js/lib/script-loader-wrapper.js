/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/* eslint-disable no-undef */
define(['underscore', 'Magento_PaymentServicesPaypal/js/lib/script-loader'], function (_, scriptLoader) {
    'use strict';

    var promises = {},
        defaultNamespace = 'paypal';

    /**
     * Parse src query string and move all params to object
     *
     * @param {Object} params
     * @return {Object}
     */
    function processParamsSrc(params) {
        var processedParams = _.clone(params),
            url = new URL(params.src),
            queryString = url.search.substring(1),
            urlParams = JSON.parse('{"' +
                decodeURI(queryString).replace(/"/g, '\\"').replace(/&/g, '","').replace(/=/g, '":"') +
                '"}');

        _.extend(processedParams, urlParams);
        delete processedParams.src;

        return processedParams;
    }

    /**
     * Convert params to object key => value format
     *
     * @param {Object} params
     * @return {Object}
     */
    function convertToParamsObject(params) {
        var processedParams = {};

        _.each(params, function (param) {
            processedParams[param.name] = param.value;
        });

        return processedParams;
    }

    /**
     * Load PayPal sdk with params.
     *
     * @param {Array} params
     * @param {String} sdkNamespace
     * @return {Promise}
     */
    return function (params, sdkNamespace) {
        var src;

        if (!params || !params.length) {
            return Promise.reject();
        }

        params = convertToParamsObject(params);
        params['data-namespace'] = sdkNamespace || defaultNamespace;

        if (!params || !params.src) {
            return Promise.reject();
        }

        src = params.src;

        if (!promises[src]) {
            params = processParamsSrc(params);

            promises[src] = scriptLoader.load(params);
        }

        return promises[src];
    };
});
