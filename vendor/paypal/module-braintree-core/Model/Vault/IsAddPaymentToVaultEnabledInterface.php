<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Model\Vault;

interface IsAddPaymentToVaultEnabledInterface
{
    /**
     * Is adding payment method to vault enabled.
     *
     * @param string $paymentMethod
     * @param int|null $storeId
     * @return bool
     */
    public function execute(string $paymentMethod, int $storeId = null): bool;
}
