<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace PayPal\Braintree\Model\GooglePay\Helper;

use InvalidArgumentException;
use Magento\Directory\Model\Region;
use Magento\Framework\App\ResourceConnection;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Api\CartRepositoryInterface;
use PayPal\Braintree\Model\GooglePay\Ui\ConfigProvider;
use Magento\Framework\Exception\LocalizedException;
use PayPal\Braintree\Observer\DataAssignObserver;
use PayPal\Braintree\Model\Paypal\Helper\AbstractHelper;
use Magento\Framework\Event\ManagerInterface;
use PayPal\Braintree\Observer\GooglePay\DataAssignObserver as GooglePayDataAssignObserver;

class QuoteUpdater extends AbstractHelper
{
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
     * QuoteUpdater constructor
     *
     * @param CartRepositoryInterface $quoteRepository
     * @param ManagerInterface $eventManager
     * @param ResourceConnection $resource
     * @param Region $region
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        ManagerInterface $eventManager,
        ResourceConnection $resource,
        Region $region
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->eventManager = $eventManager;
        $this->resource = $resource;
        $this->region = $region;
    }

    /**
     * Execute operation
     *
     * @param string $nonce
     * @param bool $isCardNetworkTokenized
     * @param array|string $deviceData
     * @param array $details
     * @param Quote $quote
     * @return void
     * @throws LocalizedException
     */
    public function execute(
        string $nonce,
        bool $isCardNetworkTokenized,
        array|string $deviceData,
        array $details,
        Quote $quote
    ): void {
        if (empty($nonce) || empty($details)) {
            throw new InvalidArgumentException('The "nonce" and/or "details" fields do not exists');
        }

        $payment = $quote->getPayment();
        $payment->setMethod(ConfigProvider::METHOD_CODE);
        $payment->setAdditionalInformation(DataAssignObserver::PAYMENT_METHOD_NONCE, $nonce);
        $payment->setAdditionalInformation(
            GooglePayDataAssignObserver::IS_CARD_NETWORK_TOKENIZED,
            $isCardNetworkTokenized
        );
        $payment->setAdditionalInformation(DataAssignObserver::DEVICE_DATA, $deviceData);
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
        $this->eventManager->dispatch('braintree_googlepay_update_quote_before', [
            'quote' => $quote,
            'googlepay_response' => $details
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

        $this->eventManager->dispatch('braintree_googlepay_update_quote_after', [
            'quote' => $quote,
            'googlepay_response' => $details
        ]);
    }

    /**
     * Clean up quote address
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
     * (Google Pay doesn't provide detailed shipping info: prefix, suffix)
     *
     * @param Quote $quote
     * @param array $details
     * @return void
     */
    private function updateShippingAddress(Quote $quote, array $details): void
    {
        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->setCollectShippingRates(true);
        $this->updateAddressData($shippingAddress, $details['shippingAddress']);

        // Google Pay's address supposes not saving against customer account
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
        $this->updateAddressData($billingAddress, $details['billingAddress']);

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
            $street = $street . ' ' . $addressData['extendedAddress'];
        }

        $name = explode(' ', $addressData['name'], 2);

        $address->setEmail($addressData['email']);
        $address->setFirstname($name[0]);
        $address->setLastname($name[1] ?? '');

        $address->setStreet($street);
        $address->setCity($addressData['locality']);

        if (empty($addressData['region'])) {
            $address->unsRegion();
            $address->setRegionCode(null);
        } else {
            $address->setRegionCode($addressData['region']);
        }

        // Setting the region is not enough, we have to set the region ID.
        $regionId = $this->region->loadByCode(
            $addressData['region'],
            $addressData['countryCodeAlpha2']
        )->getId();
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
}
