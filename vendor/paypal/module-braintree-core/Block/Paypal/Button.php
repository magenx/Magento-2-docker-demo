<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace PayPal\Braintree\Block\Paypal;

use Braintree\TransactionLineItem;
use Magento\Catalog\Block\ShortcutInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Model\MethodInterface;
use PayPal\Braintree\Gateway\Config\Config as BraintreeConfig;
use PayPal\Braintree\Gateway\Config\PayPal\Config;
use PayPal\Braintree\Gateway\Config\PayPalCredit\Config as PayPalCreditConfig;
use PayPal\Braintree\Gateway\Config\PayPalPayLater\Config as PayPalPayLaterConfig;
use PayPal\Braintree\Model\Ui\ConfigProvider;

/**
 * @api
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.0.2
 */
class Button extends Template implements ShortcutInterface
{
    public const ALIAS_ELEMENT_INDEX = 'alias';
    public const BUTTON_ELEMENT_INDEX = 'button_id';
    private const LINE_ITEMS_ARRAY = [
        'name',
        'kind',
        'quantity',
        'unitAmount',
        'productCode'
    ];

    /**
     * @var ResolverInterface $localeResolver
     */
    private ResolverInterface $localeResolver;

    /**
     * @var Session $checkoutSession
     */
    private Session $checkoutSession;

    /**
     * @var Config $config
     */
    protected Config $config;

    /**
     * @var BraintreeConfig $braintreeConfig
     */
    private BraintreeConfig $braintreeConfig;

    /**
     * @var ConfigProvider $configProvider
     */
    private ConfigProvider $configProvider;

    /**
     * @var MethodInterface $payment
     */
    private MethodInterface $payment;

    /**
     * @var PayPalCreditConfig $payPalCreditConfig
     */
    private PayPalCreditConfig $payPalCreditConfig;

    /**
     * @var PayPalPayLaterConfig $payPalPayLaterConfig
     */
    private PayPalPayLaterConfig $payPalPayLaterConfig;

    /**
     * Button constructor
     *
     * @param Context $context
     * @param ResolverInterface $localeResolver
     * @param Session $checkoutSession
     * @param Config $config
     * @param PayPalCreditConfig $payPalCreditConfig
     * @param PayPalPayLaterConfig $payPalPayLaterConfig
     * @param BraintreeConfig $braintreeConfig
     * @param ConfigProvider $configProvider
     * @param MethodInterface $payment
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        ResolverInterface $localeResolver,
        Session $checkoutSession,
        Config $config,
        PayPalCreditConfig $payPalCreditConfig,
        PayPalPayLaterConfig $payPalPayLaterConfig,
        BraintreeConfig $braintreeConfig,
        ConfigProvider $configProvider,
        MethodInterface $payment,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->localeResolver = $localeResolver;
        $this->checkoutSession = $checkoutSession;
        $this->config = $config;
        $this->braintreeConfig = $braintreeConfig;
        $this->configProvider = $configProvider;
        $this->payment = $payment;
        $this->payPalCreditConfig = $payPalCreditConfig;
        $this->payPalPayLaterConfig = $payPalPayLaterConfig;
    }

    /**
     * @inheritdoc
     */
    protected function _toHtml(): string
    {
        if ($this->isActive()) {
            return parent::_toHtml();
        }

        return '';
    }

    /**
     * @inheritdoc
     */
    public function getAlias(): string
    {
        return $this->getData(self::ALIAS_ELEMENT_INDEX);
    }

    /**
     * Get Container Id
     *
     * @return string
     */
    public function getContainerId(): string
    {
        return $this->getData(self::BUTTON_ELEMENT_INDEX);
    }

    /**
     * Get Locale
     *
     * @return string
     */
    public function getLocale(): string
    {
        return $this->localeResolver->getLocale();
    }

    /**
     * Get currency
     *
     * @return string|null
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getCurrency(): ?string
    {
        return $this->checkoutSession->getQuote()->getCurrency()->getBaseCurrencyCode();
    }

    /**
     * Get amount
     *
     * @return float
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getAmount()
    {
        return $this->checkoutSession->getQuote()->getBaseGrandTotal();
    }

    /**
     * Is active
     *
     * @return bool
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function isActive(): bool
    {
        return $this->payment->isAvailable($this->checkoutSession->getQuote()) &&
            $this->config->isDisplayShoppingCart();
    }

    /**
     * Is PayPal credit active
     *
     * @return bool
     */
    public function isCreditActive(): bool
    {
        return $this->payPalCreditConfig->isActive();
    }

    /**
     * Is PayPal pay later active
     *
     * @return bool
     */
    public function isPayLaterActive(): bool
    {
        return $this->payPalPayLaterConfig->isActive();
    }

    /**
     * Is Pay Later message active
     *
     * @param string $type
     * @return bool
     */
    public function isPayLaterMessageActive($type): bool
    {
        return $this->payPalPayLaterConfig->isMessageActive($type);
    }

    /**
     * Is show PayPal Button
     *
     * @param string $type
     * @param string $location
     * @return bool
     */
    public function showPayPalButton(string $type, string $location): bool
    {
        return $this->config->showPayPalButton($type, $location);
    }
    /**
     * Is Pay Later button active
     *
     * @param string $type
     * @return bool
     */
    public function isPayLaterButtonActive(string $type): bool
    {
        return $this->payPalPayLaterConfig->isButtonActive($type);
    }

    /**
     * Is PayPal vault active
     *
     * @return bool
     */
    public function isPayPalVaultActive(): bool
    {
        return $this->payPalPayLaterConfig->isPayPalVaultActive();
    }

    /**
     * Get Merchant Name
     *
     * @return string|null
     */
    public function getMerchantName(): ?string
    {
        return $this->config->getMerchantName();
    }

    /**
     * Get Button Shape
     *
     * @param string $type
     * @return string
     */
    public function getButtonShape(string $type): string
    {
        return $this->config->getButtonShape(Config::BUTTON_AREA_CART, $type);
    }

    /**
     * Get Button Color
     *
     * @param string $type
     * @return string
     */
    public function getButtonColor(string $type): string
    {
        if ($type === 'credit') {
            return $this->config->getCreditButtonColor(Config::BUTTON_AREA_CART);
        }
        return $this->config->getButtonColor(Config::BUTTON_AREA_CART, $type);
    }

    /**
     * Get Button Size
     *
     * @param string $type
     * @return string
     * @deprecated as Size field is redundant
     */
    public function getButtonSize(string $type): string
    {
        return $this->config->getButtonSize(Config::BUTTON_AREA_CART, $type);
    }

    /**
     * Get Button Label
     *
     * @param string $type
     * @return string
     */
    public function getButtonLabel(string $type): string
    {
        return $this->config->getButtonLabel(Config::BUTTON_AREA_CART, $type);
    }

    /**
     * Get Environment
     *
     * @return string
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getEnvironment(): string
    {
        return $this->braintreeConfig->getEnvironment();
    }

    /**
     * Get Client Token
     *
     * @return string|null
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getClientToken(): ?string
    {
        return $this->configProvider->getClientToken();
    }

    /**
     * Get Action Success
     *
     * @return string
     */
    public function getActionSuccess(): string
    {
        return $this->getUrl(ConfigProvider::CODE . '/paypal/review', ['_secure' => true]);
    }

    /**
     * Get Disabled Funding
     *
     * @return array
     */
    public function getDisabledFunding(): array
    {
        return [
            'card' => $this->config->isFundingOptionCardDisabled(Config::KEY_PAYPAL_DISABLED_FUNDING_CART),
            'elv' => $this->config->isFundingOptionElvDisabled(Config::KEY_PAYPAL_DISABLED_FUNDING_CART)
        ];
    }

    /**
     * Get Extra Class name
     *
     * @return string
     */
    public function getExtraClassname(): string
    {
        return $this->getIsCart() ? 'cart' : 'minicart';
    }

    /**
     * Is Required Billing Address
     *
     * @return bool
     */
    public function isRequiredBillingAddress(): bool
    {
        return (bool) $this->config->isRequiredBillingAddress();
    }

    /**
     * Get Merchant Country
     *
     * @return string|null
     */
    public function getMerchantCountry(): ?string
    {
        return $this->payPalPayLaterConfig->getMerchantCountry();
    }

    /**
     * Get Messaging Layout
     *
     * @param string $type
     * @return string
     */
    public function getMessagingLayout(string $type): string
    {
        return $this->config->getMessagingStyle(Config::BUTTON_AREA_CART, $type, 'layout');
    }

    /**
     * Get Messaging Logo
     *
     * @param string $type
     * @return string
     */
    public function getMessagingLogo(string $type): string
    {
        return $this->config->getMessagingStyle(Config::BUTTON_AREA_CART, $type, 'logo');
    }

    /**
     * Get Messaging Logo Position
     *
     * @param string $type
     * @return string
     */
    public function getMessagingLogoPosition(string $type): string
    {
        return $this->config->getMessagingStyle(Config::BUTTON_AREA_CART, $type, 'logo_position');
    }

    /**
     * Get Messaging Text Color
     *
     * @param string $type
     * @return string
     */
    public function getMessagingTextColor(string $type): string
    {
        return $this->config->getMessagingStyle(Config::BUTTON_AREA_CART, $type, 'text_color');
    }

    /**
     * Get cart line items
     *
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getCartLineItems(): array
    {
        $lineItems = [];
        if ($this->config->canSendCartLineItemsForPayPal()) {
            try {
                $quote = $this->checkoutSession->getQuote();
                foreach ($quote->getItems() as $item) {

                    // Skip configurable parent items and items with a base price of 0.
                    if ($item->getParentItem() || 0.0 === $item->getPrice()) {
                        continue;
                    }

                    // Regex to replace all unsupported characters.
                    $filteredFields = preg_replace(
                        '/[^a-zA-Z0-9\s\-.\']/',
                        '',
                        [
                            'name' => substr($item->getName(), 0, 127),
                            'sku' => substr($item->getSku(), 0, 127)
                        ]
                    );

                    $itemPrice = (float) $item->getPrice();
                    $lineItems[] = array_combine(
                        self::LINE_ITEMS_ARRAY,
                        [
                            $filteredFields['name'],
                            TransactionLineItem::DEBIT,
                            $this->numberToString((float)$item->getQty(), 2),
                            $this->numberToString($itemPrice, 2),
                            $filteredFields['sku']
                        ]
                    );
                }

                /**
                 * Adds credit (refund or discount) kind as LineItems for the
                 * PayPal transaction if discount amount is greater than 0(Zero)
                 * as discountAmount lineItem field is not being used by PayPal.
                 *
                 * https://developer.paypal.com/braintree/docs/reference/response/transaction-line-item/php#discount_amount
                 */
                $baseDiscountAmount = $this->numberToString(
                    abs($quote->getShippingAddress()->getBaseDiscountAmount()),
                    2
                );
                if ($baseDiscountAmount <= 0) {
                    $baseDiscountAmount = $this->numberToString(
                        abs($quote->getBillingAddress()->getBaseDiscountAmount()),
                        2
                    );
                }
                if ($baseDiscountAmount > 0) {
                    $discountLineItems[] = [
                        'name' => 'Discount',
                        'kind' => TransactionLineItem::CREDIT,
                        'quantity' => 1.00,
                        'unitAmount' => $baseDiscountAmount
                    ];

                    $lineItems = array_merge($lineItems, $discountLineItems);
                }

                /**
                 * Adds shipping as LineItems for the PayPal transaction
                 * if shipping amount is greater than 0(Zero) to manage
                 * the totals with client-side implementation as there is
                 * no any field exist in the client-side implementation
                 * to send the shipping amount to the Braintree.
                 */
                $baseShippingAmount = $this->numberToString(
                    abs($quote->getShippingAddress()->getBaseShippingAmount()),
                    2
                );
                if ($baseShippingAmount > 0) {
                    $shippingLineItem[] = [
                        'name' => 'Shipping',
                        'kind' => TransactionLineItem::DEBIT,
                        'quantity' => 1.00,
                        'unitAmount' => $baseShippingAmount
                    ];

                    $lineItems = array_merge($lineItems, $shippingLineItem);
                }

                /**
                 * Adds Tax as LineItems for the PayPal transaction
                 * if tax amount is greater than 0(Zero) to manage
                 * the totals with client-side implementation as the way
                 * of calculating the tax on items are really different
                 * in PayPal and Adobe Commerce that was causing issue.
                 */
                $baseTaxAmount = $this->numberToString($quote->getShippingAddress()->getBaseTaxAmount(), 2);
                if ($baseTaxAmount <= 0) {
                    $baseTaxAmount = $this->numberToString($quote->getBillingAddress()->getBaseTaxAmount(), 2);
                }
                if ($baseTaxAmount > 0) {
                    $taxLineItem[] = [
                        'name' => 'Tax',
                        'kind' => TransactionLineItem::DEBIT,
                        'quantity' => 1.00,
                        'unitAmount' => $baseTaxAmount
                    ];

                    $lineItems = array_merge($lineItems, $taxLineItem);
                }

                /**
                 * Adds Gift Wrapping for Order as LineItems for the PayPal
                 * transaction if it is greater than 0(Zero) to manage
                 * the totals with client-side implementation as there is
                 * no any field exist to send that amount to the Braintree.
                 */
                if ($quote->getGwBasePrice()) {
                    $gwBasePrice = $this->numberToString($quote->getGwBasePrice(), 2);
                    if ($gwBasePrice > 0) {
                        $gwBasePriceItems[] = [
                            'name' => 'Gift Wrapping for Order',
                            'kind' => TransactionLineItem::DEBIT,
                            'quantity' => 1.00,
                            'unitAmount' => $gwBasePrice,
                            'totalAmount' => $gwBasePrice
                        ];

                        $lineItems = array_merge($lineItems, $gwBasePriceItems);
                    }
                }

                /**
                 * Adds Gift Wrapping for items as LineItems for the PayPal
                 * transaction if it is greater than 0(Zero) to manage
                 * the totals with client-side implementation as there is
                 * no any field exist to send that amount to the Braintree.
                 */
                if ($quote->getGwItemsBasePrice()) {
                    $gwItemsBasePrice = $this->numberToString($quote->getGwItemsBasePrice(), 2);
                    if ($gwItemsBasePrice > 0) {
                        $gwItemsBasePriceItems[] = [
                            'name' => 'Gift Wrapping for Items',
                            'kind' => TransactionLineItem::DEBIT,
                            'quantity' => 1.00,
                            'unitAmount' => $gwItemsBasePrice,
                            'totalAmount' => $gwItemsBasePrice
                        ];

                        $lineItems = array_merge($lineItems, $gwItemsBasePriceItems);
                    }
                }

                /**
                 * Adds Gift Wrapping Printed Card as LineItems for the PayPal
                 * transaction if it is greater than 0(Zero) to manage
                 * the totals with client-side implementation as there is
                 * no any field exist to send that amount to the Braintree.
                 */
                if ($quote->getGwCardBasePrice()) {
                    $gwCardBasePrice = $this->numberToString($quote->getGwCardBasePrice(), 2);
                    if ($gwCardBasePrice > 0) {
                        $gwCardBasePriceItems[] = [
                            'name' => 'Printed Card',
                            'kind' => TransactionLineItem::DEBIT,
                            'quantity' => 1.00,
                            'unitAmount' => $gwCardBasePrice,
                            'totalAmount' => $gwCardBasePrice
                        ];

                        $lineItems = array_merge($lineItems, $gwCardBasePriceItems);
                    }
                }

                /**
                 * Adds Store Credit as credit LineItems for the PayPal
                 * transaction if store credit is greater than 0(Zero)
                 * to manage the totals with client-side implementation
                 * as there is no any field exist to send that amount
                 * to the Braintree.
                 */
                if ($quote->getBaseCustomerBalAmountUsed()) {
                    $baseCustomerBalAmountUsed = $this->numberToString(abs($quote->getBaseCustomerBalAmountUsed()), 2);
                    if ($baseCustomerBalAmountUsed > 0) {
                        $storeCreditItems[] = [
                            'name' => 'Store Credit',
                            'kind' => TransactionLineItem::CREDIT,
                            'quantity' => 1.00,
                            'unitAmount' => $baseCustomerBalAmountUsed,
                            'totalAmount' => $baseCustomerBalAmountUsed
                        ];

                        $lineItems = array_merge($lineItems, $storeCreditItems);
                    }
                }

                /**
                 * Adds Gift Cards as credit LineItems for the PayPal
                 * transaction if it is greater than 0(Zero) to manage
                 * the totals with client-side implementation as there is
                 * no any field exist to send that amount to the Braintree.
                 */
                if ($quote->getBaseGiftCardsAmountUsed()) {
                    $baseGiftCardsAmountUsed = $this->numberToString(abs($quote->getBaseGiftCardsAmountUsed()), 2);
                    if ($baseGiftCardsAmountUsed > 0) {
                        $giftCardsItems[] = [
                            'name' => 'Gift Cards',
                            'kind' => TransactionLineItem::CREDIT,
                            'quantity' => 1.00,
                            'unitAmount' => $baseGiftCardsAmountUsed,
                            'totalAmount' => $baseGiftCardsAmountUsed
                        ];

                        $lineItems = array_merge($lineItems, $giftCardsItems);
                    }
                }

                if (count($lineItems) >= 250) {
                    $lineItems = [];
                }
            } catch (NoSuchEntityException|LocalizedException $e) {
                $this->_logger->error($e->getMessage());
            }
        }

        return $lineItems;
    }

    /**
     * Number to string conversion
     *
     * @param float|string $num
     * @param int $precision
     * @return string
     */
    private function numberToString(float|string $num, int $precision): string
    {
        /**
         * To counter the fact that Magento often
         * wrongly returns a sting for price values,
         * we can cast it to a float.
         */
        if (is_string($num)) {
            $num = (float) $num;
        }

        return (string) round($num, $precision);
    }

    /**
     * Get button config
     *
     * @return array
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getButtonConfig(): array
    {
        return [
            'clientToken' => $this->getClientToken(),
            'currency' => $this->getCurrency(),
            'environment' => $this->getEnvironment(),
            'merchantCountry' => $this->getMerchantCountry(),
            'isCreditActive' => $this->isCreditActive()
        ];
    }
}
