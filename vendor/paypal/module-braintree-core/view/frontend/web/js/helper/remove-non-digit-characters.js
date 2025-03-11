define([
    'underscore'
], function (_) {
    'use strict';

    /**
     * Remove any non-digit characters from string.
     *
     * @param {string} value
     * @return {string}
     */
    return function (value) {
        return _.isString(value) ? value.replace(/\D/g, '') : '';
    };
});
