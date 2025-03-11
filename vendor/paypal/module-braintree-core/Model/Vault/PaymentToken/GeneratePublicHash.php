<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Model\Vault\PaymentToken;

use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;

class GeneratePublicHash implements GeneratePublicHashInterface
{
    /**
     * @var EncryptorInterface
     */
    private EncryptorInterface $encryptor;

    /**
     * @param EncryptorInterface $encryptor
     */
    public function __construct(EncryptorInterface $encryptor)
    {
        $this->encryptor = $encryptor;
    }

    /**
     * Generate a public hash key.
     *
     * Follow core vault payment logic to generate hash key.
     *
     * @param PaymentTokenInterface $paymentToken
     * @return string
     */
    public function execute(PaymentTokenInterface $paymentToken): string
    {
        $hashKey = $paymentToken->getGatewayToken();

        if ($paymentToken->getCustomerId() !== null) {
            $hashKey = $paymentToken->getCustomerId();
        }

        return $this->encryptor->hash(
            $hashKey
            . $paymentToken->getPaymentMethodCode()
            . $paymentToken->getType()
            . $paymentToken->getTokenDetails()
        );
    }
}
