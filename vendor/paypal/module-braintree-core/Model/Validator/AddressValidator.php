<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Model\Validator;

use Magento\Customer\Api\Data\AddressInterface;
use Magento\Framework\Validation\ValidationResult;
use Magento\Framework\Validation\ValidationResultFactory;
use Magento\Framework\Validator\StringLengthFactory;

class AddressValidator implements AddressValidatorInterface
{
    /**
     * @var ValidationResultFactory
     */
    private ValidationResultFactory $validationResultFactory;

    /**
     * @var array
     */
    private array $errors = [];

    /**
     * @param ValidationResultFactory $validationResultFactory
     */
    public function __construct(ValidationResultFactory $validationResultFactory)
    {
        $this->validationResultFactory = $validationResultFactory;
    }

    /**
     * Validate Address data.
     *
     * @param AddressInterface $address
     * @return ValidationResult
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function validate(AddressInterface $address): ValidationResult
    {
        if ($address->getFirstName() === null) {
            $this->addInvalidError('First name');
        }

        if ($address->getLastName() === null) {
            $this->addInvalidError('Last name');
        }

        if ($address->getCompany() === null) {
            $this->addInvalidError('Company name');
        }

        $street = $address->getStreet();

        // Check address line if exists.
        if (!isset($street[0])) {
            $this->addInvalidError('Street address');
        }

        $extendedAddress = null;

        // if address line 2 exists, remove the first, and check the rest of address lines merged.
        if (isset($street[1])) {
            array_shift($street);

            $extendedAddress = implode(', ', $street);
        }

        if ($extendedAddress === null) {
            $this->addInvalidError('Street address lines');
        }

        if ($address->getCity() === null) {
            $this->addInvalidError('City');
        }

        if ($address->getPostcode() == null) {
            $this->addInvalidError('Post code');
        }

        if ($address->getRegion() === null) {
            $this->addInvalidError('State/Region');
        }

        // Create country validator and set min 2 & max to 2 according to docs (code alpha-2).
        if ($address->getCountryId() === null) {
            $this->addInvalidError('Country');
        }

        return $this->validationResultFactory->create(['errors' => $this->errors]);
    }

    /**
     * Add Invalid error
     *
     * @param string $attribute
     * @return void
     */
    private function addInvalidError(string $attribute): void
    {
        $this->errors[] = __('Invalid %1', $attribute);
    }
}
