<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Model\GooglePay;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use PayPal\Braintree\Model\GooglePay\Ui\ConfigProvider;
use PayPal\Braintree\Model\Ui\ThreeDeeSecure\ConfigProvider as ThreeDeeSecureConfigProvider;

class Auth
{
    /**
     * @var UrlInterface
     */
    private UrlInterface $url;

    /**
     * @var ConfigProvider
     */
    private ConfigProvider $configProvider;

    /**
     * @var ThreeDeeSecureConfigProvider
     */
    private ThreeDeeSecureConfigProvider $threeDeeSecureConfigProvider;

    /**
     * Auth constructor
     *
     * @param UrlInterface $url
     * @param ConfigProvider $configProvider
     * @param ThreeDeeSecureConfigProvider $threeDeeSecureConfigProvider
     */
    public function __construct(
        UrlInterface $url,
        ConfigProvider $configProvider,
        ThreeDeeSecureConfigProvider $threeDeeSecureConfigProvider
    ) {
        $this->url = $url;
        $this->configProvider = $configProvider;
        $this->threeDeeSecureConfigProvider = $threeDeeSecureConfigProvider;
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
        return $this->configProvider->getClientToken();
    }

    /**
     * Get environment
     *
     * @return string
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getEnvironment(): string
    {
        return $this->configProvider->getEnvironment();
    }

    /**
     * Get Google Pay merchant id
     *
     * @return string
     */
    public function getMerchantId(): string
    {
        return $this->configProvider->getMerchantId();
    }

    /**
     * Get action success
     *
     * @return string
     */
    public function getActionSuccess(): string
    {
        return $this->url->getUrl('checkout/onepage/success', ['_secure' => true]);
    }

    /**
     * Get available card types
     *
     * @return array
     */
    public function getAvailableCardTypes(): array
    {
        return $this->configProvider->getAvailableCardTypes();
    }

    /**
     * Get Btn color
     *
     * @return int
     */
    public function getBtnColor(): int
    {
        return $this->configProvider->getBtnColor();
    }

    /**
     * Is 3DS enabled
     *
     * @return bool
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function is3DSecureEnabled(): bool
    {
        return $this->threeDeeSecureConfigProvider->isAvailable() && $this->threeDeeSecureConfigProvider->isEnabled();
    }

    /**
     * Is 3DS always requested
     *
     * @return bool
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function is3DSecureAlwaysRequested(): bool
    {
        return $this->threeDeeSecureConfigProvider->isChallengeAlwaysRequested();
    }

    /**
     * Get 3DS threshold amount
     *
     * @return float
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function get3DSecureThresholdAmount(): float
    {
        return $this->threeDeeSecureConfigProvider->getThresholdAmount();
    }

    /**
     * Get 3DS specific countries
     *
     * @return array
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function get3DSecureSpecificCountries(): array
    {
        return $this->threeDeeSecureConfigProvider->get3DSecureSpecificCountries();
    }

    /**
     * Get Customer's IP Address
     *
     * @return string
     */
    public function getIpAddress(): string
    {
        return $this->threeDeeSecureConfigProvider->getIpAddress();
    }
}
