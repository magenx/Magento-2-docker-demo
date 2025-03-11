<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Model\Ach\Ui;

use PayPal\Braintree\Gateway\Config\Ach\Config;
use PayPal\Braintree\Gateway\Config\Config as BraintreeConfig;
use PayPal\Braintree\Gateway\Request\PaymentDataBuilder;
use PayPal\Braintree\Model\Adapter\BraintreeAdapter;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;

class ConfigProvider implements ConfigProviderInterface
{
    public const METHOD_CODE = 'braintree_ach_direct_debit';
    public const METHOD_VAULT_CODE = 'braintree_ach_direct_debit_vault';
    public const CONFIG_MERCHANT_COUNTRY = 'paypal/general/merchant_country';
    public const CONFIG_STORE_NAME = 'general/store_information/name';
    public const CONFIG_STORE_URL = 'web/unsecure/base_url';
    public const ALLOWED_MERCHANT_COUNTRIES = ['US'];
    public const METHOD_KEY_ACTIVE = 'payment/braintree_ach_direct_debit/active';

    /**
     * @var BraintreeAdapter $adapter
     */
    private BraintreeAdapter $adapter;

    /**
     * @var BraintreeConfig $braintreeConfig
     */
    private BraintreeConfig $braintreeConfig;

    /**
     * @var ScopeConfigInterface $scopeConfig
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var string $clientToken
     */
    private string $clientToken = '';

    /**
     * ConfigProvider constructor.
     *
     * @param BraintreeAdapter $adapter
     * @param BraintreeConfig $braintreeConfig
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $config
     */
    public function __construct(
        BraintreeAdapter $adapter,
        BraintreeConfig $braintreeConfig,
        ScopeConfigInterface $scopeConfig,
        Config $config
    ) {
        $this->adapter = $adapter;
        $this->braintreeConfig = $braintreeConfig;
        $this->scopeConfig = $scopeConfig;
        $this->config = $config;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getConfig(): array
    {
        if (!$this->isEnabled()) {
            return [];
        }

        return [
            'payment' => [
                self::METHOD_CODE => [
                    'isActive' => $this->isActive(),
                    'clientToken' => $this->getClientToken(),
                    'storeName' => $this->getStoreName(),
                    'paymentIcon' => $this->config->getAchIcon(),
                    'vaultCode' => self::METHOD_VAULT_CODE,
                ]
            ]
        ];
    }

    /**
     * Get Payment configuration status
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::METHOD_KEY_ACTIVE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * ACH is for the US only.
     *
     * Logic based on Merchant Country Location config option.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        $merchantCountry = $this->scopeConfig->getValue(
            self::CONFIG_MERCHANT_COUNTRY,
            ScopeInterface::SCOPE_STORE
        );

        return in_array($merchantCountry, self::ALLOWED_MERCHANT_COUNTRIES, true);
    }

    /**
     * Get client token
     *
     * @return string
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getClientToken(): string
    {
        if (empty($this->clientToken)) {
            $params = [];

            $merchantAccountId = $this->braintreeConfig->getMerchantAccountId();
            if (!empty($merchantAccountId)) {
                $params[PaymentDataBuilder::MERCHANT_ACCOUNT_ID] = $merchantAccountId;
            }

            $this->clientToken = $this->adapter->generate($params);
        }

        return $this->clientToken;
    }

    /**
     * Get store name
     *
     * @return string
     */
    public function getStoreName(): string
    {
        $storeName = $this->scopeConfig->getValue(
            self::CONFIG_STORE_NAME,
            ScopeInterface::SCOPE_STORE
        );

        // If store name is empty, use the base URL
        if (!$storeName) {
            $storeName = $this->scopeConfig->getValue(
                self::CONFIG_STORE_URL,
                ScopeInterface::SCOPE_STORE
            );
        }
        return $storeName;
    }
}
