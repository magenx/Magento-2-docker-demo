<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Model\Validator;

use Magento\Framework\Validation\ValidationResult;
use Magento\Framework\Validation\ValidationResultFactory;
use Magento\Framework\Validator\StringLength;
use Magento\Framework\Validator\StringLengthFactory;
use PayPal\Braintree\Api\Data\PaymentInterface;
use PayPal\Braintree\Model\Ui\ConfigProvider as BraintreeConfigProvider;
use PayPal\Braintree\Model\Ui\PayPal\ConfigProvider as BraintreePayPalConfigProvider;

class PaymentValidator implements PaymentValidatorInterface
{
    /**
     * @var ValidationResultFactory
     */
    private ValidationResultFactory $validationResultFactory;

    /**
     * @var StringLengthFactory
     */
    private StringLengthFactory $stringLengthValidatorFactory;

    /**
     * @var array
     */
    private array $errors = [];

    /**
     * @param ValidationResultFactory $validationResultFactory
     * @param StringLengthFactory $stringLengthValidatorFactory
     */
    public function __construct(
        ValidationResultFactory $validationResultFactory,
        StringLengthFactory $stringLengthValidatorFactory
    ) {
        $this->validationResultFactory = $validationResultFactory;
        $this->stringLengthValidatorFactory = $stringLengthValidatorFactory;
    }

    /**
     * Validate Payment data.
     *
     * @param PaymentInterface $payment
     * @return ValidationResult
     */
    public function validate(PaymentInterface $payment): ValidationResult
    {
        // Validate Payment Method nonce.
        if (!$this->getStringLengthValidator()->isValid($payment->getPaymentMethodNonce())) {
            $this->addRequiredError('Payment method nonce');
        }

        if ($payment->getPaymentMethodCode() === null) {
            $this->addRequiredError('Payment method');
        }

        if ($payment->getPaymentMethodCode() !== null
            && !in_array($payment->getPaymentMethodCode(), $this->getAllowedPaymentMethodCodes(), true)
        ) {
            $this->addInvalidError('Payment method');
        }

        // Validate device data if present.
        if ($payment->getDeviceData() !== null
            && !$this->getStringLengthValidator()->isValid($payment->getDeviceData())
        ) {
            $this->addInvalidError('Device data');
        }

        return $this->validationResultFactory->create(['errors' => $this->errors]);
    }

    /**
     * Get an instance of the string length validator with minimum 1 character length.
     *
     * @return StringLength
     */
    private function getStringLengthValidator(): StringLength
    {
        return $this->stringLengthValidatorFactory->create(['min' => 1]);
    }

    /**
     * Add required error
     *
     * @param string $attribute
     * @return void
     */
    private function addRequiredError(string $attribute): void
    {
        $this->errors[] = __('%1 is required', $attribute);
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

    /**
     * Get the allowed payment methods.
     *
     * We currently only support Braintree Cards & PayPal
     *
     * @return array
     */
    private function getAllowedPaymentMethodCodes(): array
    {
        return [
            BraintreeConfigProvider::CODE,
            BraintreePayPalConfigProvider::PAYPAL_CODE
        ];
    }
}
