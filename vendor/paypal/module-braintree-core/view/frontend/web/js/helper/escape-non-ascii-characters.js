define(function () {
    'use strict';

    /**
     * Convert Non-ASCII characters into unicode.
     *
     * @param {string} value
     * @return {string}
     */
    return function (value) {
        return value.split('').map(function (c) {
            return /^[\x00-\x7F]$/.test(c) ? c : c.split('').map(function (a) {
                return '\\u00' + a.charCodeAt(0).toString(16);
            }).join('');
        }).join('');
    };
});
