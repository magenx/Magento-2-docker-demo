/**
 * Braintree Google Pay button
 **/
define(
    [
        'uiComponent',
        'underscore',
        'knockout',
        'jquery',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_CheckoutAgreements/js/view/checkout-agreements',
        'PayPal_Braintree/js/googlepay/model/parsed-response',
        'PayPal_Braintree/js/googlepay/model/payment-data',
        'PayPal_Braintree/js/view/payment/adapter',
        'braintree',
        'braintreeDataCollector',
        'braintreeGooglePay',
        'mage/translate',
        'googlePayLibrary'
    ],
    function (
        Component,
        _,
        ko,
        $,
        additionalValidators,
        checkoutAgreements,
        parsedResponseModel,
        paymentDataModel,
        braintreeMainAdapter,
        braintree,
        dataCollector,
        googlePay,
        $t
    ) {
        'use strict';

        return {
            init: function (element, context) {

                // No element or context
                if (!element || !context) {
                    return;
                }

                // Context must implement these methods
                if (typeof context.getClientToken !== 'function') {
                    console.error(
                        'Braintree GooglePay Context passed does not provide a getClientToken method',
                        context
                    );
                    return;
                }
                if (typeof context.getPaymentRequest !== 'function') {
                    console.error(
                        'Braintree GooglePay Context passed does not provide a getPaymentRequest method',
                        context
                    );
                    return;
                }
                if (typeof context.startPlaceOrder !== 'function') {
                    console.error(
                        'Braintree GooglePay Context passed does not provide a startPlaceOrder method',
                        context
                    );
                    return;
                }

                // init google pay object
                let paymentsClient = new window.google.payments.api.PaymentsClient({
                        environment: context.getEnvironment()
                    }),

                    // Create a button within the KO element, as Google Pay can only be instantiated through
                    // a valid on click event (ko onclick bind interferes with this).
                    button = document.createElement('button'),
                    color = context.getBtnColor() === 1 ? 'black' : 'white';

                button.className = 'braintree-googlepay-button long ' + color;
                button.title = $t('Buy with Google Pay');

                // init braintree api
                braintree.create({
                    authorization: context.getClientToken()
                }, function (clientErr, clientInstance) {
                    this.initGooglePay(clientErr, clientInstance, paymentsClient, button, element, context);
                }.bind(this));
            },

            initGooglePay: function (clientErr, clientInstance, paymentsClient, button, element, context) {
                if (clientErr) {
                    console.error('Error creating client:', clientErr);
                    return;
                }
                dataCollector.create({
                    client: clientInstance
                }, function (dataCollectorErr, dataCollectorInstance) {
                    if (dataCollectorErr) {
                        return;
                    }
                    googlePay.create({
                        client: clientInstance,
                        googlePayVersion: 2
                    }, function (googlePayErr, googlePaymentInstance) {
                        this.render(
                            clientInstance,
                            googlePayErr,
                            googlePaymentInstance,
                            dataCollectorInstance,
                            paymentsClient,
                            button,
                            element,
                            context
                        );
                    }.bind(this));
                }.bind(this));
            },

            render: function (clientInstance, googlePayErr, googlePaymentInstance,
                dataCollectorInstance, paymentsClient, button, element, context) {
                // No instance
                if (googlePayErr) {
                    console.error('Braintree GooglePay Error creating googlePayInstance:', googlePayErr);
                    return;
                }

                /**
                 * Assign existing client instance to braintree adapter to use existing one
                 * otherwise new client instance needs to be created for 3DS verification
                 * which calls the Braintree Client & Device Collector SDK twice and
                 * makes process slow.
                 */
                braintreeMainAdapter.clientInstance = clientInstance;
                braintreeMainAdapter.deviceData = dataCollectorInstance.deviceData;

                paymentsClient.isReadyToPay({
                    apiVersion: 2,
                    apiVersionMinor: 0,
                    allowedPaymentMethods: googlePaymentInstance.createPaymentDataRequest().allowedPaymentMethods
                }).then(function (response) {
                    if (response.result) {
                        button.addEventListener('click', function (event) {
                            let agreements = checkoutAgreements().agreements,
                                shouldDisableActions = false;

                            _.each(agreements, function (item) {
                                if (checkoutAgreements().isAgreementRequired(item)) {
                                    let inputId = '#agreement_braintree_googlepay_' + item.agreementId,
                                        inputEl = document.querySelector(inputId);

                                    if (inputEl !== null && !inputEl.checked) {
                                        shouldDisableActions = true;
                                    }

                                }
                            });

                            if ($(button).parents('#braintree-googlepay-express-payment').length === 0
                                && !additionalValidators.validate(false)) {
                                event.preventDefault();
                                return false;
                            }

                            if (!shouldDisableActions) {
                                event.preventDefault();
                                $('body').loader('show');

                                let paymentDataRequest = googlePaymentInstance.createPaymentDataRequest(
                                    context.getPaymentRequest()
                                );

                                paymentsClient.loadPaymentData(paymentDataRequest).then(function (paymentData) {
                                    // Persist the paymentData (shipping address etc.)
                                    paymentDataModel.setPaymentMethodData(_.get(
                                        paymentData,
                                        'paymentMethodData',
                                        null
                                    ));
                                    paymentDataModel.setEmail(_.get(paymentData, 'email', ''));
                                    paymentDataModel.setShippingAddress(_.get(
                                        paymentData,
                                        'shippingAddress',
                                        null
                                    ));
                                    // Return the braintree nonce promise
                                    return googlePaymentInstance.parseResponse(paymentData);
                                }).then(function (result) {
                                    parsedResponseModel.setNonce(result.nonce);
                                    parsedResponseModel.setIsNetworkTokenized(_.get(
                                        result,
                                        ['details', 'isNetworkTokenized'],
                                        false
                                    ));
                                    parsedResponseModel.setBin(_.get(
                                        result,
                                        ['details', 'bin'],
                                        null
                                    ));

                                    context.startPlaceOrder(dataCollectorInstance.deviceData);
                                    $('body').loader('hide');
                                }).catch(function (err) {
                                    // Handle errors
                                    // err = {statusCode: "CANCELED"}
                                    console.error(err);
                                    parsedResponseModel.resetDefaultData();
                                    $('body').loader('hide');
                                });
                            }
                        });
                        element.appendChild(button);
                    }
                }).catch(function (err) {
                    console.error(err);
                    $('body').loader('hide');
                });
            }
        };
    }
);
