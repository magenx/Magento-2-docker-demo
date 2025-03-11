/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

var config = {
    map: {
        '*': {
            'Magento_Vault/js/view/payment/vault': 'Magento_PaymentServicesPaypal/js/view/payment/vault'
        }
    },
    config: {
        mixins: {
            'Magento_Checkout/js/model/payment-service': {
                'Magento_PaymentServicesPaypal/js/model/payment-service-mixin': true
            }
        }
    }
};
