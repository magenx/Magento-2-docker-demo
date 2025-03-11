<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Gateway\Request;

use Braintree\Customer;
use Braintree\CustomerSearch;
use Braintree\PaymentMethod;
use Braintree\Result\UsBankAccountVerification;
use PayPal\Braintree\Gateway\Config\Config as BraintreeConfig;
use PayPal\Braintree\Gateway\Helper\SubjectReader;
use PayPal\Braintree\Observer\DataAssignObserver;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Request\BuilderInterface;

class AchDataBuilder implements BuilderInterface
{
    public const OPTIONS = 'options';

    /**
     * @var SubjectReader
     */
    private SubjectReader $subjectReader;

    /**
     * @var BraintreeConfig $braintreeConfig
     */
    private BraintreeConfig $braintreeConfig;

    /**
     * AchDataBuilder constructor.
     *
     * @param SubjectReader $subjectReader
     * @param BraintreeConfig $braintreeConfig
     */
    public function __construct(
        SubjectReader $subjectReader,
        BraintreeConfig $braintreeConfig
    ) {
        $this->subjectReader = $subjectReader;
        $this->braintreeConfig = $braintreeConfig;
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     * @throws LocalizedException
     */
    public function build(array $buildSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $payment = $paymentDO->getPayment();
        $nonce = $payment->getAdditionalInformation(DataAssignObserver::PAYMENT_METHOD_NONCE);

        // Get customer details from the billing address
        $order = $paymentDO->getOrder();
        $billingAddress = $order->getBillingAddress();

        // Let's search for an existing customer
        $customers = Customer::search([
            CustomerSearch::email()->is($billingAddress->getEmail()),
            CustomerSearch::firstName()->is($billingAddress->getFirstname()),
            CustomerSearch::lastName()->is($billingAddress->getLastname())
        ]);

        if (empty($customers->getIds())) {
            // create customer and get ID
            $result = Customer::create([
                'email' => $billingAddress->getEmail(),
                'firstName' => $billingAddress->getFirstname(),
                'lastName' => $billingAddress->getLastname()
            ]);
            $customerId = $result->customer->id;
        } else {
            $customerId = $customers->getIds()[0];
        }

        $createRequest = [
            'customerId' => $customerId,
            'paymentMethodNonce' => $nonce,
            'options' => [
                'usBankAccountVerificationMethod' => UsBankAccountVerification::NETWORK_CHECK
            ]
        ];

        $merchantAccountId = $this->braintreeConfig->getMerchantAccountId($order->getStoreId());

        if (!empty($merchantAccountId)) {
            $createRequest['options']['verificationMerchantAccountId'] = $merchantAccountId;
        }

        $result = PaymentMethod::create($createRequest);

        if ($result->success) {
            /*
             * Set payment's bank account verified status in the additional information.
             * We do not vault before the transaction as per Braintree's documentation,
             * but after transaction is successful.
             * `network_check` verification method returns an instant & accurate result (confirmed with the team).
             *
             * @see https://developer.paypal.com/braintree/docs/guides/ach/client-side/javascript/v3/
             * @see https://developer.paypal.com/braintree/docs/guides/ach/server-side/php
             */
            $payment->setAdditionalInformation(
                'usBankAccountVerificationMethod',
                UsBankAccountVerification::NETWORK_CHECK
            );

            $payment->setAdditionalInformation(
                UsBankAccountVerification::VERIFIED,
                $result->paymentMethod->verified ?? false
            );

            return [
                'paymentMethodNonce' => null,
                'paymentMethodToken' => $result->paymentMethod->token
            ];
        }

        throw new LocalizedException(__('Failed to create payment token.'));
    }
}
