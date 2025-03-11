<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);
namespace Magento\PaymentServicesPaypal\Model;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\PaymentServicesBase\Model\Config as BaseConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class Config
{
    private const CONFIG_PATH_FUNDING_FORMAT= 'payment/payment_services_paypal_smart_buttons/funding_%s';
    private const CONFIG_PATH_BUTTON_STYLE_FORMAT = 'payment/payment_services_paypal_smart_buttons/style_%s';
    private const BUTTON_STYLE_OPTIONS = [
        'layout' => 'string',
        'color' => 'string',
        'shape' => 'string',
        'height' => 'int',
        'label' => 'string',
        'tagline' => 'bool'
    ];
    private const BUTTON_STYLE_DEFAULT = [
        'height' => 'height_use_default'
    ];

    public const GOOGLE_PAY_TEST_MODE = "TEST";
    public const GOOGLE_PAY_PROD_MODE = "PRODUCTION";

    public const PAYMENTS_SERVICES_PREFIX = 'payment_services_paypal_';

    /**
     * @var BaseConfig
     */
    private $config;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Repository
     */
    private $assetRepo;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param BaseConfig $config
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param Repository $assetRepo
     * @param RequestInterface $request
     * @param UrlInterface $urlBuilder
     * @param LoggerInterface $logger
     */
    public function __construct(
        BaseConfig $config,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        Repository $assetRepo,
        RequestInterface $request,
        UrlInterface $urlBuilder,
        LoggerInterface $logger,
    ) {
        $this->config = $config;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->assetRepo = $assetRepo;
        $this->request = $request;
        $this->urlBuilder = $urlBuilder;
        $this->logger = $logger;
    }

    /**
     * Get domain association config value
     *
     * @return string
     */
    public function getDomainAssociation(): string
    {
        return (string) $this->scopeConfig->getValue(
            'payment/payment_services_paypal_apple_pay/domain_association',
            ScopeInterface::SCOPE_STORE,
        );
    }

    /**
     * Get soft descriptor config value
     *
     * @param string|null $storeCode
     * @return mixed
     */
    public function getSoftDescriptor($storeCode = null)
    {
        return $this->scopeConfig->getValue(
            'payment/payment_services/soft_descriptor',
            ScopeInterface::SCOPE_STORE,
            $storeCode
        );
    }

    /**
     * Get 3DS config value
     *
     * @param string|null $storeCode
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getThreeDS($storeCode = null)
    {
        $storeCode = $this->storeManager->getStore($storeCode)->getCode();
        return $this->scopeConfig->getValue(
            'payment/payment_services_paypal_hosted_fields/three_ds',
            ScopeInterface::SCOPE_STORE,
            $storeCode
        );
    }

    /**
     * Get 3DS config value
     *
     * @param string|null $storeCode
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getGooglePayThreeDS($storeCode = null)
    {
        $storeCode = $this->storeManager->getStore($storeCode)->getCode();
        return $this->scopeConfig->getValue(
            'payment/payment_services_paypal_google_pay/three_ds',
            ScopeInterface::SCOPE_STORE,
            $storeCode
        );
    }

    /**
     * Get the merchant ID
     *
     * @param string $environment
     * @return string
     */
    public function getMerchantId(string $environment = '') : string
    {
        return $this->config->getMerchantId($environment);
    }

    /**
     * Check if the payment method is enabled
     *
     * @param int|null $store
     * @return bool
     */
    public function isEnabled($store = null) : bool
    {
        return $this->config->isEnabled($store);
    }

    /**
     * Check if Smart Buttons for a particular location is enabled
     *
     * @param string $location
     * @param int|null $store
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isLocationEnabled(string $location, ?int $store = null): bool
    {
        $storeCode = $this->storeManager->getStore($store)->getCode();
        return (bool) $this->scopeConfig->getValue(
            'payment/payment_services_paypal_smart_buttons/display_buttons_' . $location,
            ScopeInterface::SCOPE_STORE,
            $storeCode
        );
    }

    /**
     * Check if Apple Pay for a particular location is enabled
     *
     * @param string $location
     * @param int|null $store
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isApplePayLocationEnabled(string $location, ?int $store = null): bool
    {
        $storeCode = $this->storeManager->getStore($store)->getCode();
        return (bool) $this->scopeConfig->getValue(
            'payment/payment_services_paypal_apple_pay/display_' . $location,
            ScopeInterface::SCOPE_STORE,
            $storeCode
        );
    }

    /**
     * Check if Google Pay for a particular location is enabled
     *
     * @param string $location
     * @param int|null $store
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isGooglePayLocationEnabled(string $location, ?int $store = null): bool
    {
        $storeCode = $this->storeManager->getStore($store)->getCode();
        return (bool) $this->scopeConfig->getValue(
            'payment/payment_services_paypal_google_pay/display_' . $location,
            ScopeInterface::SCOPE_STORE,
            $storeCode
        );
    }

    /**
     * Check if Hosted Fields method is enabled
     *
     * @param int|null $store
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isHostedFieldsEnabled(?int $store = null): bool
    {
        $storeCode = $this->storeManager->getStore($store)->getCode();
        return (bool) $this->scopeConfig->getValue(
            'payment/payment_services_paypal_hosted_fields/display_on_checkout',
            ScopeInterface::SCOPE_STORE,
            $storeCode
        );
    }

    /**
     * Get the payment intent (authorize/capture) for a particular payment method
     *
     * @param string $code
     * @param int|null $storeId
     * @return string
     * @throws NoSuchEntityException
     */
    public function getPaymentIntent(string $code, $storeId = null): string
    {
        $storeCode = $this->storeManager->getStore($storeId)->getCode();
        $configPath = 'payment/' . $code . '/payment_action';
        $paymentAction = $this->scopeConfig->getValue(
            $configPath,
            ScopeInterface::SCOPE_STORE,
            $storeCode
        );
        if ($paymentAction === MethodInterface::ACTION_AUTHORIZE_CAPTURE) {
            $paymentAction = TransactionInterface::TYPE_CAPTURE;
        }
        return $paymentAction;
    }

    /**
     * Get the payment title
     *
     * @param string $code
     * @param int|null $store
     * @return string
     * @throws NoSuchEntityException
     */
    public function getPaymentTitle(string $code, ?int $store = null): string
    {
        $storeCode = $this->storeManager->getStore($store)->getCode();
        $configPath = 'payment/' . $code . '/title';
        $paymentTitle = $this->scopeConfig->getValue(
            $configPath,
            ScopeInterface::SCOPE_STORE,
            $storeCode
        );

        return $paymentTitle;
    }

    /**
     * Get the payment title
     *
     * @param string $code
     * @param int|null $store
     * @return string
     * @throws NoSuchEntityException
     */
    public function getSortOrder(string $code, ?int $store = null): string
    {
        $storeCode = $this->storeManager->getStore($store)->getCode();
        $configPath = 'payment/' . $code . '/sort_order';
        $paymentTitle = $this->scopeConfig->getValue(
            $configPath,
            ScopeInterface::SCOPE_STORE,
            $storeCode
        );

        return $paymentTitle;
    }

    /**
     * Check if the pay later message should be displayed
     *
     * @param int|null $store
     * @return bool
     * @throws NoSuchEntityException
     */
    public function canDisplayPayLaterMessage(?int $store = null) : bool
    {
        $storeCode = $this->storeManager->getStore($store)->getCode();
        return (bool) $this->scopeConfig->getValue(
            'payment/payment_services_paypal_smart_buttons/display_paylater_message',
            ScopeInterface::SCOPE_STORE,
            $storeCode
        );
    }

    /**
     * Check if the vault is enabled
     *
     * @param int|null $store
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isVaultEnabled(?int $store = null) : bool
    {
        $storeCode = $this->storeManager->getStore($store)->getCode();
        return (bool) $this->scopeConfig->getValue(
            'payment/payment_services_paypal_vault/active',
            ScopeInterface::SCOPE_STORE,
            $storeCode
        );
    }

    /**
     * Get a funding source configuration by name (for the current store)
     *
     * @param string $funding
     * @param int|null $store
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isFundingSourceEnabledByName(string $funding = '', ?int $store = null) : bool
    {
        $storeCode = $this->storeManager->getStore($store)->getCode();

        $configVal = $this->scopeConfig->getValue(
            sprintf(self::CONFIG_PATH_FUNDING_FORMAT, $funding),
            ScopeInterface::SCOPE_STORE,
            $storeCode
        );

        return $configVal === null || (bool)$configVal;
    }

    /**
     * Get configured button style options
     *
     * @param int|null $store
     * @return array
     * @throws NoSuchEntityException
     */
    public function getButtonConfiguration(?int $store = null) : array
    {
        $buttonConfig = [];
        $storeCode = $this->storeManager->getStore($store)->getCode();

        foreach (self::BUTTON_STYLE_OPTIONS as $styleName => $styleType) {
            // Check to see if we want to ignore custom values and use defaults from the provider.
            if (array_key_exists($styleName, self::BUTTON_STYLE_DEFAULT)) {
                $useDefaultParam = self::BUTTON_STYLE_DEFAULT[$styleName];
                $useDefault = (bool)$this->scopeConfig->getValue(
                    sprintf(self::CONFIG_PATH_BUTTON_STYLE_FORMAT, $useDefaultParam),
                    ScopeInterface::SCOPE_STORE,
                    $storeCode
                );

                if ($useDefault === true) {
                    continue;
                }
            }

            $styleVal = $this->scopeConfig->getValue(
                sprintf(self::CONFIG_PATH_BUTTON_STYLE_FORMAT, $styleName),
                ScopeInterface::SCOPE_STORE,
                $storeCode
            );

            if ($styleVal == null) {
                continue;
            } elseif ($styleType === 'bool') {
                $styleVal = (bool)$styleVal;
            } elseif ($styleType === 'int') {
                $styleVal = (int)$styleVal;
            }

            $buttonConfig[$styleName] = $styleVal;
        }

        return $buttonConfig;
    }

    /**
     * Retrieve url of a view file
     *
     * @param string $fileId
     * @param array $params
     * @return string
     */
    public function getViewFileUrl(string $fileId, array $params = [])
    {
        try {
            $params = array_merge(['_secure' => $this->request->isSecure()], $params);
            return $this->assetRepo->getUrlWithParams($fileId, $params);
        } catch (LocalizedException $e) {
            $this->logger->critical($e);
            return $this->urlBuilder->getUrl('', ['_direct' => 'core/index/notFound']);
        }
    }

    /**
     * Check if the Signifyd extension is enabled
     *
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isSignifydEnabled() : bool
    {
        $storeCode = $this->storeManager->getStore()->getCode();
        return (bool) $this->scopeConfig->getValue(
            'signifyd/general/enabled',
            ScopeInterface::SCOPE_STORE,
            $storeCode
        );
    }

    /**
     * Get the Google Pay Styles
     *
     * @param int|null $store
     * @return array
     * @throws NoSuchEntityException
     */
    public function getGooglePayStyles(?int $store = null) : array
    {
        $storeCode = $this->storeManager->getStore($store)->getCode();
        $genericStyles = $this->getButtonConfiguration($store);

        return [
            'button_color' => $this->getGooglePayButtonStyle('button_color', $storeCode),
            'button_type' => $this->getGooglePayButtonStyle('button_type', $storeCode),
            'button_custom_height' => isset($genericStyles['height']) ? (int) $genericStyles['height'] : 0,
        ];
    }

    /**
     * Get the Google Pay button style
     *
     * @param string $configName
     * @param string $storeCode
     * @return string
     */
    private function getGooglePayButtonStyle(string $configName, string $storeCode): string
    {
        return $this->scopeConfig->getValue(
            'payment/payment_services_paypal_google_pay/' . $configName,
            ScopeInterface::SCOPE_STORE,
            $storeCode
        ) ?? '';
    }

    /**
     * See https://developers.google.com/pay/api/web/guides/test-and-deploy/deploy-production-environment
     *
     * @return string
     */
    public function getGooglePayMode(): string
    {
        if ($this->isSandboxEnvironment()) {
            return self::GOOGLE_PAY_TEST_MODE;
        }

        return self::GOOGLE_PAY_PROD_MODE;
    }

    /**
     * Check if async status updates for payments are enabled
     *
     * @param string|null $storeCode
     * @return bool
     */
    public function isAsyncPaymentStatusUpdatesEnabled(?string $storeCode = null) : bool
    {
        return (bool) $this->scopeConfig->getValue(
            'payment/payment_services/async_status_updates',
            ScopeInterface::SCOPE_STORE,
            $storeCode
        );
    }

    /**
     * Check if the environment is sandbox
     *
     * @return bool
     */
    private function isSandboxEnvironment(): bool
    {
        return $this->config->getEnvironmentType() === "sandbox";
    }

    /**
     * Check if sending L2 L3 Data is enabled
     *
     * @param string|null $storeCode
     * @return bool
     */
    public function isL2L3SendDataEnabled(?string $storeCode = null) : bool
    {
        return (bool) $this->scopeConfig->getValue(
            'payment/payment_services_paypal_l2_l3/send_data',
            ScopeInterface::SCOPE_STORE,
            $storeCode
        );
    }

    /**
     * Return the Payment Services SDK url
     *
     * @return string
     */
    public function getPaymentsSDKUrl() : string
    {
        return $this->scopeConfig->getValue('payment/payment_services/sdk_url');
    }
}
