/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([], function () {
    'use strict';

    /**
     * Error type to handle response errors.
     *
     * @param {String} message
     * @constructor
     */
    function ResponseError(message) {
        this.name = 'ResponseError';
        this.message = message;
        this.stack = new Error().stack;
    }

    ResponseError.prototype = new Error;

    /**
     * Return a string representation
     *
     * @returns {String}
     */
    ResponseError.prototype.toString = function () {
        return this.message;
    };

    return ResponseError;
});
