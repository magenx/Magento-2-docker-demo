<?php
/*************************************************************************
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2023 Adobe
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
 **************************************************************************/
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Model\Api\Data;

use Magento\Framework\DataObject;
use Magento\PaymentServicesPaypal\Model\Api\VaultConfigManagement;
use Magento\PaymentServicesPaypal\Model\Vault\CreditCardConfigProvider;

class VaultCreditCardConfig extends DataObject
{
    /**
     * @return string
     */
    public function getThreeDS(): string
    {
        return $this->getData(CreditCardConfigProvider::THREE_DS);
    }

    /**
     * @param string $threeDS
     * @return VaultCreditCardConfig
     */
    public function setThreeDS(string $threeDS): VaultCreditCardConfig
    {
        return $this->setData(CreditCardConfigProvider::THREE_DS, $threeDS);
    }

    /**
     * @return bool
     */
    public function hasIsVaultEnabled(): bool
    {
        return $this->getData(CreditCardConfigProvider::VAULT_ENABLED);
    }

    /**
     * @param bool $hasIsVaultEnabled
     * @return VaultCreditCardConfig
     */
    public function setHasIsVaultEnabled(bool $hasIsVaultEnabled): VaultCreditCardConfig
    {
        return $this->setData(CreditCardConfigProvider::VAULT_ENABLED, $hasIsVaultEnabled);
    }

    /**
     * @return \Magento\PaymentServicesPaypal\Api\Data\PaymentConfigSdkParamsInterface[]
     */
    public function getSdkParams()
    {
        return $this->getData(VaultConfigManagement::SDK_PARAMS);
    }

    /**
     * @param \Magento\PaymentServicesPaypal\Api\Data\PaymentConfigSdkParamsInterface[] $sdkParams
     * @return VaultCreditCardConfig
     */
    public function setSdkParams(array $sdkParams)
    {
        return $this->setData(VaultConfigManagement::SDK_PARAMS, $sdkParams);
    }
}
