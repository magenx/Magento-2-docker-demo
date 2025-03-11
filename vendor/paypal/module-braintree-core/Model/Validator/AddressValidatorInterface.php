<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Model\Validator;

use Magento\Customer\Api\Data\AddressInterface;
use Magento\Framework\Validation\ValidationResult;

interface AddressValidatorInterface
{
    /**
     * Validate Address data.
     *
     * @param AddressInterface $address
     * @return ValidationResult
     */
    public function validate(AddressInterface $address): ValidationResult;
}
