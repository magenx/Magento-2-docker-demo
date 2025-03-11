<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Model\Ui\Adminhtml;

use Magento\PaymentServicesPaypal\Model\Ui\ConfigProvider;
use Magento\Framework\UrlInterface;
use Magento\PaymentServicesPaypal\Block\Adminhtml\Form\AdminVault;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\Ui\TokenUiComponentProviderInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterfaceFactory;

/**
 * Class TokenProvider
 */
class TokenUiComponentProvider implements TokenUiComponentProviderInterface
{
    private const CREATE_ORDER_URL = 'paymentservicespaypal/order/create';

    /**
     * @var TokenUiComponentInterfaceFactory
     */
    private $componentFactory;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * Constructor
     *
     * @param TokenUiComponentInterfaceFactory $componentFactory
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        TokenUiComponentInterfaceFactory $componentFactory,
        UrlInterface $urlBuilder
    ) {
        $this->componentFactory = $componentFactory;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Build admin vault token components with custom configurations
     *
     * @param PaymentTokenInterface $paymentToken
     * @return \Magento\Vault\Model\Ui\TokenUiComponentInterface
     */
    public function getComponentForToken(PaymentTokenInterface $paymentToken)
    {
        $jsonDetails = json_decode($paymentToken->getTokenDetails() ?: '{}', true);
        $component = $this->componentFactory->create(
            [
                'config' => [
                    'code' => ConfigProvider::CC_VAULT_CODE,
                    'createOrderUrl' => $this->urlBuilder->getUrl(self::CREATE_ORDER_URL),
                    TokenUiComponentProviderInterface::COMPONENT_DETAILS => $jsonDetails,
                    TokenUiComponentProviderInterface::COMPONENT_PUBLIC_HASH => $paymentToken->getPublicHash(),
                    'template' => 'Magento_PaymentServicesPaypal::form/vault.phtml'
                ],
                'name' => AdminVault::class
            ]
        );

        return $component;
    }
}
