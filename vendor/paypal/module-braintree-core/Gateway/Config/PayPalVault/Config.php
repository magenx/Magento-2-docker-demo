<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Gateway\Config\PayPalVault;

use Magento\Payment\Gateway\Config\Config as MagentoPaymentGatewayConfig;
use PayPal\Braintree\Gateway\Config\PayPal\Config as PayPalGatewayConfig;
use PayPal\Braintree\Model\StoreConfigResolver;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;

class Config extends MagentoPaymentGatewayConfig
{
    private const KEY_ACTIVE = 'active';

    /**
     * @var StoreConfigResolver
     */
    private StoreConfigResolver $storeConfigResolver;

    /**
     * @var PayPalGatewayConfig
     */
    private PayPalGatewayConfig $payPalConfig;

    /**
     * Config constructor.
     *
     * @param StoreConfigResolver $storeConfigResolver
     * @param PayPalGatewayConfig $payPalConfig
     * @param ScopeConfigInterface $scopeConfig
     * @param string|null $methodCode
     * @param string $pathPattern
     */
    public function __construct(
        StoreConfigResolver $storeConfigResolver,
        PayPalGatewayConfig $payPalConfig,
        ScopeConfigInterface $scopeConfig,
        ?string $methodCode = null,
        string $pathPattern = self::DEFAULT_PATH_PATTERN
    ) {
        parent::__construct($scopeConfig, $methodCode, $pathPattern);

        $this->storeConfigResolver = $storeConfigResolver;
        $this->payPalConfig = $payPalConfig;
    }

    /**
     * Validate whether PayPal Vault is active.
     *
     * Should be active if both PayPal is active as a payment method & also PayPal vault config is set to active.
     *
     * @param int|null $storeId
     * @return bool
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function isActive(int $storeId = null): bool
    {
        if ($storeId === null) {
            $storeId = $this->storeConfigResolver->getStoreId();
        }

        // Type casting if fetched from resolved to avoid some observed scenarios when it's not int.
        if ($storeId !== null) {
            $storeId = (int) $storeId;
        }

        return $this->payPalConfig->isActive($storeId) && (bool) $this->getValue(self::KEY_ACTIVE, $storeId) === true;
    }
}
