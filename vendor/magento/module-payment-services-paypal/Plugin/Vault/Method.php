<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Plugin\Vault;

use Magento\PaymentServicesPaypal\Model\HostedFieldsConfigProvider;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Vault\Model\Method\Vault as VaultMethod;
use Magento\Vault\Model\PaymentTokenManagement;

class Method
{
    private const ADMIN_VAULT_ENABLED = 'active_admin';

    /**
     * @var PaymentTokenManagement
     */
    private $tokenManagement;

    /**
     * @param PaymentTokenManagement $tokenManagement
     */
    public function __construct(
        PaymentTokenManagement $tokenManagement
    ) {
        $this->tokenManagement = $tokenManagement;
    }

    /**
     * Determine whether to render vaulted tokens for admin checkout
     *
     * @param VaultMethod $subject
     * @param bool $result
     * @return bool|false
     */
    public function afterCanUseInternal(VaultMethod $subject, bool $result): bool
    {
        if ($subject->getCode() === HostedFieldsConfigProvider::CC_VAULT_CODE
        && !$subject->getConfigData(self::ADMIN_VAULT_ENABLED)) {
            return false;
        }
        return $result;
    }

    /**
     * Hide stored cards payment option on admin checkout page when the customer is new or doesn't have any stored cards
     *
     * @param VaultMethod $subject
     * @param bool $result
     * @param CartInterface $quote
     * @return bool
     */
    public function afterIsAvailable(VaultMethod $subject, bool $result, CartInterface $quote): bool
    {
        if ($subject->getCode() === HostedFieldsConfigProvider::CC_VAULT_CODE) {
            if ($customerId = $quote->getCustomerId()) {
                return !empty($this->tokenManagement->getVisibleAvailableTokens($customerId));
            }
            return false;
        }
        return $result;
    }
}
