<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Model\Ach\Ui\Adminhtml;

use InvalidArgumentException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\Element\Template;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterface;
use Magento\Vault\Model\Ui\TokenUiComponentProviderInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterfaceFactory;
use Magento\Framework\UrlInterface;
use PayPal\Braintree\Gateway\Config\Ach\Config;
use PayPal\Braintree\Model\Ach\Ui\ConfigProvider;
use Psr\Log\LoggerInterface;

class TokenUiComponentProvider implements TokenUiComponentProviderInterface
{
    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    /**
     * @var TokenUiComponentInterfaceFactory
     */
    private TokenUiComponentInterfaceFactory $componentFactory;

    /**
     * @var UrlInterface
     */
    private UrlInterface $urlBuilder;

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param SerializerInterface $serializer
     * @param TokenUiComponentInterfaceFactory $componentFactory
     * @param UrlInterface $urlBuilder
     * @param Config $config
     * @param LoggerInterface $logger
     * @return void
     */
    public function __construct(
        SerializerInterface $serializer,
        TokenUiComponentInterfaceFactory $componentFactory,
        UrlInterface $urlBuilder,
        Config $config,
        LoggerInterface $logger
    ) {
        $this->serializer = $serializer;
        $this->componentFactory = $componentFactory;
        $this->urlBuilder = $urlBuilder;
        $this->config = $config;
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
            $this->logger->error('Failed to un-serialize Ach Direct Debit token with error: ' . $ex->getMessage(), [
                'payment_token_entity_id' => $paymentToken->getEntityId(),
                'fqn' => TokenUiComponentProvider::class
            ]);

            $jsonDetails = null;
        }

        $config = [
            'code' => ConfigProvider::METHOD_VAULT_CODE,
            'nonceUrl' => $this->getNonceRetrieveUrl(),
            'icon' => $this->config->getAchIcon(),
            TokenUiComponentProviderInterface::COMPONENT_DETAILS => $jsonDetails,
            TokenUiComponentProviderInterface::COMPONENT_PUBLIC_HASH => $paymentToken->getPublicHash(),
            'template' => 'PayPal_Braintree::form/ach/vault.phtml'
        ];

        return $this->componentFactory->create([
            'config' => $config,
            'name' => Template::class
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
