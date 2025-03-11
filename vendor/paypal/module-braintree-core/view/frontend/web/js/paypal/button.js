/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        'rjsResolver',
        'uiRegistry',
        'uiComponent',
        'underscore',
        'jquery',
        'Magento_Customer/js/customer-data',
        'mage/translate',
        'braintree',
        'braintreeCheckoutPayPalAdapter',
        'braintreeDataCollector',
        'braintreePayPalCheckout',
        'PayPal_Braintree/js/form-builder',
        'PayPal_Braintree/js/helper/remove-non-digit-characters',
        'PayPal_Braintree/js/helper/replace-single-quote-character',
        'domReady!'
    ],
    function (
        resolver,
        registry,
        Component,
        _,
        $,
        customerData,
        $t,
        braintree,
        Braintree,
        dataCollector,
        paypalCheckout,
        formBuilder,
        removeNonDigitCharacters,
        replaceSingleQuoteCharacter
    ) {
        'use strict';

        return {
            events: {
                onClick: null,
                onCancel: null,
                onError: null
            },

            /**
             * Initialize button
             *
             * @param buttonConfig
             * @param lineItems
             */
            init: function (buttonConfig, lineItems) {
                if ($('.action-braintree-paypal-message').length) {
                    $('.product-add-form form').on('keyup change paste', 'input, select, textarea', function () {
                        let currentPrice, currencySymbol;

                        currentPrice = $('.product-info-main span').find('[data-price-type=\'finalPrice\']').text();
                        currencySymbol = $('.action-braintree-paypal-message[data-pp-type="product"]')
                            .data('currency-symbol');
                        $('.action-braintree-paypal-message[data-pp-type="product"]')
                            .attr('data-pp-amount', currentPrice.replace(currencySymbol,''));
                    });
                }

                this.loadSDK(buttonConfig, lineItems);

                window.addEventListener('hashchange', function () {
                    const step = window.location.hash.replace('#', '');

                    if (step === 'shipping') {
                        Braintree.getPayPalInstance().teardown(function () {
                            this.loadSDK(buttonConfig, lineItems);
                        }.bind(this));
                    }

                }.bind(this));

                window.addEventListener('paypal:reinit-express', function () {
                    this.loadSDK(buttonConfig, lineItems);
                }.bind(this));
            },

            /**
             * Load Braintree PayPal SDK
             *
             * @param buttonConfig
             * @param lineItems
             */
            loadSDK: function (buttonConfig, lineItems) {
                braintree.create({
                    authorization: buttonConfig.clientToken
                }, function (clientErr, clientInstance) {
                    if (clientErr) {
                        console.error('paypalCheckout error', clientErr);
                        let error = 'PayPal Checkout could not be initialized. Please contact the store owner.';

                        return this.showError(error);
                    }
                    dataCollector.create({
                        client: clientInstance,
                        paypal: true
                    }, function (err) {
                        if (err) {
                            return console.log(err);
                        }
                    });
                    paypalCheckout.create({
                        client: clientInstance
                    }, function (err, paypalCheckoutInstance) {
                        Braintree.setPayPalInstance(paypalCheckoutInstance);
                        let configSDK = {
                                components: 'buttons,messages,funding-eligibility',
                                'enable-funding': this.isCreditActive(buttonConfig) ? 'credit' : 'paylater',
                                currency: buttonConfig.currency
                            },

                            buyerCountry = this.getMerchantCountry(buttonConfig);

                        if (buttonConfig.environment === 'sandbox'
                            && (buyerCountry !== '' || buyerCountry !== 'undefined'))
                        {
                            configSDK['buyer-country'] = buyerCountry;
                        }
                        paypalCheckoutInstance.loadPayPalSDK(configSDK, function () {
                            this.renderPayPalButtons(paypalCheckoutInstance, lineItems);
                            this.renderPayPalMessages();
                        }.bind(this));
                    }.bind(this));
                }.bind(this));
            },

            /**
             * Is Credit enabled
             *
             * @param buttonConfig
             * @returns {boolean}
             */
            isCreditActive: function (buttonConfig) {
                return buttonConfig.isCreditActive;
            },

            /**
             * Get merchant country
             *
             * @param buttonConfig
             * @returns {string}
             */
            getMerchantCountry: function (buttonConfig) {
                return buttonConfig.merchantCountry;
            },

            /**
             * Render PayPal buttons
             *
             * @param paypalCheckoutInstance
             * @param lineItems
             */
            renderPayPalButtons: function (paypalCheckoutInstance, lineItems) {
                this.payPalButton(paypalCheckoutInstance, lineItems);
            },

            /**
             * Render PayPal messages
             */
            renderPayPalMessages: function () {
                $('.action-braintree-paypal-message').each(function () {
                    window.paypal.Messages({
                        amount: $(this).data('pp-amount'),
                        pageType: $(this).data('pp-type'),
                        style: {
                            layout: $(this).data('messaging-layout'),
                            text: {
                                color:   $(this).data('messaging-text-color')
                            },
                            logo: {
                                type: $(this).data('messaging-logo'),
                                position: $(this).data('messaging-logo-position')
                            }
                        }
                    }).render('#' + $(this).attr('id'));


                });
            },

            /**
             * @param paypalCheckoutInstance
             * @param lineItems
             */
            payPalButton: function (paypalCheckoutInstance, lineItems) {
                $('.action-braintree-paypal-logo').each(function () {
                    let currentElement = $(this),
                        style = {
                            label: currentElement.data('label'),
                            color: currentElement.data('color'),
                            shape: currentElement.data('shape')
                        },
                        button;

                    if (currentElement.data('fundingicons')) {
                        style.fundingicons = currentElement.data('fundingicons');
                    }

                    // Render
                    button = window.paypal.Buttons({
                        fundingSource: currentElement.data('funding'),
                        style: style,
                        createOrder: function () {
                            return paypalCheckoutInstance.createPayment({
                                amount: currentElement.data('amount'),
                                locale: currentElement.data('locale'),
                                currency: currentElement.data('currency'),
                                flow: 'checkout',
                                enableShippingAddress: true,
                                displayName: currentElement.data('displayname'),
                                lineItems: JSON.parse(lineItems)
                            });
                        },
                        validate: function (actions) {
                            let cart = customerData.get('cart'),
                                customer = customerData.get('customer'),
                                declinePayment = false,
                                isGuestCheckoutAllowed;

                            isGuestCheckoutAllowed = cart().isGuestCheckoutAllowed;
                            declinePayment = !customer().firstname && !isGuestCheckoutAllowed
                                && typeof isGuestCheckoutAllowed !== 'undefined';

                            if (declinePayment) {
                                actions.disable();
                            }
                        },

                        onCancel: function () {
                            jQuery('#maincontent').trigger('processStop');
                        },

                        onError: function (errorData) {
                            console.error('paypalCheckout button render error', errorData);
                            jQuery('#maincontent').trigger('processStop');
                        },

                        onClick: function () {
                            if (currentElement.data('location') === 'productpage') {
                                let form = $('#product_addtocart_form');

                                if (!(form.validation() && form.validation('isValid'))) {
                                    return false;
                                }
                            }

                            let cart = customerData.get('cart'),
                                customer = customerData.get('customer'),
                                declinePayment = false,
                                isGuestCheckoutAllowed;

                            isGuestCheckoutAllowed = cart().isGuestCheckoutAllowed;
                            declinePayment = !customer().firstname && !isGuestCheckoutAllowed
                                && typeof isGuestCheckoutAllowed !== 'undefined';
                            if (declinePayment) {
                                // eslint-disable-next-line
                                alert($t('To check out, please sign in with your email address.'));
                            }
                        },

                        onApprove: function (approveData) {
                            return paypalCheckoutInstance.tokenizePayment(approveData, function (err, payload) {
                                jQuery('#maincontent').trigger('processStart');

                                /* Set variables & default values for shipping/recipient name to billing */
                                let accountFirstName = replaceSingleQuoteCharacter(payload.details.firstName),
                                    accountLastName = replaceSingleQuoteCharacter(payload.details.lastName),
                                    accountEmail = replaceSingleQuoteCharacter(payload.details.email),
                                    recipientFirstName = accountFirstName,
                                    recipientLastName = accountLastName,
                                    address = payload.details.shippingAddress,
                                    recipientName = null,
                                    actionSuccess,
                                    isRequiredBillingAddress,
                                    phone = _.get(payload, ['details', 'phone'], '');

                                // Map the shipping address correctly
                                if (!_.isUndefined(address.recipientName) && _.isString(address.recipientName)) {
                                    /*
                                         * Trim leading/ending spaces before splitting,
                                         * filter to remove array keys with empty values
                                         * & set to variable.
                                         */
                                    recipientName = address.recipientName.trim().split(' ').filter(n => n);
                                }

                                /*
                                     * If the recipientName is not null, and it is an array with
                                     * first/last name, use it. Otherwise, keep the default billing first/last name.
                                     * This is to avoid cases of old accounts where spaces were allowed to first or
                                     * last name in PayPal and the result was an array with empty fields
                                     * resulting in empty names in the system.
                                     */
                                if (!_.isNull(recipientName) && !_.isUndefined(recipientName[1])) {
                                    recipientFirstName = replaceSingleQuoteCharacter(recipientName[0]);
                                    recipientLastName = replaceSingleQuoteCharacter(recipientName[1]);
                                }

                                payload.details.shippingAddress = {
                                    streetAddress: typeof address.line2 !== 'undefined' && _.isString(address.line2)
                                        ? replaceSingleQuoteCharacter(address.line1)
                                                + ' ' + replaceSingleQuoteCharacter(address.line2)
                                        : replaceSingleQuoteCharacter(address.line1),
                                    locality: replaceSingleQuoteCharacter(address.city),
                                    postalCode: address.postalCode,
                                    countryCodeAlpha2: address.countryCode,
                                    email: accountEmail,
                                    recipientFirstName: recipientFirstName,
                                    recipientLastName: recipientLastName,
                                    telephone: removeNonDigitCharacters(phone),
                                    region: typeof address.state !== 'undefined'
                                        ? replaceSingleQuoteCharacter(address.state)
                                        : ''
                                };

                                payload.details.email = accountEmail;
                                payload.details.firstName = accountFirstName;
                                payload.details.lastName = accountLastName;
                                if (typeof payload.details.businessName !== 'undefined'
                                        && _.isString(payload.details.businessName)) {
                                    payload.details.businessName
                                            = replaceSingleQuoteCharacter(payload.details.businessName);
                                }

                                // Map the billing address correctly
                                isRequiredBillingAddress = currentElement.data('requiredbillingaddress');

                                if (isRequiredBillingAddress === 1
                                            && typeof payload.details.billingAddress !== 'undefined') {
                                    let billingAddress = payload.details.billingAddress;

                                    payload.details.billingAddress = {
                                        streetAddress: typeof billingAddress.line2 !== 'undefined'
                                                && _.isString(billingAddress.line2)
                                            ? replaceSingleQuoteCharacter(billingAddress.line1)
                                                    + ' ' + replaceSingleQuoteCharacter(billingAddress.line2)
                                            : replaceSingleQuoteCharacter(billingAddress.line1),
                                        locality: replaceSingleQuoteCharacter(billingAddress.city),
                                        postalCode: billingAddress.postalCode,
                                        countryCodeAlpha2: billingAddress.countryCode,
                                        telephone: removeNonDigitCharacters(phone),
                                        region: typeof billingAddress.state !== 'undefined'
                                            ? replaceSingleQuoteCharacter(billingAddress.state)
                                            : ''
                                    };
                                }

                                if (currentElement.data('location') === 'productpage') {
                                    let form = $('#product_addtocart_form');

                                    payload.additionalData = form.serialize();
                                }

                                actionSuccess = currentElement.data('actionsuccess');

                                formBuilder.build(
                                    {
                                        action: actionSuccess,
                                        fields: {
                                            result: JSON.stringify(payload)
                                        }
                                    }
                                ).submit();
                            });
                        }
                    });

                    if (!button.isEligible()) {
                        console.log('PayPal button is not elligible');
                        currentElement.parent().remove();
                        return;
                    }
                    if (button.isEligible() && $('#' + currentElement.attr('id')).length) {
                        button.render('#' + currentElement.attr('id'));
                    }
                });
            }
        };
    }
);
