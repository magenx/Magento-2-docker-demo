<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Model\Paypal\Helper;

use InvalidArgumentException;
use Magento\Directory\Model\Region;
use Magento\Framework\App\ResourceConnection;
use Magento\Quote\Model\Quote;
use Magento\Quote\Api\CartRepositoryInterface;
use PayPal\Braintree\Model\Ui\PayPal\ConfigProvider;
use Magento\Framework\Exception\LocalizedException;
use PayPal\Braintree\Observer\DataAssignObserver;
use PayPal\Braintree\Gateway\Config\PayPal\Config;
use Magento\Framework\Event\ManagerInterface;
use Magento\Quote\Model\Quote\Address;

class QuoteUpdater extends AbstractHelper
{
    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var CartRepositoryInterface
     */
    private CartRepositoryInterface $quoteRepository;

    /**
     * @var ManagerInterface
     */
    private ManagerInterface $eventManager;

    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resource;

    /**
     * @var Region
     */
    private Region $region;

    /**
     * Flag on whether shipping address holds the full name details.
     *
     * @var bool|null
     */
    private ?bool $hasShippingAddressFullName = null;

    /**
     * QuoteUpdater constructor
     *
     * @param Config $config
     * @param CartRepositoryInterface $quoteRepository
     * @param ManagerInterface $eventManager
     * @param ResourceConnection $resource
     * @param Region $region
     */
    public function __construct(
        Config $config,
        CartRepositoryInterface $quoteRepository,
        ManagerInterface $eventManager,
        ResourceConnection $resource,
        Region $region
    ) {
        $this->config = $config;
        $this->quoteRepository = $quoteRepository;
        $this->eventManager = $eventManager;
        $this->resource = $resource;
        $this->region = $region;
    }

    /**
     * Execute operation
     *
     * @param string $nonce
     * @param array $details
     * @param Quote $quote
     * @return void
     * @throws InvalidArgumentException
     * @throws LocalizedException
     */
    public function execute(string $nonce, array $details, Quote $quote): void
    {
        if (empty($nonce) || empty($details)) {
            throw new InvalidArgumentException('The "nonce" and "details" fields do not exist.');
        }

        $payment = $quote->getPayment();
        $payment->setMethod(ConfigProvider::PAYPAL_CODE);
        $payment->setAdditionalInformation(DataAssignObserver::PAYMENT_METHOD_NONCE, $nonce);
        $this->updateQuote($quote, $details);
    }

    /**
     * Update quote data
     *
     * @param Quote $quote
     * @param array $details
     * @return void
     */
    private function updateQuote(Quote $quote, array $details): void
    {
        $this->eventManager->dispatch('braintree_paypal_update_quote_before', [
            'quote' => $quote,
            'paypal_response' => $details
        ]);

        $quote->setMayEditShippingAddress(false);
        $quote->setMayEditShippingMethod(true);

        $this->updateQuoteAddress($quote, $details);
        $this->disabledQuoteAddressValidation($quote);

        $quote->collectTotals();

        /**
         * Unset shipping assignment to prevent from saving / applying outdated data
         * @see \Magento\Quote\Model\QuoteRepository\SaveHandler::processShippingAssignment
         */
        if ($quote->getExtensionAttributes()) {
            $quote->getExtensionAttributes()->setShippingAssignments(null);
        }

        $this->quoteRepository->save($quote);
        $this->cleanUpAddress($quote);

        $this->eventManager->dispatch('braintree_paypal_update_quote_after', [
            'quote' => $quote,
            'paypal_response' => $details
        ]);
    }

    /**
     * Clean up address
     *
     * @param Quote $quote
     */
    private function cleanUpAddress(Quote $quote): void
    {
        $connection = $this->resource->getConnection();
        $tableName = $this->resource->getTableName('quote_address');
        $connection->delete(
            $tableName,
            'quote_id = ' . (int) $quote->getId() . ' AND email IS NULL'
        );
    }

    /**
     * Update quote address
     *
     * @param Quote $quote
     * @param array $details
     * @return void
     */
    private function updateQuoteAddress(Quote $quote, array $details): void
    {
        if (!$quote->getIsVirtual()) {
            $this->updateShippingAddress($quote, $details);
        }

        $this->updateBillingAddress($quote, $details);
    }

    /**
     * Update shipping address
     * (PayPal doesn't provide detailed shipping info: prefix, suffix)
     *
     * @param Quote $quote
     * @param array $details
     * @return void
     */
    private function updateShippingAddress(Quote $quote, array $details): void
    {
        $shippingAddress = $quote->getShippingAddress();

        // Default
        $shippingAddress->setFirstname($details['firstName']);
        $shippingAddress->setLastname($details['lastName']);
        $shippingAddress->setEmail($details['email']);

        // If full shipping address name is provided, use it.
        if ($this->hasShippingFullName($details)) {
            $shippingAddress->setFirstname($details['shippingAddress']['recipientFirstName']);
            $shippingAddress->setLastname($details['shippingAddress']['recipientLastName']);
        }

        $shippingAddress->setCollectShippingRates(true);

        $this->updateAddressData($shippingAddress, $details['shippingAddress']);

        // PayPal's address supposes not saving against customer account
        $shippingAddress->setSaveInAddressBook(false);
        $shippingAddress->setSameAsBilling(false);
        $shippingAddress->unsCustomerAddressId();
        $shippingAddress->setCustomerAddressId(null);
    }

    /**
     * Update billing address
     *
     * @param Quote $quote
     * @param array $details
     * @return void
     */
    private function updateBillingAddress(Quote $quote, array $details): void
    {
        $billingAddress = $quote->getBillingAddress();

        // If full shipping address name is set, use it as default, otherwise use account's name.
        $billingAddress->setFirstname(
            $this->hasShippingFullName($details)
                ? $details['shippingAddress']['recipientFirstName']
                : $details['firstName']
        );

        $billingAddress->setLastname(
            $this->hasShippingFullName($details)
                ? $details['shippingAddress']['recipientLastName']
                : $details['lastName']
        );

        $billingAddress->setEmail($details['email']);

        if (!empty($details['billingAddress']) && $this->config->isRequiredBillingAddress()) {
            $billingAddress->setFirstname($details['firstName']);
            $billingAddress->setLastname($details['lastName']);

            $this->updateAddressData($billingAddress, $details['billingAddress']);
        } else {
            $this->updateAddressData($billingAddress, $details['shippingAddress']);
        }

        // PayPal's address supposes not saving against customer account
        $billingAddress->setSaveInAddressBook(false);
        $billingAddress->setSameAsBilling(false);
        $billingAddress->setCustomerAddressId(null);
    }

    /**
     * Sets address data from exported address
     *
     * @param Address $address
     * @param array $addressData
     * @return void
     */
    private function updateAddressData(Address $address, array $addressData): void
    {
        $street = $addressData['streetAddress'];

        if (isset($addressData['extendedAddress'])) {
            $street .= ' ' . $addressData['extendedAddress'];
        }
        $address->setStreet($street);
        $address->setCity($addressData['locality']);

        $address->setRegion($addressData['region']);

        // Setting the region is not enough, we have to set the region ID.
        $regionId = $this->region->loadByCode($addressData['region'], $addressData['countryCodeAlpha2'])->getId();
        $address->setRegionId($regionId);

        $address->setCountryId($addressData['countryCodeAlpha2']);
        $address->setPostcode($addressData['postalCode']);

        if (!empty($addressData['telephone'])) {
            $address->setTelephone($addressData['telephone']);
        }

        // PayPal's address supposes not saving against customer account
        $address->setSaveInAddressBook(false);
        $address->setSameAsBilling(false);
    }

    /**
     * Validate whether both first & last name is included in the shipping data.
     *
     * @param array $data
     * @return bool
     */
    private function hasShippingFullName(array $data): bool
    {
        if ($this->hasShippingAddressFullName !== null) {
            return $this->hasShippingAddressFullName;
        }

        $this->hasShippingAddressFullName =
            isset($data['shippingAddress']['recipientFirstName'], $data['shippingAddress']['recipientLastName'])
            && trim($data['shippingAddress']['recipientFirstName']) !== ''
            && trim($data['shippingAddress']['recipientLastName']) !== '';

        return $this->hasShippingAddressFullName;
    }
}
