<?php
/*************************************************************************
 * ADOBE CONFIDENTIAL
 * ___________________
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
 **************************************************************************/
declare(strict_types=1);

namespace Magento\PaymentServicesPaypalGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\PaymentServicesPaypal\Api\VaultConfigManagementInterface;
use Magento\PaymentServicesPaypal\Model\Vault\CreditCardConfigProvider;

class GetVaultConfig implements ResolverInterface
{
    private const VAULT_METHODS = [
        CreditCardConfigProvider::CODE
    ];

    /**
     * @var VaultConfigManagementInterface
     */
    private VaultConfigManagementInterface $vaultConfigManagement;

    /**
     * @param VaultConfigManagementInterface $vaultConfigManagement
     */
    public function __construct(
        VaultConfigManagementInterface $vaultConfigManagement
    ) {
        $this->vaultConfigManagement = $vaultConfigManagement;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null
    ) {
        $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();

        $result = [];

        foreach ($info->getFieldSelection() as $method => $requested) {
            if ($requested && in_array($method, self::VAULT_METHODS, true)) {
                $result[$method] = $this->vaultConfigManagement->getConfig($method, $storeId);
            }
        }

        return $result;
    }
}
