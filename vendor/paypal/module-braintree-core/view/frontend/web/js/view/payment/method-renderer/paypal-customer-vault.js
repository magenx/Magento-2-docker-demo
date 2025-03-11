/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
define([
    'jquery',
    'underscore',
    'mage/translate',
    'braintree',
    'braintreeDataCollector',
    'braintreePayPalCheckout',
    'PayPal_Braintree/js/helper/format-amount',
    'PayPal_Braintree/js/helper/remove-non-digit-characters',
    'PayPal_Braintree/js/helper/replace-unsupported-characters',
    'Magento_Checkout/js/action/create-billing-address',
    'Magento_Checkout/js/action/select-billing-address',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Magento_CheckoutAgreements/js/view/checkout-agreements',
    'Magento_Vault/js/view/payment/method-renderer/vault',
    'Magento_Ui/js/model/messageList'
], function (
    $,
    _,
    $t,
    client,
    dataCollector,
    paypalCheckout,
    formatAmount,
    removeNonDigitCharacters,
    replaceUnsupportedCharacters,
    createBillingAddress,
    selectBillingAddress,
    fullScreenLoader,
    quote,
    additionalValidators,
    checkoutAgreements,
    VaultComponent,
    globalMessageList
) {
    'use strict';

    return VaultComponent.extend({
        defaults: {
            template: 'PayPal_Braintree/payment/paypal/customer-vault',

            /**
             * Component config set via provider.
             */
            code: null,
            nonceUrl: null,
            publicHash: null,
            clientToken: null,
            clientInstance: null,
            checkout: null,
            details: {},

            /**
             * {Object}
             */
            events: {
                onClick: null,
                onCancel: null,
                onError: null,
                onRender: null
            },

            /**
             * Additional payment data
             *
             * {Object}
             */
            additionalData: {},

            /**
             * Placeholders for PayPal instance vaulted payment.
             */
            paypalInstance: null,
            paymentMethodNonce: null,
            grandTotalAmount: null,
            isReviewRequired: false,
            customerEmail: null,

            /**
             * {Array}
             */
            lineItemsArray: [
                'name',
                'kind',
                'quantity',
                'unitAmount',
                'productCode',
                'description'
            ],

            /**
             * PayPal client configuration
             *
             * {Object}
             */
            clientConfig: {
                dataCollector: {
                    paypal: true
                },

                /**
                 * Triggers when widget is loaded
                 */
                onReady: function () {
                    this.setupPayPal();
                },

                /**
                 * Triggers on payment nonce receive
                 * @param {Object} response
                 */
                onPaymentMethodReceived: function (response) {
                    this.beforePlaceOrder(response);
                }
            },

            imports: {
                onActiveChange: 'active'
            }
        },

        /**
         * Set list of observable attributes
         *
         * @returns {exports.initObservable}
         */
        initObservable: function () {
            let self = this;

            this._super().observe(['active', 'isReviewRequired', 'customerEmail']);

            window.addEventListener('hashchange', function (e) {
                let methodCode = quote.paymentMethod();

                if (methodCode && methodCode.method === self.getId()) {
                    if (e.newURL.indexOf('payment') > 0 && self.grandTotalAmount !== null) {
                        self.reInitPayPalVault();
                    }
                }
            });

            quote.paymentMethod.subscribe(function (value) {
                if (value.method && value.method === self.getId()) {
                    self.reInitPayPalVault();
                }
            });

            self.grandTotalAmount = quote.totals()['base_grand_total'];

            quote.totals.subscribe(function () {
                if (self.grandTotalAmount !== quote.totals()['base_grand_total']) {
                    self.grandTotalAmount = quote.totals()['base_grand_total'];
                    let methodCode = quote.paymentMethod();

                    if (methodCode && methodCode.method === self.getId()) {
                        self.reInitPayPalVault();
                    }
                }
            });

            self.isReviewRequired(false);
            self.initClientConfig();

            return this;
        },

        /**
         * Triggers when payment method change
         *
         * @param {Boolean} isActive
         */
        onActiveChange: function (isActive) {
            if (!isActive) {
                return;
            }

            // need always re-init Braintree with PayPal configuration
            this.reInitPayPalVault();
        },

        /**
         * Init config
         */
        initClientConfig: function () {
            this.clientConfig = _.extend(this.clientConfig, this.getPayPalConfig());

            _.each(this.clientConfig, function (fn, name) {
                if (typeof fn === 'function') {
                    this.clientConfig[name] = fn.bind(this);
                }
            }, this);
        },

        /**
         * Get configuration for PayPal
         *
         * @returns {Object}
         */
        getPayPalConfig: function () {
            let totals = quote.totals(),
                config = {};

            config.paypal = {
                flow: 'checkout',
                amount: formatAmount(this.grandTotalAmount),
                currency: totals['base_currency_code'],
                locale: this.getLocale(),

                /**
                 * Triggers on any Braintree error
                 */
                onError: function () {
                    this.paymentMethodNonce = null;
                },

                /**
                 * Triggers if browser doesn't support PayPal Checkout
                 */
                onUnsupported: function () {
                    this.paymentMethodNonce = null;
                }
            };

            if (!quote.isVirtual()) {
                config.paypal.enableShippingAddress = true;
                config.paypal.shippingAddressEditable = false;
                config.paypal.shippingAddressOverride = this.getShippingAddress();
            }

            if (this.getMerchantName()) {
                config.paypal.displayName = this.getMerchantName();
            }

            return config;
        },

        /**
         * Re-init PayPal Vault Auth Flow
         */
        reInitPayPalVault: function () {
            this.disableButton();
            this.clientConfig.paypal.amount = formatAmount(this.grandTotalAmount);

            if (!quote.isVirtual()) {
                this.clientConfig.paypal.enableShippingAddress = true;
                this.clientConfig.paypal.shippingAddressEditable = false;
                this.clientConfig.paypal.shippingAddressOverride = this.getShippingAddress();
            }

            // Send Line Items
            this.clientConfig.paypal.lineItems = this.getLineItems();

            if (this.getPayPalInstance()) {
                this.getPayPalInstance().teardown(function () {
                    this.setPayPalInstance(null);
                    this.createClientInstance(null);
                }.bind(this));
            } else {
                this.createClientInstance(null);
                this.enableButton();
            }
        },

        /**
         * Get the current Braintree client instance.
         *
         * Null if not set.
         *
         * @return {*|null}
         */
        getClientInstance: function () {
            if (typeof this.clientInstance !== 'undefined' && this.clientInstance) {
                return this.clientInstance;
            }

            return null;
        },

        /**
         * Set the Braintree client instance or null it.
         *
         * @param val
         */
        setClientInstance: function (val) {
            this.clientInstance = val;
        },

        /**
         * Get the PayPal instance if already instantiated, otherwise null.
         *
         * @return {*|null}
         */
        getPayPalInstance: function () {
            if (typeof this.paypalInstance !== 'undefined' && this.paypalInstance) {
                return this.paypalInstance;
            }

            return null;
        },

        /**
         * Set the PayPal instance or null it by setting the value of the property.
         *
         * @param val
         */
        setPayPalInstance: function (val) {
            this.paypalInstance = val;
        },

        /**
         * Create the Braintree client instance.
         *
         * @param {Function|null} callback
         */
        createClientInstance: function (callback = null) {
            if (this.getClientToken() === null) {
                this.showError($t('Sorry, but something went wrong.'));
                return;
            }

            if (this.getClientInstance()) {
                if (typeof this.clientConfig.onReady === 'function') {
                    this.clientConfig.onReady(this);
                }

                if (typeof callback === 'function') {
                    callback(this.clientInstance);
                }

                return;
            }

            client.create({
                authorization: this.getClientToken()
            }, function (clientErr, clientInstance) {
                if (clientErr) {
                    console.error('Braintree Setup Error', clientErr);
                    return this.showError('Sorry, but something went wrong. Please contact the store owner.');
                }

                let options = {
                    client: clientInstance
                };

                if (typeof this.clientConfig.dataCollector === 'object'
                    && typeof this.clientConfig.dataCollector.paypal === 'boolean')
                {
                    options.paypal = true;
                }

                dataCollector.create(options, function (err, dataCollectorInstance) {
                    if (err) {
                        return console.log(err);
                    }
                    this.additionalData['device_data'] = dataCollectorInstance.deviceData;
                }.bind(this));

                this.setClientInstance(clientInstance);

                if (typeof this.clientConfig.onReady === 'function') {
                    this.clientConfig.onReady(this);
                }

                if (typeof callback === 'function') {
                    callback(this.getClientInstance());
                }
            }.bind(this));
        },

        /**
         * Get Environment
         *
         * @returns {String}
         */
        getEnvironment: function () {
            return window.checkoutConfig.payment['braintree_paypal'].environment;
        },

        /**
         * Setup PayPal instance
         */
        setupPayPal: function () {
            if (this.getPayPalInstance()) {
                fullScreenLoader.stopLoader(true);
                return;
            }

            paypalCheckout.create({
                autoSetDataUserIdToken: true,
                client: this.getClientInstance()
            }, function (createErr, paypalCheckoutInstance) {
                if (createErr) {
                    this.showError(
                        $t('PayPal Checkout could not be initialized. Please contact the store owner.')
                    );
                    console.error('paypalCheckout error', createErr);
                    return;
                }

                let quoteObj = quote.totals(),

                    configSDK = {
                        components: 'buttons,messages,funding-eligibility',
                        'enable-funding': 'paylater',
                        currency: quoteObj['base_currency_code']
                    },

                    buyerCountry = this.getMerchantCountry();

                if (this.getEnvironment() === 'sandbox' && buyerCountry !== null) {
                    configSDK['buyer-country'] = buyerCountry;
                }

                paypalCheckoutInstance.loadPayPalSDK(configSDK, function () {
                    this.loadPayPalButton(paypalCheckoutInstance, 'paypal');

                    if (this.isPayLaterEnabled()) {
                        this.loadPayPalButton(paypalCheckoutInstance, 'paylater');
                    }
                }.bind(this));
            }.bind(this));
        },

        /**
         * Load PayPal buttons
         *
         * @param paypalCheckoutInstance
         * @param funding
         */
        loadPayPalButton: function (paypalCheckoutInstance, funding) {
            let paypalPayment = this.clientConfig.paypal,
                onPaymentMethodReceived = this.clientConfig.onPaymentMethodReceived,

                style = {
                    label: this.getLabelByFunding(funding),
                    color: this.getColorByFunding(funding),
                    shape: this.getShapeByFunding(funding)
                },

                payPalButtonId = this.getButtonIdByFunding(funding),
                payPalButtonElement = $('#' + payPalButtonId),
                button,
                events = this.events;

            payPalButtonElement.html('');

            // Render
            this.setPayPalInstance(paypalCheckoutInstance);

            button = window.paypal.Buttons({
                fundingSource: funding,
                env: this.getEnvironment(),
                style: style,
                commit: true,
                locale: this.clientConfig.paypal.locale,

                onInit: function (data, actions) {
                    let agreements = checkoutAgreements().agreements,
                        shouldDisableActions = false;

                    actions.disable();

                    _.each(agreements, function (item) {
                        if (checkoutAgreements().isAgreementRequired(item)) {
                            let paymentMethodCode = quote.paymentMethod().method,
                                inputId = '#agreement_' + paymentMethodCode + '_' + item.agreementId,
                                inputEl = document.querySelector(inputId);

                            if (!inputEl.checked) {
                                shouldDisableActions = true;
                            }

                            inputEl.addEventListener('change', function () {
                                if (additionalValidators.validate(false)) {
                                    actions.enable();
                                } else {
                                    actions.disable();
                                }
                            });
                        }
                    });

                    if (!shouldDisableActions) {
                        actions.enable();
                    }
                },

                createOrder: function () {
                    return paypalCheckoutInstance.createPayment(paypalPayment).catch(function (err) {
                        throw err.details.originalError.details.originalError.paymentResource;
                    });
                },

                onCancel: function (data) {
                    console.log('checkout.js payment cancelled', JSON.stringify(data, 0, 2));

                    if (typeof events.onCancel === 'function') {
                        events.onCancel();
                    }
                },

                onError: function (err) {
                    if (err.errorName === 'VALIDATION_ERROR'
                        && err.errorMessage.indexOf('Value is invalid') !== -1
                    ) {
                        this.showError(
                            $t('Address failed validation. Please check and confirm your City, State, and Postal Code')
                        );
                    } else {
                        this.showError($t('PayPal Checkout could not be initialized. Please contact the store owner.'));
                    }

                    this.setPayPalInstance(null);
                    console.error('Paypal checkout.js error', err);

                    if (typeof events.onError === 'function') {
                        events.onError(err);
                    }
                }.bind(this),

                onClick: function (data) {
                    if (!quote.isVirtual()) {
                        this.clientConfig.paypal.enableShippingAddress = true;
                        this.clientConfig.paypal.shippingAddressEditable = false;
                        this.clientConfig.paypal.shippingAddressOverride = this.getShippingAddress();
                    }

                    // To check term & conditions input checked - validate additional validators.
                    if (!additionalValidators.validate(false)) {
                        return false;
                    }

                    if (typeof events.onClick === 'function') {
                        events.onClick(data);
                    }
                }.bind(this),

                onApprove: function (data) {
                    return paypalCheckoutInstance.tokenizePayment(data)
                        .then(function (payload) {
                            onPaymentMethodReceived(payload);
                        });
                }

            });

            if (button.isEligible() && payPalButtonElement.length) {
                button.render('#' + payPalButtonId).then(function () {
                    this.enableButton();

                    if (typeof this.clientConfig.onPaymentMethodError === 'function') {
                        this.clientConfig.onPaymentMethodError();
                    }
                }.bind(this)).then(function (data) {
                    if (typeof events.onRender === 'function') {
                        events.onRender(data);
                    }
                });
            }
        },

        /**
         * Prepare data to place order
         *
         * @param {Object} data
         */
        beforePlaceOrder: function (data) {
            this.setPaymentMethodNonce(data.nonce);
            this.customerEmail(data.details.email);
            if (quote.isVirtual()) {
                this.isReviewRequired(true);
            } else if (this.isRequiredBillingAddress() === '1' || quote.billingAddress() === null) {
                if (typeof data.details.billingAddress !== 'undefined') {
                    this.setBillingAddress(data.details, data.details.billingAddress);
                } else {
                    this.setBillingAddress(data.details, data.details.shippingAddress);
                }
            } else if (quote.shippingAddress() === quote.billingAddress()) {
                selectBillingAddress(quote.shippingAddress());
            } else {
                selectBillingAddress(quote.billingAddress());
            }
            this.placeOrder();
        },

        /**
         * Get the component's client token.
         *
         * @return {String}
         */
        getClientToken: function () {
            return this.clientToken;
        },

        /**
         * Get merchant country
         *
         * @returns {*}
         */
        getMerchantCountry: function () {
            return _.get(window.checkoutConfig.payment, ['braintree_paypal', 'merchantCountry'], null);
        },

        /**
         * Get PayPal payer email
         *
         * @returns {String}
         */
        getPayerEmail: function () {
            return this.details.payerEmail;
        },

        /**
         * Get type of payment
         *
         * @returns {String}
         */
        getPaymentIcon: function () {
            return window.checkoutConfig.payment['braintree_paypal'].paymentIcon;
        },

        /**
         * Get merchant name
         *
         * @returns {String}
         */
        getMerchantName: function () {
            return window.checkoutConfig.payment['braintree_paypal'].merchantName;
        },

        /**
         * Get payment method data
         *
         * @returns {Object}
         */
        getData: function () {
            let data = {
                'method': this.code,
                'additional_data': {
                    'public_hash': this.publicHash,
                    'payment_method_nonce': this.paymentMethodNonce
                }
            };

            data['additional_data'] = _.extend(data['additional_data'], this.additionalData);

            return data;
        },

        /**
         * Set payment nonce.
         *
         * @param {String} paymentMethodNonce
         */
        setPaymentMethodNonce: function (paymentMethodNonce) {
            this.paymentMethodNonce = paymentMethodNonce;
        },

        /**
         * Get shipping address
         *
         * @returns {Object}
         */
        getShippingAddress: function () {
            let address = quote.shippingAddress();

            return {
                recipientName: address.firstname + ' ' + address.lastname,
                line1: address.street[0],
                line2: typeof address.street[2] === 'undefined'
                    ? address.street[1]
                    : address.street[1] + ' ' + address.street[2],
                city: address.city,
                countryCode: address.countryId,
                postalCode: address.postcode,
                state: address.regionCode
            };
        },

        /**
         * Update quote billing address
         *
         * @param {Object}customer
         * @param {Object}address
         */
        setBillingAddress: function (customer, address) {
            let billingAddress = {
                street: [address.line1],
                city: address.city,
                postcode: address.postalCode,
                countryId: address.countryCode,
                email: customer.email,
                firstname: customer.firstName,
                lastname: customer.lastName,
                telephone: removeNonDigitCharacters(_.get(customer, 'phone', '00000000000'))
            };

            billingAddress['region_code'] = typeof address.state === 'string' ? address.state : '';
            billingAddress = createBillingAddress(billingAddress);
            quote.billingAddress(billingAddress);
        },

        /**
         * Disable submit button
         */
        disableButton: function () {
            // stop any previous shown loaders
            fullScreenLoader.stopLoader(true);
            fullScreenLoader.startLoader();
            $('[data-button="place"]').attr('disabled', 'disabled');
        },

        /**
         * Enable submit button
         */
        enableButton: function () {
            $('[data-button="place"]').removeAttr('disabled');
            fullScreenLoader.stopLoader(true);
        },

        /**
         * Triggers when customer click "Continue to PayPal" button
         */
        payWithPayPal: function () {
            if (additionalValidators.validate(false)) {
                this.checkout.paypal.initAuthFlow();
            }
        },

        /**
         * Get a kebab case formatted string of the component ID (normally with `_`).
         *
         * @return {String|null}
         */
        getKebabCaseId: function () {
            if (this.getId() === null) {
                return null;
            }

            return this.getId().replace('_', '-');
        },

        /**
         * Get the PayPal button placeholder id.
         *
         * @returns {String}
         */
        getPayPalButtonId: function () {
            return this.getId() + '_placeholder';
        },

        /**
         * Get PayPal Pay Later button placeholder id.
         *
         * @returns {String}
         */
        getPayLaterButtonId: function () {
            return this.getId() + '_paylater_placeholder';
        },

        /**
         * Check if Pay Later enabled.
         *
         * @returns {boolean}
         */
        isPayLaterEnabled: function () {
            return _.get(window.checkoutConfig.payment, ['braintree_paypal_paylater', 'isActive'], false);
        },

        /**
         * Check if Pay Later messaging enabled.
         *
         * @returns {boolean}
         */
        isPayLaterMessageEnabled: function () {
            return _.get(window.checkoutConfig.payment, ['braintree_paypal_paylater', 'isMessageActive'], false);
        },

        /**
         * Get the formatted grand total.
         *
         * @return {string}
         */
        getGrandTotalAmount: function () {
            return formatAmount(this.grandTotalAmount);
        },

        /**
         * Get locale
         *
         * @returns {String}
         */
        getLocale: function () {
            return _.get(window.checkoutConfig.payment, ['braintree_paypal', 'locale'], '');
        },

        /**
         * Get PayPal Pay Later Message Layout.
         *
         * @returns {string}
         */
        getMessagingLayout: function () {
            return _.get(window.checkoutConfig.payment, ['braintree_paypal_paylater', 'message', 'layout'], '');
        },

        /**
         * Get PayPal Pay Later Message Logo.
         *
         * @returns {string}
         */
        getMessagingLogo: function () {
            return _.get(window.checkoutConfig.payment, ['braintree_paypal_paylater', 'message', 'logo'], '');
        },

        /**
         * Get PayPal Pay Later Message Logo position.
         *
         * @returns {string}
         */
        getMessagingLogoPosition: function () {
            return _.get(window.checkoutConfig.payment, ['braintree_paypal_paylater', 'message', 'logo_position'], '');
        },

        /**
         * Get PayPal Pay Later Message Text Color.
         *
         * @returns {string}
         */
        getMessagingTextColor: function () {
            return _.get(window.checkoutConfig.payment, ['braintree_paypal_paylater', 'message', 'text_color'], '');
        },

        /**
         * Is Billing Address required from PayPal side.
         *
         * @returns {exports.isRequiredBillingAddress|(function())|boolean|String}
         */
        isRequiredBillingAddress: function () {
            return window.checkoutConfig.payment['braintree_paypal'].isRequiredBillingAddress;
        },

        /**
         * Show error message
         *
         * @param {String} errorMessage
         */
        showError: function (errorMessage) {
            globalMessageList.addErrorMessage({
                message: errorMessage
            });
            fullScreenLoader.stopLoader(true);
        },

        /**
         * Get line items
         *
         * @returns {Array}
         */
        getLineItems: function () {
            let self = this,
                lineItems = [];

            if (this.canSendLineItems()) {
                let giftWrappingItems = 0,
                    giftWrappingOrder = 0,
                    storeCredit = 0,
                    giftCardAccount = 0,
                    giftWrappingPrintedCard = 0,
                    baseDiscountAmount,
                    baseTaxAmount;

                $.each(quote.totals()['total_segments'], function (segmentsKey, segmentsItem) {
                    if (segmentsItem['code'] === 'customerbalance') {
                        storeCredit = formatAmount(Math.abs(segmentsItem['value']).toString());
                    }
                    if (segmentsItem['code'] === 'giftcardaccount') {
                        giftCardAccount = formatAmount(Math.abs(segmentsItem['value']).toString());
                    }
                    if (segmentsItem['code'] === 'giftwrapping') {
                        let extensionAttributes = segmentsItem['extension_attributes'];

                        giftWrappingOrder = extensionAttributes['gw_base_price'];
                        giftWrappingItems = extensionAttributes['gw_items_base_price'];
                        giftWrappingPrintedCard = extensionAttributes['gw_card_base_price'];
                    }
                });

                $.each(quote.getItems(), function (quoteItemKey, quoteItem) {
                    if (quoteItem.parent_item_id !== null || quoteItem.price === 0.0) {
                        return true;
                    }

                    let itemName = replaceUnsupportedCharacters(quoteItem.name),
                        itemSku = replaceUnsupportedCharacters(quoteItem.sku),

                        description = '',
                        itemQty = parseFloat(quoteItem.qty),
                        itemUnitAmount = parseFloat(quoteItem.price),
                        lineItemValues,
                        mappedLineItems;

                    if (itemQty > Math.floor(itemQty) && itemQty < Math.ceil(itemQty)) {
                        description = 'Item quantity is ' + itemQty.toFixed(2)
                            + ' and per unit amount is ' + itemUnitAmount.toFixed(2);
                        itemUnitAmount = parseFloat(itemQty * itemUnitAmount);
                        itemQty = parseFloat('1');
                    }

                    lineItemValues = [
                        itemName,
                        'debit',
                        itemQty.toFixed(2),
                        itemUnitAmount.toFixed(2),
                        itemSku,
                        description
                    ],

                    mappedLineItems = $.map(self.lineItemsArray, function (itemElement, itemIndex) {
                        return [[
                            self.lineItemsArray[itemIndex],
                            lineItemValues[itemIndex]
                        ]];
                    });

                    lineItems[quoteItemKey] = Object.fromEntries(mappedLineItems);
                });

                /**
                 * Adds credit (refund or discount) kind as LineItems for the
                 * PayPal transaction if discount amount is greater than 0(Zero)
                 * as discountAmount lineItem field is not being used by PayPal.
                 *
                 * developer.paypal.com/braintree/docs/reference/response/transaction-line-item/php#discount_amount
                 */
                baseDiscountAmount = formatAmount(Math.abs(quote.totals()['base_discount_amount']).toString());

                if (baseDiscountAmount > 0) {
                    let discountLineItem = {
                        'name': 'Discount',
                        'kind': 'credit',
                        'quantity': 1.00,
                        'unitAmount': baseDiscountAmount
                    };

                    lineItems = $.merge(lineItems, [discountLineItem]);
                }

                /**
                 * Adds shipping as LineItems for the PayPal transaction
                 * if shipping amount is greater than 0(Zero) to manage
                 * the totals with client-side implementation as there is
                 * no any field exist in the client-side implementation
                 * to send the shipping amount to the Braintree.
                 */
                if (quote.totals()['base_shipping_amount'] > 0) {
                    let shippingLineItem = {
                        'name': 'Shipping',
                        'kind': 'debit',
                        'quantity': 1.00,
                        'unitAmount': quote.totals()['base_shipping_amount']
                    };

                    lineItems = $.merge(lineItems, [shippingLineItem]);
                }

                baseTaxAmount = formatAmount(quote.totals()['base_tax_amount']);

                if (baseTaxAmount > 0) {
                    let taxLineItem = {
                        'name': 'Tax',
                        'kind': 'debit',
                        'quantity': 1.00,
                        'unitAmount': baseTaxAmount
                    };

                    lineItems = $.merge(lineItems, [taxLineItem]);
                }

                /**
                 * Adds credit (Store Credit) kind as LineItems for the
                 * PayPal transaction if store credit is greater than 0(Zero)
                 * to manage the totals with client-side implementation
                 */
                if (storeCredit > 0) {
                    let storeCreditItem = {
                        'name': 'Store Credit',
                        'kind': 'credit',
                        'quantity': 1.00,
                        'unitAmount': storeCredit
                    };

                    lineItems = $.merge(lineItems, [storeCreditItem]);
                }

                /**
                 * Adds Gift Wrapping for items as LineItems for the PayPal
                 * transaction if it is greater than 0(Zero) to manage
                 * the totals with client-side implementation
                 */
                if (giftWrappingItems > 0) {
                    let gwItems = {
                        'name': 'Gift Wrapping for Items',
                        'kind': 'debit',
                        'quantity': 1.00,
                        'unitAmount': giftWrappingItems
                    };

                    lineItems = $.merge(lineItems, [gwItems]);
                }

                /**
                 * Adds Gift Wrapping for order as LineItems for the PayPal
                 * transaction if it is greater than 0(Zero) to manage
                 * the totals with client-side implementation
                 */
                if (giftWrappingOrder > 0) {
                    let gwOrderItem = {
                        'name': 'Gift Wrapping for Order',
                        'kind': 'debit',
                        'quantity': 1.00,
                        'unitAmount': giftWrappingOrder
                    };

                    lineItems = $.merge(lineItems, [gwOrderItem]);
                }

                /**
                 * Adds Gift Wrapping Printed Card as LineItems for the PayPal
                 * transaction if it is greater than 0(Zero) to manage
                 * the totals with client-side implementation
                 */
                if (giftWrappingPrintedCard > 0) {
                    let gwPrintedCard = {
                        'name': 'Printed Card',
                        'kind': 'debit',
                        'quantity': 1.00,
                        'unitAmount': giftWrappingPrintedCard
                    };

                    lineItems = $.merge(lineItems, [gwPrintedCard]);
                }

                /**
                 * Adds Gift Cards as credit LineItems for the PayPal
                 * transaction if it is greater than 0(Zero) to manage
                 * the totals with client-side implementation
                 */
                if (giftCardAccount > 0) {
                    let giftCardItem = {
                        'name': 'Gift Cards',
                        'kind': 'credit',
                        'quantity': 1.00,
                        'unitAmount': giftCardAccount
                    };

                    lineItems = $.merge(lineItems, [giftCardItem]);
                }

                if (lineItems.length >= 250) {
                    lineItems = [];
                }
            }
            return lineItems;
        },

        /**
         * Can send line items
         *
         * @returns {Boolean}
         */
        canSendLineItems: function () {
            return window.checkoutConfig.payment['braintree_paypal'].canSendLineItems;
        },

        /**
         * Get the Button ID for the required funding
         *
         * @param {string} funding
         * @return {string}
         */
        getButtonIdByFunding: function (funding) {
            if (funding === 'paylater') {
                return this.getPayLaterButtonId();
            }

            return this.getPayPalButtonId();
        },

        /**
         * Get the label config associated to the PayPal funding source.
         *
         * @param {string} funding
         * @return {string}
         */
        getLabelByFunding: function (funding) {
            return _.get(
                window.checkoutConfig.payment,
                [this.getPaymentMethodCodeByFunding(funding), 'style', 'label'],
                ''
            );
        },

        /**
         * Get the color config associated to the PayPal funding source.
         *
         * @param {string} funding
         * @return {string}
         */
        getColorByFunding: function (funding) {
            return _.get(
                window.checkoutConfig.payment,
                [this.getPaymentMethodCodeByFunding(funding), 'style', 'color'],
                ''
            );
        },

        /**
         * Get the shape config associated to the PayPal funding source.
         *
         * @param {string} funding
         * @return {string}
         */
        getShapeByFunding: function (funding) {
            return _.get(
                window.checkoutConfig.payment,
                [this.getPaymentMethodCodeByFunding(funding), 'style', 'shape'],
                ''
            );
        },

        /**
         * Get the payment method code related to the PayPal funding source.
         *
         * @param {string} funding
         * @return {string}
         */
        getPaymentMethodCodeByFunding: function (funding) {
            if (funding === 'paylater') {
                return 'braintree_paypal_paylater';
            }

            return 'braintree_paypal';
        }
    });
});
