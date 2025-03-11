<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Model\Vault\PaymentToken;

use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;

class Save implements SaveInterface
{
    /**
     * @var PaymentTokenManagementInterface
     */
    private PaymentTokenManagementInterface $paymentTokenManagement;

    /**
     * @var PaymentTokenRepositoryInterface
     */
    private PaymentTokenRepositoryInterface $paymentTokenRepository;

    /**
     * @var EncryptorInterface
     */
    private EncryptorInterface $encryptor;

    /**
     * @param PaymentTokenManagementInterface $paymentTokenManagement
     * @param PaymentTokenRepositoryInterface $paymentTokenRepository
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        PaymentTokenManagementInterface $paymentTokenManagement,
        PaymentTokenRepositoryInterface $paymentTokenRepository,
        EncryptorInterface $encryptor
    ) {
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->encryptor = $encryptor;
    }

    /**
     * Save a payment token, checking for duplicates.
     *
     * Copy logic from core Payment Token Management.
     *
     * @param PaymentTokenInterface $paymentToken
     * @return bool
     */
    public function execute(PaymentTokenInterface $paymentToken): bool
    {
        $existingToken = $this->paymentTokenManagement->getByPublicHash(
            $paymentToken->getPublicHash(),
            $paymentToken->getCustomerId()
        );

        // If token found, save & return success.
        if ($existingToken === null) {
            $this->paymentTokenRepository->save($paymentToken);

            return true;
        }

        if ($paymentToken->getIsVisible() || $existingToken->getIsVisible()) {
            $paymentToken->setEntityId($existingToken->getEntityId());
            $paymentToken->setIsVisible(true);

            $this->paymentTokenRepository->save($paymentToken);

            return true;
        }

        if ($paymentToken->getIsVisible() === $existingToken->getIsVisible()) {
            $paymentToken->setEntityId($existingToken->getEntityId());

            $this->paymentTokenRepository->save($paymentToken);

            return true;
        }

        // Last case, generate new hash & save new token.
        $hash = $this->encryptor->getHash($paymentToken->getPublicHash() . $paymentToken->getGatewayToken());

        $paymentToken->setPublicHash($hash);

        $this->paymentTokenRepository->save($paymentToken);

        return true;
    }
}
