<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace PayPal\Braintree\Model\GooglePay\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Asset\Source;
use PayPal\Braintree\Gateway\Config\Config as BraintreeConfig;
use PayPal\Braintree\Gateway\Request\PaymentDataBuilder;
use PayPal\Braintree\Model\GooglePay\Config;
use PayPal\Braintree\Model\Adapter\BraintreeAdapter;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Asset\Repository;

class ConfigProvider implements ConfigProviderInterface
{
    public const METHOD_CODE = 'braintree_googlepay';
    public const METHOD_VAULT_CODE = 'braintree_googlepay_vault';

    /**
     * @var Source
     */
    private Source $assetSource;

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var BraintreeAdapter
     */
    private BraintreeAdapter $adapter;

    /**
     * @var Repository
     */
    private Repository $assetRepo;

    /**
     * @var BraintreeConfig
     */
    private BraintreeConfig $braintreeConfig;

    /**
     * @var string
     */
    private string $clientToken = '';

    /**
     * @var string
     */
    private string $fileId = 'PayPal_Braintree::images/GooglePay_AcceptanceMark_WhiteShape_RGB_60x36pt@4x.png';

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
     */
    public function __construct(
        Source $assetSource,
        Config $config,
        BraintreeAdapter $adapter,
        Repository $assetRepo,
        BraintreeConfig $braintreeConfig
    ) {
        $this->assetSource = $assetSource;
        $this->config = $config;
        $this->adapter = $adapter;
        $this->assetRepo = $assetRepo;
        $this->braintreeConfig = $braintreeConfig;
    }

    /**
     * @inheritDoc
     *
     * @throws LocalizedException
     */
    public function getConfig(): array
    {
        if (!$this->config->isActive()) {
            return [];
        }

        return [
            'payment' => [
                self::METHOD_CODE => [
                    'environment' => $this->getEnvironment(),
                    'clientToken' => $this->getClientToken(),
                    'merchantId' => $this->getMerchantId(),
                    'cardTypes' => $this->getAvailableCardTypes(),
                    'btnColor' => $this->getBtnColor(),
                    'paymentMarkSrc' => $this->getPaymentMarkSrc(),
                    'vaultCode' => self::METHOD_VAULT_CODE,
                    'icons' => $this->getIcons()
                ]
            ]
        ];
    }

    /**
     * Generate a new client token if necessary
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
     * Get environment
     *
     * @return string
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getEnvironment(): string
    {
        return $this->config->getEnvironment();
    }

    /**
     * Get merchant name
     *
     * @return string
     */
    public function getMerchantId(): string
    {
        return $this->config->getMerchantId();
    }

    /**
     * Get button color
     *
     * @return int
     */
    public function getBtnColor(): int
    {
        return $this->config->getBtnColor();
    }

    /**
     * Get available card types
     *
     * @return array
     */
    public function getAvailableCardTypes(): array
    {
        return $this->config->getAvailableCardTypes();
    }

    /**
     * Get the url to the payment mark image
     *
     * @return string
     */
    public function getPaymentMarkSrc(): string
    {
        return $this->assetRepo->getUrl($this->fileId);
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
            'ae' => 'Google Pay - American Express',
            'di' => 'Google Pay - Discover',
            'mc' => 'Google Pay - MasterCard',
            'vi' => 'Google Pay - Visa',
            'googlepaymark' => 'Google Pay'
        ];

        foreach ($availableIcons as $code => $label) {
            if (array_key_exists($code, $this->icons)) {
                continue;
            }

            $asset = $this->assetRepo->createAsset(
                $code === 'googlepaymark'
                    ? $this->fileId
                    : 'PayPal_Braintree::images/googlepay/' . strtolower($code) . '.png',
                ['_secure' => true]
            );
            $placeholder = $this->assetSource->findSource($asset);

            if (!$placeholder) {
                continue;
            }

            $this->icons[$code] = [
                'url' => $asset->getUrl(),
                'width' => 46,
                'height' => 30,
                'title' => __($label),
            ];
        }

        return $this->icons;
    }
}
