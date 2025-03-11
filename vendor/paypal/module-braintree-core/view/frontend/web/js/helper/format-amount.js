define(function () {
    'use strict';

    /**
     * Format amount (string) to two decimals.
     *
     * @param {string} str
     * @return {string}
     */
    return function (amount) {
        return parseFloat(amount).toFixed(2);
    };
});
