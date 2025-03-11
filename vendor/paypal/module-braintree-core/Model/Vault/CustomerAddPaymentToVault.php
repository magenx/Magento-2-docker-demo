<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Model\Vault;

use Magento\Framework\Exception\NotFoundException;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Validation\ValidationException;
use Magento\Store\Model\StoreManagerInterface;
use PayPal\Braintree\Api\CustomerAddPaymentToVaultInterface;
use PayPal\Braintree\Api\Data\PaymentInterface;
use PayPal\Braintree\Model\Validator\AddressValidatorInterface;
use PayPal\Braintree\Model\Validator\PaymentValidatorInterface;
use Psr\Log\LoggerInterface;

class CustomerAddPaymentToVault implements CustomerAddPaymentToVaultInterface
{
    /**
     * @var CustomerRepositoryInterface
     */
    private CustomerRepositoryInterface $customerRepository;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var PaymentValidatorInterface
     */
    private PaymentValidatorInterface $paymentValidator;

    /**
     * @var AddressValidatorInterface
     */
    private AddressValidatorInterface $addressValidator;

    /**
     * @var AddPaymentToVaultInterface
     */
    private AddPaymentToVaultInterface $addPaymentToVault;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param CustomerRepositoryInterface $customerRepository
     * @param StoreManagerInterface $storeManager
     * @param AddressValidatorInterface $addressValidator
     * @param PaymentValidatorInterface $paymentValidator
     * @param AddPaymentToVaultInterface $addPaymentToVault
     * @param LoggerInterface $logger
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        StoreManagerInterface $storeManager,
        AddressValidatorInterface $addressValidator,
        PaymentValidatorInterface $paymentValidator,
        AddPaymentToVaultInterface $addPaymentToVault,
        LoggerInterface $logger
    ) {
        $this->customerRepository = $customerRepository;
        $this->storeManager = $storeManager;
        $this->addressValidator = $addressValidator;
        $this->paymentValidator = $paymentValidator;
        $this->addPaymentToVault = $addPaymentToVault;
        $this->logger = $logger;
    }

    /**
     * Vault a Payment nonce for a customer.
     *
     * Billing address is optional but advised for Card vaulting.
     *
     * @param int $customerId
     * @param PaymentInterface $payment
     * @param AddressInterface|null $billingAddress
     * @return bool
     * @throws ValidationException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute(
        int $customerId,
        PaymentInterface $payment,
        ?AddressInterface $billingAddress = null
    ): bool {
        try {
            $customer = $this->customerRepository->getById($customerId);
        } catch (NoSuchEntityException $ex) {
            $this->logger->error('Failed to get customer to add payment to vault: ' . $ex->getMessage(), [
                'class' => CustomerAddPaymentToVault::class,
                'customer_id' => $customerId
            ]);

            throw new NotFoundException(__('Resource not found'));
        }

        $this->validatePayment($payment);

        if ($billingAddress !== null && $billingAddress->getCountryId()) {
            $this->validateAddress($billingAddress);
        } else {
            $billingAddress = null;
        }

        if (!$this->addPaymentToVault->execute(
            $customer,
            $payment,
            $billingAddress,
            (int) $this->storeManager->getStore()->getId()
        )) {
            throw new NotFoundException(__('Failed to save Payment Details to vault'));
        }

        return true;
    }

    /**
     * Validate payment
     *
     * @param PaymentInterface $payment
     * @return void
     * @throws ValidationException
     */
    private function validatePayment(PaymentInterface $payment): void
    {
        $paymentValidationResult = $this->paymentValidator->validate($payment);

        if ($paymentValidationResult->isValid()) {
            return;
        }

        throw new ValidationException(__('Invalid Payment data'), null, 422, $paymentValidationResult);
    }

    /**
     * Validate address
     *
     * @param AddressInterface|null $address
     * @return void
     * @throws ValidationException
     */
    private function validateAddress(?AddressInterface $address = null): void
    {
        if ($address === null) {
            return;
        }

        $addressValidationResult = $this->addressValidator->validate($address);

        if ($addressValidationResult->isValid()) {
            return;
        }

        throw new ValidationException(__('Invalid Address data'), null, 422, $addressValidationResult);
    }
}
