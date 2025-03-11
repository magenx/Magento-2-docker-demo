<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Model\Vault\PaymentToken;

use Magento\Vault\Api\Data\PaymentTokenInterface;

interface GeneratePublicHashInterface
{
    /**
     * Generate a public hash key.
     *
     * @param PaymentTokenInterface $paymentToken
     * @return string
     */
    public function execute(PaymentTokenInterface $paymentToken): string;
}
