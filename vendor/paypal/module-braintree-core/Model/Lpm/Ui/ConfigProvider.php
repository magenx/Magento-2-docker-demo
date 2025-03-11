<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Model\Lpm\Ui;

use PayPal\Braintree\Model\Lpm\Config;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;

class ConfigProvider implements ConfigProviderInterface
{
    public const METHOD_CODE = 'braintree_local_payment';

    /**
     * @var Config
     */
    private Config $config;

    /**
     * ConfigProvider constructor.
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Get config
     *
     * @return array
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getConfig(): array
    {
        if (!$this->config->isActive()) {
            return [];
        }

        return [
            'payment' => [
                self::METHOD_CODE => [
                    'allowedMethods' => $this->config->getAllowedMethods(),
                    'clientToken' => $this->config->getClientToken(),
                    'merchantAccountId' => $this->config->getMerchantAccountId(),
                    'paymentIcons' => $this->config->getPaymentIcons(),
                    'title' => $this->config->getTitle(),
                    'fallbackUrl' => $this->config->getFallbackUrl(),
                    'fallbackButtonText' => $this->config->getFallbackButtonText()
                ]
            ]
        ];
    }
}
