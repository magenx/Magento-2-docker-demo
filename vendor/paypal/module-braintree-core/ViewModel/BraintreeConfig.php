<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\ViewModel;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use PayPal\Braintree\Gateway\Config\Vault\Config as VaultConfig;
use PayPal\Braintree\Model\Ui\ConfigProvider;
use Psr\Log\LoggerInterface;

class BraintreeConfig implements ArgumentInterface
{
    /**
     * @var ConfigProvider
     */
    private ConfigProvider $configProvider;

    /**
     * @var VaultConfig
     */
    private VaultConfig $vaultConfig;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var array
     */
    private array $config = [];

    /**
     * @param ConfigProvider $configProvider
     * @param VaultConfig $vaultConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        ConfigProvider $configProvider,
        VaultConfig $vaultConfig,
        LoggerInterface $logger
    ) {
        $this->configProvider = $configProvider;
        $this->vaultConfig = $vaultConfig;
        $this->logger = $logger;
    }

    /**
     * Get whether Braintree cards payment method is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return (bool) $this->getConfig()['isActive'];
    }

    /**
     * Check whether Braintree CC vault enabled or not
     *
     * @return bool
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function isCCVaultEnabled(): bool
    {
        return $this->vaultConfig->isActive();
    }

    /**
     * Get client token
     *
     * @return string|null
     */
    public function getClientToken(): ?string
    {
        return $this->getConfig()['clientToken'] ?? null;
    }

    /**
     * Get mapper between Magento and Braintree card types
     *
     * @return array
     */
    public function getCcTypesMapper(): array
    {
        return $this->getConfig()['ccTypesMapper'] ?? [];
    }

    /**
     * Get the country specific card type config.
     *
     * @return array
     */
    public function getCountrySpecificCardTypes(): array
    {
        return $this->getConfig()['countrySpecificCardTypes'] ?? [];
    }

    /**
     * Should CVV be used/required.
     *
     * @return bool
     */
    public function shouldUseCvv(): bool
    {
        return isset($this->getConfig()['useCvv']) && $this->getConfig()['useCvv'];
    }

    /**
     * Get the available credit card types.
     *
     * @return array
     */
    public function getAvailableCardTypes(): array
    {
        $types = $this->getConfig()['availableCardTypes'] ?? [];
        $types[] = 'NONE';
        return $types;
    }

    /**
     * Get the current environment.
     *
     * @return string|null
     */
    public function getEnvironment(): ?string
    {
        return $this->getConfig()['environment'] ?? null;
    }

    /**
     * Get the merchant's ID.
     *
     * @return string|null
     */
    public function getMerchantId(): ?string
    {
        return $this->getConfig()['merchantId'] ?? null;
    }

    /**
     * Get the card types icons.
     *
     * @return array
     */
    public function getCardIcons(): array
    {
        return $this->getConfig()['icons'] ?? [];
    }

    /**
     * Get the Braintree Card config settings.
     *
     * @return array
     */
    private function getConfig(): array
    {
        if (!empty($this->config)) {
            return $this->config;
        }

        try {
            $config = $this->configProvider->getConfig();

            // If config empty, set flag to false, otherwise, load full config.
            $this->config = empty($config) ? ['isActive' => false] : $config['payment'][ConfigProvider::CODE];

            return $this->config;
        } catch (LocalizedException $ex) {
            $this->logger->error('Failed to get Braintree Config: ' . $ex->getMessage(), [
                'class' => BraintreeConfig::class
            ]);

            $this->config = ['isActive' => false];

            return $this->config;
        }
    }

    /**
     * Retrieve CVV tooltip image url
     *
     * @return string
     */
    public function getCvvImageUrl(): string
    {
        return $this->configProvider->getCvvImageUrl();
    }
}
