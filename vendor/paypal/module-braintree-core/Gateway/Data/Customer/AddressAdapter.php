<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Gateway\Data\Customer;

use Magento\Customer\Api\Data\AddressInterface as MagentoCustomerAddressInterface;
use Magento\Framework\Escaper;
use PayPal\Braintree\Gateway\Data\AddressAdapterInterface;

/**
 * Address Adapter for Customer Address.
 */
class AddressAdapter implements AddressAdapterInterface
{
    /**
     * @var MagentoCustomerAddressInterface
     */
    private MagentoCustomerAddressInterface $address;

    /**
     * @var Escaper
     */
    private Escaper $escaper;

    /**
     * @param MagentoCustomerAddressInterface $address
     * @param Escaper $escaper
     */
    public function __construct(
        MagentoCustomerAddressInterface $address,
        Escaper $escaper
    ) {
        $this->address = $address;
        $this->escaper = $escaper;
    }

    /**
     * Get region name
     *
     * @return string
     */
    public function getRegionCode(): string
    {
        $region = $this->address->getRegion();

        if ($region === null) {
            return '';
        }

        return $region->getRegionCode() === null ? '' : $this->escaper->escapeHtml($region->getRegionCode());
    }

    /**
     * Get country id
     *
     * @return string
     */
    public function getCountryId(): string
    {
        return $this->address->getCountryId() === null
            ? ''
            : $this->escaper->escapeHtml($this->address->getCountryId());
    }

    /**
     * Get street line 1
     *
     * @return string
     */
    public function getStreetLine1(): string
    {
        $street = $this->address->getStreet();

        return isset($street[0]) ? $this->escaper->escapeHtml($street[0]) : '';
    }

    /**
     * Get street line 2
     *
     * @return string
     */
    public function getStreetLine2(): string
    {
        $street = $this->address->getStreet();

        return isset($street[1]) ? $this->escaper->escapeHtml($street[1]) : '';
    }

    /**
     * Get telephone number
     *
     * @return string
     */
    public function getTelephone(): string
    {
        return $this->address->getTelephone() === null
            ? ''
            : $this->escaper->escapeHtml($this->address->getTelephone());
    }

    /**
     * Get postcode
     *
     * @return string
     */
    public function getPostcode(): string
    {
        return $this->address->getPostcode() === null ? '' : $this->escaper->escapeHtml($this->address->getPostcode());
    }

    /**
     * Get city name
     *
     * @return string
     */
    public function getCity(): string
    {
        return $this->address->getCity() === null ? '' : $this->escaper->escapeHtml($this->address->getCity());
    }

    /**
     * Get first name
     *
     * @return string
     */
    public function getFirstname(): string
    {
        return $this->address->getFirstname() === null
            ? ''
            : $this->escaper->escapeHtml($this->address->getFirstname());
    }

    /**
     * Get last name
     *
     * @return string
     */
    public function getLastname(): string
    {
        return $this->address->getLastname() === null ? '' : $this->escaper->escapeHtml($this->address->getLastname());
    }

    /**
     * Get middle name
     *
     * @return string|null
     */
    public function getMiddlename(): ?string
    {
        return $this->address->getMiddlename() === null
            ? ''
            : $this->escaper->escapeHtml($this->address->getMiddlename());
    }

    /**
     * Get customer id
     *
     * @return int|null
     */
    public function getCustomerId(): ?int
    {
        return $this->address->getCustomerId() === null ? null : (int) $this->address->getCustomerId();
    }

    /**
     * Get billing/shipping email
     *
     * No email against a customer address record.
     *
     * @return string
     */
    public function getEmail(): string
    {
        return '';
    }

    /**
     * Returns name prefix
     *
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->address->getPrefix() === null ? '' : $this->escaper->escapeHtml($this->address->getPrefix());
    }

    /**
     * Returns name suffix
     *
     * @return string
     */
    public function getSuffix(): string
    {
        return $this->address->getSuffix() === null ? '' : $this->escaper->escapeHtml($this->address->getSuffix());
    }

    /**
     * Get company
     *
     * @return string
     */
    public function getCompany(): string
    {
        return $this->address->getCompany() === null ? '' : $this->escaper->escapeHtml($this->address->getCompany());
    }

    /**
     * Gets the street values
     *
     * @return string[]|null
     */
    public function getStreet(): ?array
    {
        $street = $this->address->getStreet();

        if ($street === null) {
            return null;
        }

        // Return an array with escaped values.
        return array_map(
            function ($value) {
                return $this->escaper->escapeHtml($value);
            },
            $street
        );
    }
}
