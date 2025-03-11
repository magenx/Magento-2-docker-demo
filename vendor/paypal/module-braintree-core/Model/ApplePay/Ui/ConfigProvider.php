<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Model\ApplePay\Ui;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Asset\Source;
use PayPal\Braintree\Gateway\Config\Config as BraintreeConfig;
use PayPal\Braintree\Gateway\Request\PaymentDataBuilder;
use PayPal\Braintree\Model\ApplePay\Config;
use Magento\Checkout\Model\ConfigProviderInterface;
use PayPal\Braintree\Model\Adapter\BraintreeAdapter;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Asset\Repository;
use Magento\Store\Model\ScopeInterface;


class ConfigProvider implements ConfigProviderInterface
{
    public const METHOD_CODE = 'braintree_applepay';
    public const METHOD_VAULT_CODE = 'braintree_applepay_vault';
    private const METHOD_KEY_ACTIVE = 'payment/braintree_applepay/active';

    /**
     * @var Source
     */
    private Source $assetSource;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var BraintreeAdapter
     */
    private $adapter;

    /**
     * @var Repository
     */
    private $assetRepo;

    /**
     * @var BraintreeConfig
     */
    private $braintreeConfig;

    /**
     * @var string
     */
    private $clientToken = '';

    /**
     * @var ScopeConfigInterface $scopeConfig
     */
    private $scopeConfig;

    /**
     * @var array
     */
    private array $icons = [];

    /**
     * ConfigProvider constructor.
     *
     * @param Source $assetSource
     * @param Config $config
     * @param BraintreeAdapter $adapter
     * @param Repository $assetRepo
     * @param BraintreeConfig $braintreeConfig
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Source $assetSource,
        Config $config,
        BraintreeAdapter $adapter,
        Repository $assetRepo,
        BraintreeConfig $braintreeConfig,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->assetSource = $assetSource;
        $this->config = $config;
        $this->adapter = $adapter;
        $this->assetRepo = $assetRepo;
        $this->braintreeConfig = $braintreeConfig;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     * @throws LocalizedException
     */
    public function getConfig(): array
    {
        if (!$this->isActive()) {
            return [];
        }

        return [
            'payment' => [
                self::METHOD_CODE => [
                    'clientToken' => $this->getClientToken(),
                    'merchantName' => $this->getMerchantName(),
                    'paymentMarkSrc' => $this->getPaymentMarkSrc(),
                    'vaultCode' => self::METHOD_VAULT_CODE,
                    'icons' => $this->getIcons()
                ]
            ]
        ];
    }

    /**
     * Get Payment configuration status
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::METHOD_KEY_ACTIVE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Generate a new client token if necessary
     *
     * @return string|null
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getClientToken()
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
     * Get merchant name
     *
     * @return string
     */
    public function getMerchantName(): string
    {
        return $this->config->getMerchantName();
    }

    /**
     * Get the url to the payment mark image
     *
     * @return string
     */
    public function getPaymentMarkSrc(): string
    {
        return $this->assetRepo->getUrl('PayPal_Braintree::images/applepaymark.png');
    }

    /**
     * Get icons for available payment methods
     *
     * @return array
     * @throws LocalizedException
     */
    public function getIcons(): array
    {
        if (!empty($this->icons)) {
            return $this->icons;
        }

        $availableIcons = [
            'ae' => 'Apple Pay - American Express',
            'di' => 'Apple Pay - Discover',
            'mc' => 'Apple Pay - MasterCard',
            'vi' => 'Apple Pay - Vista',
            'applepaymark' => 'Apple Pay'
        ];

        foreach ($availableIcons as $code => $label) {
            if (array_key_exists($code, $this->icons)) {
                continue;
            }

            $asset = $this->assetRepo->createAsset(
                $code === 'applepaymark'
                    ? 'PayPal_Braintree::images/' . strtolower($code) . '.png'
                    : 'PayPal_Braintree::images/applepay/' . strtolower($code) . '.png',
                ['_secure' => true]
            );
            $placeholder = $this->assetSource->findSource($asset);

            if (!$placeholder) {
                continue;
            }

            [$width, $height] = getimagesize($asset->getSourceFile());
            $this->icons[$code] = [
                'url' => $asset->getUrl(),
                'width' => $width,
                'height' => $height,
                'title' => __($label),
            ];
        }

        return $this->icons;
    }
}
