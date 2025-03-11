<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\ViewModel;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use PayPal\Braintree\Gateway\Config\PayPalPayLater\Config as PayLaterConfig;
use PayPal\Braintree\Model\Ui\PayPal\ConfigProvider;
use Psr\Log\LoggerInterface;

class BraintreePayPalConfig implements ArgumentInterface
{
    /**
     * @var ConfigProvider
     */
    private ConfigProvider $configProvider;

    /**
     * @var PayLaterConfig
     */
    private PayLaterConfig $payLaterConfig;

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
     * @param PayLaterConfig $payLaterConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        ConfigProvider $configProvider,
        PayLaterConfig $payLaterConfig,
        LoggerInterface $logger
    ) {
        $this->configProvider = $configProvider;
        $this->payLaterConfig = $payLaterConfig;
        $this->logger = $logger;
    }

    /**
     * Get whether PayPal is active or not.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return (bool) $this->getConfig()['isActive'];
    }

    /**
     * Check whether PayPal Vault enabled or not
     *
     * @return bool
     */
    public function isPayPalVaultEnabled(): bool
    {
        return $this->payLaterConfig->isPayPalVaultActive();
    }

    /**
     * Get Client token
     *
     * @return string|null
     */
    public function getClientToken(): ?string
    {
        return $this->getConfig()['clientToken'] ?? null;
    }

    /**
     * Get the payment method title.
     *
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->getConfig()['title'] ?? null;
    }

    /**
     * Is shipping address override allowed?
     *
     * @return bool
     */
    public function isAllowShippingAddressOverride(): bool
    {
        return isset($this->getConfig()['isAllowShippingAddressOverride'])
            && $this->getConfig()['isAllowShippingAddressOverride'];
    }

    /**
     * Get the merchant's name.
     *
     * @return string|null
     */
    public function getMerchantName(): ?string
    {
        return $this->getConfig()['merchantName'] ?? null;
    }

    /**
     * Get the available credit card types.
     *
     * @return array
     */
    public function getAvailableCardTypes(): array
    {
        return $this->getConfig()['availableCardTypes'] ?? [];
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
     * Get the merchant's country.
     *
     * @return string|null
     */
    public function getMerchantCountry(): ?string
    {
        return $this->getConfig()['merchantCountry'] ?? null;
    }

    /**
     * Get the locale.
     *
     * @return string|null
     */
    public function getLocale(): ?string
    {
        return $this->getConfig()['locale'] ?? null;
    }

    /**
     * Get the payment acceptance mark source file.
     *
     * @return string|null
     */
    public function getPaymentAcceptanceMarkSrc(): ?string
    {
        return $this->getConfig()['paymentAcceptanceMarkSrc'] ?? null;
    }

    /**
     * Get the PayPal icon data array, otherwise null.
     *
     * @return array|null
     */
    public function getIcon(): ?array
    {
        return $this->getConfig()['paymentIcon'] ?? null;
    }

    /**
     * Get the PayPal Icon style.
     *
     * @return array|null
     */
    public function getStyle(): ?array
    {
        return $this->getConfig()['style'] ?? null;
    }

    /**
     * Is billing address required?
     *
     * @return bool
     */
    public function isRequiredBillingAddress(): bool
    {
        return isset($this->getConfig()['isRequiredBillingAddress']) && $this->getConfig()['isRequiredBillingAddress'];
    }

    /**
     * Can send line items to PayPal?
     *
     * @return bool
     */
    public function canSendLineItems(): bool
    {
        return isset($this->getConfig()['canSendLineItems']) && $this->getConfig()['canSendLineItems'];
    }

    /**
     * Get the Braintree PayPal config settings.
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
            $this->config = empty($config) ? ['isActive' => false] : $config['payment'][ConfigProvider::PAYPAL_CODE];

            return $this->config;
        } catch (LocalizedException $ex) {
            $this->logger->error('Failed to get Braintree PayPal Config: ' . $ex->getMessage(), [
                'class' => BraintreePayPalConfig::class
            ]);

            $this->config = ['isActive' => false];

            return $this->config;
        }
    }
}
