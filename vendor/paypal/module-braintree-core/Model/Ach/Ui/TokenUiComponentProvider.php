<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Model\Ach\Ui;

use InvalidArgumentException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterface;
use Magento\Vault\Model\Ui\TokenUiComponentProviderInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterfaceFactory;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;

class TokenUiComponentProvider implements TokenUiComponentProviderInterface
{
    /**
     * @var TokenUiComponentInterfaceFactory
     */
    private TokenUiComponentInterfaceFactory $componentFactory;

    /**
     * @var UrlInterface
     */
    private UrlInterface $urlBuilder;

    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param TokenUiComponentInterfaceFactory $componentFactory
     * @param UrlInterface $urlBuilder
     * @param SerializerInterface $serializer
     * @param LoggerInterface $logger
     */
    public function __construct(
        TokenUiComponentInterfaceFactory $componentFactory,
        UrlInterface $urlBuilder,
        SerializerInterface $serializer,
        LoggerInterface $logger
    ) {
        $this->componentFactory = $componentFactory;
        $this->urlBuilder = $urlBuilder;
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    /**
     * Get UI component for token
     *
     * @param PaymentTokenInterface $paymentToken
     * @return TokenUiComponentInterface
     */
    public function getComponentForToken(PaymentTokenInterface $paymentToken): TokenUiComponentInterface
    {
        try {
            $jsonDetails = $this->serializer->unserialize($paymentToken->getTokenDetails() ?: '{}');

            // Unset any details we do not currently display in the frontend.
            unset(
                $jsonDetails['customerId'],
                $jsonDetails['bankName'],
                $jsonDetails['accountHolderName'],
                $jsonDetails['accountType']
            );
        } catch (InvalidArgumentException $ex) {
            $this->logger->error('Failed to unserialize Ach Direct Debit token with error: ' . $ex->getMessage(), [
                'payment_token_entity_id' => $paymentToken->getEntityId()
            ]);

            $jsonDetails = null;
        }

        return $this->componentFactory->create([
            'config' => [
                'code' => ConfigProvider::METHOD_VAULT_CODE,
                'nonceUrl' => $this->getNonceRetrieveUrl(),
                TokenUiComponentProviderInterface::COMPONENT_DETAILS => $jsonDetails,
                TokenUiComponentProviderInterface::COMPONENT_PUBLIC_HASH => $paymentToken->getPublicHash()
            ],
            'name' => 'PayPal_Braintree/js/view/payment/method-renderer/ach-vault'
        ]);
    }

    /**
     * Get url to retrieve payment method nonce
     *
     * @return string
     */
    private function getNonceRetrieveUrl(): string
    {
        return $this->urlBuilder->getUrl(
            'braintree/payment/getNonce',
            ['_secure' => true]
        );
    }
}
