<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Model\Vault\PaymentToken;

use Magento\Vault\Api\Data\PaymentTokenInterface;

interface SaveInterface
{
    /**
     * Save a payment token
     *
     * @param PaymentTokenInterface $paymentToken
     * @return bool
     */
    public function execute(PaymentTokenInterface $paymentToken): bool;
}
