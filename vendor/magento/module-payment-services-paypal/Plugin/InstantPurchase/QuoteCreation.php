<?php
/************************************************************************
 *
 * Copyright 2024 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Plugin\InstantPurchase;

use Magento\InstantPurchase\Model\QuoteManagement\QuoteCreation as InstantPurchaseQuoteCreation;
use Magento\PaymentServicesPaypal\Model\InstantPurchase\PaymentMethodResolver;
use Magento\Quote\Model\Quote;
use Magento\PaymentServicesPaypal\Model\HostedFieldsConfigProvider;

class QuoteCreation
{
    private PaymentMethodResolver $paymentMethodResolver;

    /**
     * @param PaymentMethodResolver $paymentMethodResolver
     */
    public function __construct(PaymentMethodResolver $paymentMethodResolver)
    {
        $this->paymentMethodResolver = $paymentMethodResolver;
    }

    public function afterCreateQuote(InstantPurchaseQuoteCreation $subject, Quote $quote): Quote
    {
        if ($this->paymentMethodResolver->resolve() == HostedFieldsConfigProvider::CODE) {
            $quote->getShippingAddress()->setCollectShippingRates(true);
            $quote->getBillingAddress()->setCollectShippingRates(true);
        }
        return $quote;
    }
}
