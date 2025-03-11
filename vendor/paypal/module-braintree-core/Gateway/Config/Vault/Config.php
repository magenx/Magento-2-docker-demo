<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Gateway\Config\Vault;

use Magento\Payment\Gateway\Config\Config as GatewayConfig;
use PayPal\Braintree\Model\StoreConfigResolver;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;

class Config extends GatewayConfig
{
    private const KEY_ACTIVE = 'active';
    private const KEY_CVV = 'cvv';

    /**
     * @var StoreConfigResolver
     */
    private StoreConfigResolver $storeConfigResolver;

    /**
     * Config constructor.
     *
     * @param StoreConfigResolver $storeConfigResolver
     * @param ScopeConfigInterface $scopeConfig
     * @param string|null $methodCode
     * @param string $pathPattern
     */
    public function __construct(
        StoreConfigResolver $storeConfigResolver,
        ScopeConfigInterface $scopeConfig,
        $methodCode = null,
        string $pathPattern = GatewayConfig::DEFAULT_PATH_PATTERN
    ) {
        $this->storeConfigResolver = $storeConfigResolver;
        parent::__construct($scopeConfig, $methodCode, $pathPattern);
    }

    /**
     * Is Braintree Vault (cards) active?
     *
     * @param int|null $storeId
     * @return bool
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function isActive(?int $storeId = null): bool
    {
        return (bool) $this->getValue(self::KEY_ACTIVE, $storeId ?? $this->storeConfigResolver->getStoreId());
    }

    /**
     * Is CVV verification for vaulted card enabled?
     *
     * @return bool
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function isCvvVerifyEnabled(): bool
    {
        return (bool) $this->getValue(
            self::KEY_CVV,
            $this->storeConfigResolver->getStoreId()
        );
    }
}
