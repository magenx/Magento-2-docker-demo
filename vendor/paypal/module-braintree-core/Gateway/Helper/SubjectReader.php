<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace PayPal\Braintree\Gateway\Helper;

use Braintree\CreditCard;
use Braintree\Customer;
use Braintree\PayPalAccount;
use Braintree\Transaction;
use InvalidArgumentException;
use Magento\Payment\Gateway\Helper;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use PayPal\Braintree\Gateway\Data\AddressAdapterInterface;
use PayPal\Braintree\Gateway\Data\PaymentAdapterInterface;

class SubjectReader
{
    /**
     * Reads response object from subject
     *
     * @param array $subject
     * @return object
     */
    public function readResponseObject(array $subject)
    {
        $response = Helper\SubjectReader::readResponse($subject);
        if (!isset($response['object']) || !is_object($response['object'])) {
            throw new InvalidArgumentException(__('Response object does not exist'));
        }

        return $response['object'];
    }

    /**
     * Reads payment from subject
     *
     * @param array $subject
     * @return PaymentDataObjectInterface
     */
    public function readPayment(array $subject): PaymentDataObjectInterface
    {
        return Helper\SubjectReader::readPayment($subject);
    }

    /**
     * Reads transaction from subject
     *
     * @param array $subject
     * @return Transaction
     */
    public function readTransaction(array $subject): Transaction
    {
        if (!isset($subject['object']) || !is_object($subject['object'])) {
            throw new InvalidArgumentException(__('Response object does not exist'));
        }

        if (!isset($subject['object']->transaction)
            && !$subject['object']->transaction instanceof Transaction
        ) {
            throw new InvalidArgumentException(__('The object is not a class \Braintree\Transaction.'));
        }

        return $subject['object']->transaction;
    }

    /**
     * Reads amount from subject
     *
     * @param array $subject
     * @return mixed
     */
    public function readAmount(array $subject)
    {
        return Helper\SubjectReader::readAmount($subject);
    }

    /**
     * Reads customer id from subject
     *
     * @param array $subject
     * @return int
     */
    public function readCustomerId(array $subject): int
    {
        if (!isset($subject['customer_id'])) {
            throw new InvalidArgumentException(__('The "customerId" field does not exists'));
        }

        return (int) $subject['customer_id'];
    }

    /**
     * Reads public hash from subject
     *
     * @param array $subject
     * @return string
     */
    public function readPublicHash(array $subject): string
    {
        if (empty($subject[PaymentTokenInterface::PUBLIC_HASH])) {
            throw new InvalidArgumentException(__('The "public_hash" field does not exists'));
        }

        return $subject[PaymentTokenInterface::PUBLIC_HASH];
    }

    /**
     * Reads PayPal details from transaction object
     *
     * @param Transaction $transaction
     * @return array
     */
    public function readPayPal(Transaction $transaction): array
    {
        if (!isset($transaction->paypal)) {
            throw new InvalidArgumentException(__('Transaction has not paypal attribute'));
        }

        return $transaction->paypal;
    }

    /**
     * Reads Local Payment details from transaction object
     *
     * @param Transaction $transaction
     * @return array
     */
    public function readLocalPayment(Transaction $transaction): array
    {
        if (!isset($transaction->localPayment)) {
            throw new InvalidArgumentException(__('Transaction has not localPayment attribute'));
        }

        return $transaction->localPayment;
    }

    /**
     * Reads Braintree customer from subject
     *
     * @param array $subject
     * @return Customer
     */
    public function readCustomer(array $subject): Customer
    {
        if (!isset($subject['object']) || !is_object($subject['object'])) {
            throw new InvalidArgumentException(__('Response object does not exist'));
        }

        if (!isset($subject['object']->customer) || !$subject['object']->customer instanceof Customer) {
            throw new InvalidArgumentException(__('The object is not a class \Braintree\Customer.'));
        }

        return $subject['object']->customer;
    }

    /**
     * Read Braintree customer id
     *
     * @param array $subject
     * @return string
     */
    public function readBraintreeCustomerId(array $subject): string
    {
        if (!isset($subject['braintreeCustomerId'])) {
            throw new InvalidArgumentException(__('The "braintreeCustomerId" field does not exists'));
        }

        return $subject['braintreeCustomerId'];
    }

    /**
     * Get the Braintree Payment Method object from the response.
     *
     * @param array $subject
     * @return CreditCard|PayPalAccount
     */
    public function readPaymentMethod(array $subject): CreditCard|PayPalAccount
    {
        if (!isset($subject['object']) || !is_object($subject['object'])) {
            throw new InvalidArgumentException(__('Response object does not exist'));
        }

        if (!isset($subject['object']->paymentMethod)) {
            throw new InvalidArgumentException(__('The paymentMethod object does not exist.'));
        }

        return $subject['object']->paymentMethod;
    }

    /**
     * Read payment method data
     *
     * @param array $subject
     * @return PaymentAdapterInterface
     */
    public function readPaymentMethodData(array $subject): PaymentAdapterInterface
    {
        if (!isset($subject['paymentMethodData'])
            || !$subject['paymentMethodData'] instanceof PaymentAdapterInterface
            || $subject['paymentMethodData']->getPaymentMethodNonce() === null
        ) {
            throw new InvalidArgumentException(__('Invalid payment method data object.'));
        }

        return $subject['paymentMethodData'];
    }

    /**
     * Read address data
     *
     * @param array $subject
     * @return AddressAdapterInterface|null
     */
    public function readAddressData(array $subject): ?AddressAdapterInterface
    {
        if (!isset($subject['addressData'])) {
            return null;
        }

        if (!$subject['addressData'] instanceof AddressAdapterInterface) {
            throw new InvalidArgumentException(__('Invalid address data object.'));
        }

        return $subject['addressData'];
    }
}
