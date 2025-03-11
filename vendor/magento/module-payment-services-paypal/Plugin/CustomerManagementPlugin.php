<?php

/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2025 Adobe
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
 */

declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Plugin;

use Magento\Quote\Model\CustomerManagement;
use Magento\Quote\Model\Quote as QuoteEntity;

/**
 * Skip billing address validation for Payments Services payment method
 */
class CustomerManagementPlugin
{
    /**
     * Around plugin for the validateAddresses method
     * Skip address validation for guest customers when using Payments Services payment method
     * TODO: Add location to quote so we can skip validation for all locations except checkout
     *
     * @param CustomerManagement $subject
     * @param \Closure $proceed
     * @param QuoteEntity $quote
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundValidateAddresses(CustomerManagement $subject, \Closure $proceed, QuoteEntity $quote): void
    {
        if ($quote->getCustomerIsGuest() && in_array($quote->getPayment()->getMethod(), $this->getPaymentMethods())) {
            return;
        }
        $proceed($quote);
    }

    /**
     * @return string[]
     */
    private function getPaymentMethods(): array
    {
        return [
            'payment_services_paypal_smart_buttons',
            'payment_services_paypal_apple_pay',
            'payment_services_paypal_google_pay',
        ];
    }
}
