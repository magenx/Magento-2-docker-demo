/*eslint-disable */
/* jscs:disable */
/*!
 * paypal-js v3.1.1 (2021-03-14T21:08:07.006Z)
 * Copyright 2020-present, PayPal, Inc. All rights reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
var paypalLoadScript = (function (exports) {
    'use strict';

    function findScript(url, attributes) {
        var currentScript = document.querySelector("script[src=\"".concat(url, "\"]"));
        if (currentScript === null) return null;
        var nextScript = createScriptElement(url, attributes); // check if the new script has the same number of data attributes

        if (Object.keys(currentScript.dataset).length !== Object.keys(nextScript.dataset).length) {
            return null;
        }

        var isExactMatch = true; // check if the data attribute values are the same

        Object.keys(currentScript.dataset).forEach(function (key) {
            if (currentScript.dataset[key] !== nextScript.dataset[key]) {
                isExactMatch = false;
            }
        });
        return isExactMatch ? currentScript : null;
    }
    function insertScriptElement(_ref) {
        var url = _ref.url,
            attributes = _ref.attributes,
            onSuccess = _ref.onSuccess,
            onError = _ref.onError;
        var newScript = createScriptElement(url, attributes);
        newScript.onerror = onError;
        newScript.onload = onSuccess;
        document.head.insertBefore(newScript, document.head.firstElementChild);
    }
    function processOptions(options) {
        var sdkBaseURL = "https://www.paypal.com/sdk/js";

        if (options.sdkBaseURL) {
            sdkBaseURL = options.sdkBaseURL;
            delete options.sdkBaseURL;
        }

        var processedMerchantIDAttributes = processMerchantID(options["merchant-id"], options["data-merchant-id"]);
        var newOptions = Object.assign(options, processedMerchantIDAttributes);

        var _Object$keys$filter$r = Object.keys(newOptions).filter(function (key) {
                return typeof newOptions[key] !== "undefined" && newOptions[key] !== null && newOptions[key] !== "";
            }).reduce(function (accumulator, key) {
                var value = newOptions[key].toString();

                if (key.substring(0, 5) === "data-") {
                    accumulator.dataAttributes[key] = value;
                } else {
                    accumulator.queryParams[key] = value;
                }

                return accumulator;
            }, {
                queryParams: {},
                dataAttributes: {}
            }),
            queryParams = _Object$keys$filter$r.queryParams,
            dataAttributes = _Object$keys$filter$r.dataAttributes;

        return {
            url: "".concat(sdkBaseURL, "?").concat(objectToQueryString(queryParams)),
            dataAttributes: dataAttributes
        };
    }
    function objectToQueryString(params) {
        var queryString = "";
        Object.keys(params).forEach(function (key) {
            if (queryString.length !== 0) queryString += "&";
            queryString += key + "=" + params[key];
        });
        return queryString;
    }

    function createScriptElement(url) {
        var attributes = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
        var newScript = document.createElement("script");
        newScript.src = url;
        Object.keys(attributes).forEach(function (key) {
            newScript.setAttribute(key, attributes[key]);

            if (key === "data-csp-nonce") {
                newScript.setAttribute("nonce", attributes["data-csp-nonce"]);
            }
        });
        return newScript;
    }

    function processMerchantID(merchantID, dataMerchantID) {
        var newMerchantID = "";
        var newDataMerchantID = "";

        if (Array.isArray(merchantID)) {
            if (merchantID.length > 1) {
                newMerchantID = "*";
                newDataMerchantID = merchantID.toString();
            } else {
                newMerchantID = merchantID.toString();
            }
        } else if (typeof merchantID === "string" && merchantID.length > 0) {
            newMerchantID = merchantID;
        } else if (typeof dataMerchantID === "string" && dataMerchantID.length > 0) {
            newMerchantID = "*";
            newDataMerchantID = dataMerchantID;
        }

        return {
            "merchant-id": newMerchantID,
            "data-merchant-id": newDataMerchantID
        };
    }

    /**
     * Load the Paypal JS SDK script asynchronously.
     *
     * @param {Object} options - used to configure query parameters and data attributes for the JS SDK.
     * @param {PromiseConstructor} [PromisePonyfill=window.Promise] - optional Promise Constructor ponyfill.
     * @return {Promise<Object>} paypalObject - reference to the global window PayPal object.
     */

    function loadScript(options) {
        var PromisePonyfill = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : getDefaultPromiseImplementation();
        validateArguments(options, PromisePonyfill); // resolve with null when running in Node

        if (typeof window === "undefined") return PromisePonyfill.resolve(null);

        var _processOptions = processOptions(options),
            url = _processOptions.url,
            dataAttributes = _processOptions.dataAttributes;

        var namespace = dataAttributes["data-namespace"] || "paypal";
        var existingWindowNamespace = getPayPalWindowNamespace(namespace); // resolve with the existing global paypal namespace when a script with the same params already exists

        if (findScript(url, dataAttributes) && existingWindowNamespace) {
            return PromisePonyfill.resolve(existingWindowNamespace);
        }

        return loadCustomScript({
            url: url,
            attributes: dataAttributes
        }, PromisePonyfill).then(function () {
            var newWindowNamespace = getPayPalWindowNamespace(namespace);

            if (newWindowNamespace) {
                return newWindowNamespace;
            }

            throw new Error("The window.".concat(namespace, " global variable is not available."));
        });
    }
    /**
     * Load a custom script asynchronously.
     *
     * @param {Object} options - used to set the script url and attributes.
     * @param {PromiseConstructor} [PromisePonyfill=window.Promise] - optional Promise Constructor ponyfill.
     * @return {Promise<void>} returns a promise to indicate if the script was successfully loaded.
     */

    function loadCustomScript(options) {
        var PromisePonyfill = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : getDefaultPromiseImplementation();
        validateArguments(options, PromisePonyfill);
        var url = options.url,
            attributes = options.attributes;

        if (typeof url !== "string" || url.length === 0) {
            throw new Error("Invalid url.");
        }

        if (typeof attributes !== "undefined" && typeof attributes !== "object") {
            throw new Error("Expected attributes to be an object.");
        }

        return new PromisePonyfill(function (resolve, reject) {
            // resolve with undefined when running in Node
            if (typeof window === "undefined") return resolve();
            insertScriptElement({
                url: url,
                attributes: attributes,
                onSuccess: function onSuccess() {
                    return resolve();
                },
                onError: function onError() {
                    return reject(new Error("The script \"".concat(url, "\" failed to load.")));
                }
            });
        });
    }

    function getDefaultPromiseImplementation() {
        if (typeof Promise === "undefined") {
            throw new Error("Promise is undefined. To resolve the issue, use a Promise polyfill.");
        }

        return Promise;
    }

    function getPayPalWindowNamespace(namespace) {
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        return window[namespace];
    }

    function validateArguments(options, PromisePonyfill) {
        if (typeof options !== "object" || options === null) {
            throw new Error("Expected an options object.");
        }

        if (typeof PromisePonyfill !== "undefined" && typeof PromisePonyfill !== "function") {
            throw new Error("Expected PromisePonyfill to be a function.");
        }
    }

    var version = "3.1.1";

    exports.loadCustomScript = loadCustomScript;
    exports.loadScript = loadScript;
    exports.version = version;

    Object.defineProperty(exports, '__esModule', { value: true });

    return exports;

}({}));
window.paypalLoadCustomScript = paypalLoadScript.loadCustomScript;
window.paypalLoadScript = paypalLoadScript.loadScript;
