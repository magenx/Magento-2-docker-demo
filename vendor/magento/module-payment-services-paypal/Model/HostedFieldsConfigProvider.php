<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);
namespace Magento\PaymentServicesPaypal\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\UrlInterface;
use Magento\Payment\Model\CcConfigProvider;
use Magento\Payment\Model\CcConfig;
use Magento\PaymentServicesPaypal\Model\SdkService\PaymentOptionsBuilder;
use Magento\PaymentServicesPaypal\Model\SdkService\PaymentOptionsBuilderFactory;
use Magento\PaymentServicesBase\Model\Config as BaseConfig;

/**
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class HostedFieldsConfigProvider implements ConfigProviderInterface
{
    public const CODE = Config::PAYMENTS_SERVICES_PREFIX . 'hosted_fields';

    public const CC_VAULT_CODE = Config::PAYMENTS_SERVICES_PREFIX . 'vault';

    public const CC_SOURCE = 'cc';

    public const VAULT_SOURCE = 'vault';

    private const LOCATION = 'checkout_hosted_fields';

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var CcConfig
     */
    private CcConfig $ccConfig;

    /**
     * @var CcConfigProvider
     */
    private CcConfigProvider $ccConfigProvider;

    /**
     * @var CustomerSession
     */
    private CustomerSession $customerSession;

    /**
     * @var BaseConfig
     */
    private BaseConfig $baseConfig;

    /**
     * @var UrlInterface
     */
    private UrlInterface $url;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     *
     * @param Config $config
     * @param CcConfig $ccConfig
     * @param CcConfigProvider $ccConfigProvider
     * @param CustomerSession $customerSession
     * @param UrlInterface $url
     * @param BaseConfig $baseConfig
     * @param ConfigProvider $configProvider
     */
    public function __construct(
        Config $config,
        CcConfig $ccConfig,
        CcConfigProvider $ccConfigProvider,
        CustomerSession $customerSession,
        UrlInterface $url,
        BaseConfig $baseConfig,
        ConfigProvider $configProvider
    ) {
        $this->config = $config;
        $this->baseConfig = $baseConfig;
        $this->ccConfig = $ccConfig;
        $this->ccConfigProvider = $ccConfigProvider;
        $this->customerSession = $customerSession;
        $this->url = $url;
        $this->configProvider = $configProvider;
    }

    /**
     * @inheritdoc
     */
    public function getConfig()
    {
        $config = $this->configProvider->getConfig();
        if (!$this->baseConfig->isConfigured() || !$this->config->isHostedFieldsEnabled()) {
            $config['payment'][self::CODE]['isVisible'] = false;
            return $config;
        }
        $config['payment'][self::CODE]['isVisible'] = true;
        $config['payment'][self::CODE]['createOrderUrl'] = $this->url->getUrl('paymentservicespaypal/order/create');
        $config['payment'][self::CODE]['requiresCardDetails'] = $this->decideIfCardDetailsAreRequired();
        $config['payment'][self::CODE]['getOrderDetailsUrl'] =
            $this->url->getUrl('paymentservicespaypal/order/getcurrentorder');
        $config['payment'][self::CODE]['sdkParams'] = $this->configProvider->getScriptParams(
            self::CODE,
            self::LOCATION,
            $this->getPaymentOptions()
        );
        $config['payment'][self::CODE]['ccIcons'] = $this->ccConfigProvider->getIcons();
        $config['payment'][self::CODE]['cvvImageUrl'] = $this->ccConfig->getCvvImageUrl();
        $config['payment'][self::CODE]['paymentTypeIconUrl'] =
            $this->config->getViewFileUrl('Magento_PaymentServicesPaypal::images/cc_icon.png');
        $config['payment'][self::CODE]['paymentSource'] = self::CC_SOURCE;
        $config['payment'][self::CODE]['threeDS'] = $this->config->getThreeDS() !== "0" ?
            $this->config->getThreeDS() : false;
        $config['payment'][self::CODE]['isCommerceVaultEnabled'] = $this->config->isVaultEnabled()
            && $this->customerSession->isLoggedIn();
        $config['payment'][self::CODE]['ccVaultCode'] = self::CC_VAULT_CODE;
        return $config;
    }

    /**
     * @inheritdoc
     */
    private function getPaymentOptions(): PaymentOptionsBuilder
    {
        $paymentOptionsBuilder = $this->configProvider->getPaymentOptions();
        $paymentOptionsBuilder->setIsCreditCardEnabled($this->config->isHostedFieldsEnabled());
        $paymentOptionsBuilder->useHostedFieldsForCreditCard();
        return $paymentOptionsBuilder;
    }

    /**
     * Decides if we need to load card details for Signifyd Pre-Auth flow
     *      - we don't need card details if the merchant does not use Signifyd integration
     *      - we need card details if the merchant uses Signifyd integration
     *
     * @return bool
     */
    private function decideIfCardDetailsAreRequired() : bool
    {
        return $this->config->isSignifydEnabled();
    }
}
