<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Model\Lpm;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository;
use PayPal\Braintree\Gateway\Config\Config as BraintreeConfig;
use PayPal\Braintree\Gateway\Request\PaymentDataBuilder;
use PayPal\Braintree\Model\Adapter\BraintreeAdapter;
use PayPal\Braintree\Model\StoreConfigResolver;

/**
 * Provide configuration for LPMs
 */
class Config extends \Magento\Payment\Gateway\Config\Config
{
    public const KEY_ACTIVE = 'active';
    public const KEY_ALLOWED_METHODS = 'allowed_methods';
    public const KEY_TITLE = 'title';
    public const KEY_FALLBACK_BUTTON_TEXT = 'fallback_button_text';
    public const KEY_REDIRECT_ON_FAIL = 'redirect_on_fail';
    private const LPM_FALLBACK_ACTION_URL = 'braintree/lpm/fallback';

    public const VALUE_BANCONTACT = 'bancontact';
    public const VALUE_EPS = 'eps';
    public const VALUE_GIROPAY = 'giropay';
    public const VALUE_IDEAL = 'ideal';
    public const VALUE_SOFORT = 'sofort';
    public const VALUE_MYBANK = 'mybank';
    public const VALUE_P24 = 'p24';
    public const VALUE_SEPA = 'sepa';

    public const LABEL_BANCONTACT = 'Bancontact';
    public const LABEL_EPS = 'EPS';
    public const LABEL_GIROPAY = 'giropay';
    public const LABEL_IDEAL = 'iDEAL';
    public const LABEL_SOFORT = 'Klarna Pay Now / SOFORT';
    public const LABEL_MYBANK = 'MyBank';
    public const LABEL_P24 = 'P24';
    public const LABEL_SEPA = 'SEPA/ELV Direct Debit';

    private const COUNTRIES_BANCONTACT = 'BE';
    private const COUNTRIES_EPS = 'AT';
    private const COUNTRIES_GIROPAY = 'DE';
    private const COUNTRIES_IDEAL = 'NL';
    private const COUNTRIES_SOFORT = ['AT', 'BE', 'DE', 'ES', 'IT', 'NL', 'GB'];
    private const COUNTRIES_MYBANK = 'IT';
    private const COUNTRIES_P24 = 'PL';
    private const COUNTRIES_SEPA = ['AT', 'DE'];

    /**
     * @var StoreConfigResolver
     */
    private StoreConfigResolver $storeConfigResolver;

    /**
     * @var string
     */
    private string $clientToken = '';

    /**
     * @var BraintreeAdapter
     */
    private BraintreeAdapter $adapter;

    /**
     * @var BraintreeConfig
     */
    private BraintreeConfig $braintreeConfig;

    /**
     * @var array
     */
    private array $allowedMethods;

    /**
     * @var Repository
     */
    private Repository $assetRepo;

    /**
     * @var UrlInterface
     */
    private UrlInterface $urlBuilder;

    /**
     * @param BraintreeAdapter $adapter
     * @param BraintreeConfig $braintreeConfig
     * @param StoreConfigResolver $storeConfigResolver
     * @param Repository $assetRepo
     * @param UrlInterface $urlBuilder
     * @param ScopeConfigInterface $scopeConfig
     * @param string|null $methodCode
     * @param string $pathPattern
     */
    public function __construct(
        BraintreeAdapter $adapter,
        BraintreeConfig $braintreeConfig,
        StoreConfigResolver $storeConfigResolver,
        Repository $assetRepo,
        UrlInterface $urlBuilder,
        ScopeConfigInterface $scopeConfig,
        string $methodCode = null,
        string $pathPattern = \Magento\Payment\Gateway\Config\Config::DEFAULT_PATH_PATTERN
    ) {
        parent::__construct($scopeConfig, $methodCode, $pathPattern);
        $this->adapter = $adapter;
        $this->braintreeConfig = $braintreeConfig;
        $this->storeConfigResolver = $storeConfigResolver;
        $this->assetRepo = $assetRepo;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Is method active
     *
     * @return bool
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function isActive(): bool
    {
        return (bool) $this->getValue(
            self::KEY_ACTIVE,
            $this->storeConfigResolver->getStoreId()
        );
    }

    /**
     * Get allowed methods
     *
     * @return array
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getAllowedMethods(): array
    {
        $allowedMethodsValue = $this->getValue(
            self::KEY_ALLOWED_METHODS,
            $this->storeConfigResolver->getStoreId()
        );
        if ($allowedMethodsValue === null) {
            return [];
        }
        $allowedMethods = explode(
            ',',
            $allowedMethodsValue
        );

        foreach ($allowedMethods as $allowedMethod) {
            $this->allowedMethods[] = [
                'method' => $allowedMethod,
                'label' => constant('self::LABEL_' . strtoupper($allowedMethod)),
                'countries' => constant('self::COUNTRIES_' . strtoupper($allowedMethod))
            ];
        }

        return $this->allowedMethods;
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
        if (empty($this->clientToken) && $this->isActive()) {
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
     * Get merchant account id
     *
     * @return string|null
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getMerchantAccountId(): ?string
    {
        return $this->braintreeConfig->getMerchantAccountId();
    }

    /**
     * Get payment icons
     *
     * @return array
     */
    public function getPaymentIcons(): array
    {
        return [
            self::VALUE_BANCONTACT => $this->assetRepo
                ->getUrl('PayPal_Braintree::images/' . self::VALUE_BANCONTACT . '.svg'),
            self::VALUE_EPS => $this->assetRepo
                ->getUrl('PayPal_Braintree::images/' . self::VALUE_EPS . '.svg'),
            self::VALUE_GIROPAY => $this->assetRepo
                ->getUrl('PayPal_Braintree::images/' . self::VALUE_GIROPAY . '.svg'),
            self::VALUE_IDEAL => $this->assetRepo
                ->getUrl('PayPal_Braintree::images/' . self::VALUE_IDEAL . '.svg'),
            self::VALUE_SOFORT => $this->assetRepo
                ->getUrl('PayPal_Braintree::images/' . self::VALUE_SOFORT . '.svg'),
            self::VALUE_MYBANK => $this->assetRepo
                ->getUrl('PayPal_Braintree::images/' . self::VALUE_MYBANK . '.svg'),
            self::VALUE_P24 => $this->assetRepo
                ->getUrl('PayPal_Braintree::images/' . self::VALUE_P24 . '.svg'),
            self::VALUE_SEPA => $this->assetRepo
                ->getUrl('PayPal_Braintree::images/' . self::VALUE_SEPA . '.svg')
        ];
    }

    /**
     * Get title
     *
     * @return string
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getTitle(): string
    {
        return $this->getValue(
            self::KEY_TITLE,
            $this->storeConfigResolver->getStoreId()
        );
    }

    /**
     * Get fallback url
     *
     * @return string
     */
    public function getFallbackUrl(): string
    {
        return $this->urlBuilder->getDirectUrl(self::LPM_FALLBACK_ACTION_URL);
    }

    /**
     * Get fallback button text
     *
     * @return string
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getFallbackButtonText(): string
    {
        return $this->getValue(
            self::KEY_FALLBACK_BUTTON_TEXT,
            $this->storeConfigResolver->getStoreId()
        );
    }

    /**
     * Get redirect url on fail
     *
     * @return mixed|null
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getRedirectUrlOnFail(): mixed
    {
        return $this->getValue(
            self::KEY_REDIRECT_ON_FAIL,
            $this->storeConfigResolver->getStoreId()
        );
    }
}
