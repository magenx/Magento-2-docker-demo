<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Model\Venmo\Ui;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Asset\Source;
use PayPal\Braintree\Gateway\Config\Config as BraintreeConfig;
use PayPal\Braintree\Gateway\Request\PaymentDataBuilder;
use PayPal\Braintree\Model\Adapter\BraintreeAdapter;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Asset\Repository;
use Magento\Store\Model\ScopeInterface;

class ConfigProvider implements ConfigProviderInterface
{
    public const METHOD_CODE = 'braintree_venmo';
    public const METHOD_VAULT_CODE = 'braintree_venmo_vault';

    private const MERCHANT_COUNTRY_CONFIG_VALUE = 'paypal/general/merchant_country';
    private const ALLOWED_MERCHANT_COUNTRIES = ['US'];
    private const METHOD_KEY_ACTIVE = 'payment/braintree_venmo/active';

    /**
     * @var BraintreeAdapter $adapter
     */
    private BraintreeAdapter $adapter;

    /**
     * @var Repository $assetRepo
     */
    private Repository $assetRepo;

    /**
     * @var BraintreeConfig $braintreeConfig
     */
    private BraintreeConfig $braintreeConfig;

    /**
     * @var ScopeConfigInterface $scopeConfig
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var Source
     */
    private Source $assetSource;

    /**
     * @var string $clientToken
     */
    private string $clientToken = '';

    /**
     * @var array
     */
    private array $icons = [];

    /**
     * ConfigProvider constructor.
     *
     * @param BraintreeAdapter $adapter
     * @param Repository $assetRepo
     * @param BraintreeConfig $braintreeConfig
     * @param ScopeConfigInterface $scopeConfig
     * @param Source $assetSource
     */
    public function __construct(
        BraintreeAdapter $adapter,
        Repository $assetRepo,
        BraintreeConfig $braintreeConfig,
        ScopeConfigInterface $scopeConfig,
        Source $assetSource
    ) {
        $this->adapter = $adapter;
        $this->assetRepo = $assetRepo;
        $this->braintreeConfig = $braintreeConfig;
        $this->scopeConfig = $scopeConfig;
        $this->assetSource = $assetSource;
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
        if (!$this->isActive()) {
            return [];
        }

        return [
            'payment' => [
                self::METHOD_CODE => [
                    'isAllowed' => $this->isAllowed(),
                    'clientToken' => $this->getClientToken(),
                    'paymentMarkSrc' => $this->getPaymentMarkSrc(),
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
    public function isActive(): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::METHOD_KEY_ACTIVE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Venmo is (currently) for the US only.
     *
     * Logic based on Merchant Country Location config option.
     *
     * @return bool
     */
    public function isAllowed(): bool
    {
        $merchantCountry = $this->scopeConfig->getValue(
            self::MERCHANT_COUNTRY_CONFIG_VALUE,
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
     * Get payment mark src
     *
     * @return string
     */
    public function getPaymentMarkSrc(): string
    {
        return $this->assetRepo->getUrl('PayPal_Braintree::images/venmo_logo_blue.png');
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
            'venmo' => 'Venmo',
        ];

        foreach ($availableIcons as $code => $label) {
            if (array_key_exists($code, $this->icons)) {
                continue;
            }

            $asset = $this->assetRepo->createAsset(
                'PayPal_Braintree::images/venmo/venmo_logo_blue.png',
                ['_secure' => true]
            );
            $placeholder = $this->assetSource->findSource($asset);

            if (!$placeholder) {
                continue;
            }

            $this->icons[$code] = [
                'url' => $asset->getUrl(),
                'title' => __($label),
                'width' => 54,
                'height' => 20
            ];
        }
        return $this->icons;
    }
}
