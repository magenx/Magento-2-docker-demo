/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
], function (Component, rendererList) {
    'use strict';

    rendererList.push({
        type: 'payment_services_paypal_hosted_fields',
        component: 'Magento_PaymentServicesPaypal/js/view/payment/method-renderer/hosted-fields'
    }, {
        type: 'payment_services_paypal_smart_buttons',
        component: 'Magento_PaymentServicesPaypal/js/view/payment/method-renderer/smart-buttons'
    }, {
        type: 'payment_services_paypal_apple_pay',
        component: 'Magento_PaymentServicesPaypal/js/view/payment/method-renderer/apple-pay'
    }, {
        type: 'payment_services_paypal_google_pay',
        component: 'Magento_PaymentServicesPaypal/js/view/payment/method-renderer/google-pay'
    });

    return Component.extend({});
});
