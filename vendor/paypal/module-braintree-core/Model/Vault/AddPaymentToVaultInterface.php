<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Model\Vault;

use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use PayPal\Braintree\Api\Data\PaymentInterface;

interface AddPaymentToVaultInterface
{
    /**
     * Vault a Payment nonce for a customer.
     *
     * Billing address is optional but advised for Card vaulting.
     *
     * @param CustomerInterface $customer
     * @param PaymentInterface $payment
     * @param AddressInterface|null $address
     * @param int|null $storeId
     * @return bool
     */
    public function execute(
        CustomerInterface $customer,
        PaymentInterface $payment,
        ?AddressInterface $address = null,
        ?int $storeId = null
    ): bool;
}
