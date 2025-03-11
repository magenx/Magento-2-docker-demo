<?php
/************************************************************************
 *
 * Copyright 2024 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Model\Vault;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\PaymentServicesPaypal\Model\Api\Data\VaultCreditCardConfig;
use Magento\PaymentServicesPaypal\Model\Config;

class CreditCardConfigProvider implements ConfigProviderInterface
{
    public const CODE = 'credit_card';

    public const THREE_DS = 'three_ds';
    public const VAULT_ENABLED = 'is_vault_enabled';

    /**
     * @var Config
     */
    private $config;

    /**
     * @param Config $config
     */
    public function __construct(
        Config $config,
    ) {
        $this->config = $config;
    }

    /**
     * @inheritdoc
     *
     * @throws NoSuchEntityException
     */
    public function getConfig(?int $store): VaultCreditCardConfig
    {
        $config = new VaultCreditCardConfig();
        $config->setThreeDS($this->config->getThreeDS($store));
        $config->setHasIsVaultEnabled($this->config->isVaultEnabled($store));

        return $config;
    }
}
