<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\ViewModel;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use PayPal\Braintree\Gateway\Config\Config;
use PayPal\Braintree\Model\Ui\ThreeDeeSecure\ConfigProvider;
use Psr\Log\LoggerInterface;

class BraintreeThreeDSecureConfig implements ArgumentInterface
{
    /**
     * @var ConfigProvider
     */
    private ConfigProvider $configProvider;

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
     * @param LoggerInterface $logger
     */
    public function __construct(
        ConfigProvider $configProvider,
        LoggerInterface $logger
    ) {
        $this->configProvider = $configProvider;
        $this->logger = $logger;
    }

    /**
     * Get whether Braintree 3D Secure is active.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return (bool) $this->getConfig()['enabled'];
    }

    /**
     * Should always request 3D Secure?
     *
     * @return bool
     */
    public function isChallengeRequested(): bool
    {
        return isset($this->getConfig()['challengeRequested']) && $this->getConfig()['challengeRequested'];
    }

    /**
     * Get the threshold amount to trigger 3D Secure.
     *
     * @return float|null
     */
    public function getThresholdAmount(): ?float
    {
        return $this->getConfig()['thresholdAmount'] ?? null;
    }

    /**
     * Get the specific countries 3DS should be applied to.
     *
     * @return array
     */
    public function getSpecificCountries(): array
    {
        return $this->getConfig()['specificCountries'] ?? [];
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
            $this->config = empty($config) ? ['enabled' => false] : $config['payment'][Config::CODE_3DSECURE];

            return $this->config;
        } catch (LocalizedException $ex) {
            $this->logger->error('Failed to get Braintree 3D Secure Config: ' . $ex->getMessage(), [
                'class' => BraintreeThreeDSecureConfig::class
            ]);

            $this->config = ['enabled' => false];

            return $this->config;
        }
    }
}
