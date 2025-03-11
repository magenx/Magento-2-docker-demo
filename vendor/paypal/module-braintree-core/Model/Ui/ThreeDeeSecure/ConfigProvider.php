<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Model\Ui\ThreeDeeSecure;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use PayPal\Braintree\Gateway\Config\Config;
use PayPal\Braintree\Gateway\Config\Vault\Config as VaultConfig;
use PayPal\Braintree\Model\GooglePay\Config as GooglePayConfig;
use PayPal\Braintree\Model\Ui\ConfigProvider as BraintreeConfigProvider;

class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var GooglePayConfig
     */
    private GooglePayConfig $googlePayConfig;

    /**
     * @var VaultConfig
     */
    private VaultConfig $vaultConfig;

    /**
     * @var RemoteAddress
     */
    private RemoteAddress $remoteAddress;

    /**
     * ConfigProvider constructor.
     *
     * @param Config $config
     * @param GooglePayConfig $googlePayConfig
     * @param VaultConfig $vaultConfig
     * @param RemoteAddress $remoteAddress
     */
    public function __construct(
        Config $config,
        GooglePayConfig $googlePayConfig,
        VaultConfig $vaultConfig,
        RemoteAddress $remoteAddress
    ) {
        $this->config = $config;
        $this->googlePayConfig = $googlePayConfig;
        $this->vaultConfig = $vaultConfig;
        $this->remoteAddress = $remoteAddress;
    }

    /**
     * @inheritDoc
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getConfig(): array
    {
        // 3DS is currently used either by Google Pay or Card payments, so do not include if none of those is enabled.
        if (!$this->isAvailable()) {
            return [];
        }

        return [
            'payment' => [
                Config::CODE_3DSECURE => [
                    'enabled' => $this->isEnabled(),
                    'challengeRequested' => $this->isChallengeAlwaysRequested(),
                    'thresholdAmount' => $this->getThresholdAmount(),
                    'specificCountries' => $this->get3DSecureSpecificCountries(),
                    'ccVaultCode' => BraintreeConfigProvider::CC_VAULT_CODE,
                    'useCvvVault' => $this->vaultConfig->isCvvVerifyEnabled(),
                    'ipAddress' => $this->getIpAddress()
                ]
            ]
        ];
    }

    /**
     * 3DS is currently used either by Google Pay or Card payments, so do not include if none of those is enabled.
     *
     * @return bool
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function isAvailable(): bool
    {
        return $this->config->isActive() || $this->googlePayConfig->isActive();
    }

    /**
     * Is enabled
     *
     * @return bool
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function isEnabled(): bool
    {
        return $this->config->isVerify3DSecure();
    }

    /**
     * Is challenge always requested
     *
     * @return bool
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function isChallengeAlwaysRequested(): bool
    {
        return $this->config->is3DSAlwaysRequested();
    }

    /**
     * Get threshold amount
     *
     * @return float
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getThresholdAmount(): float
    {
        return $this->config->getThresholdAmount();
    }

    /**
     * Get 3D secure specific countries
     *
     * @return array
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function get3DSecureSpecificCountries(): array
    {
        return $this->config->get3DSecureSpecificCountries();
    }

    /**
     * Get Customer's IP Address
     *
     * @return string
     */
    public function getIpAddress(): string
    {
        return $this->remoteAddress->getRemoteAddress();
    }
}
