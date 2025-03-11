<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Model\Venmo\Ui\Adminhtml;

use InvalidArgumentException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\Element\Template;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterface;
use Magento\Vault\Model\Ui\TokenUiComponentProviderInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterfaceFactory;
use Magento\Framework\UrlInterface;
use PayPal\Braintree\Model\Venmo\Ui\ConfigProvider;
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
     * @var ConfigProvider
     */
    private ConfigProvider $configProvider;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param SerializerInterface $serializer
     * @param TokenUiComponentInterfaceFactory $componentFactory
     * @param UrlInterface $urlBuilder
     * @param ConfigProvider $configProvider
     * @param LoggerInterface $logger
     * @return void
     */
    public function __construct(
        SerializerInterface $serializer,
        TokenUiComponentInterfaceFactory $componentFactory,
        UrlInterface $urlBuilder,
        ConfigProvider $configProvider,
        LoggerInterface $logger
    ) {
        $this->serializer = $serializer;
        $this->componentFactory = $componentFactory;
        $this->urlBuilder = $urlBuilder;
        $this->configProvider = $configProvider;
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
        } catch (InvalidArgumentException $ex) {
            $this->logger->error('Failed to unserialize Venmo token with error: ' . $ex->getMessage(), [
                'payment_token_entity_id' => $paymentToken->getEntityId()
            ]);

            $jsonDetails = null;
        }

        $icons = [];

        try {
            $icons = $this->configProvider->getIcons();
        } catch (LocalizedException $ex) {
            $this->logger->error('Failed to get Venmo icons with error: ' . $ex->getMessage());
        }

        $config = [
            'code' => ConfigProvider::METHOD_VAULT_CODE,
            'nonceUrl' => $this->getNonceRetrieveUrl(),
            TokenUiComponentProviderInterface::COMPONENT_DETAILS => $jsonDetails,
            TokenUiComponentProviderInterface::COMPONENT_PUBLIC_HASH => $paymentToken->getPublicHash(),
            'template' => 'PayPal_Braintree::form/venmo/vault.phtml'
        ];

        if (!empty($icons)) {
            $config['icons'] = $icons;
        }

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
