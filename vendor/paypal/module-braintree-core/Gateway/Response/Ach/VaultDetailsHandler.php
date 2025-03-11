<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Gateway\Response\Ach;

use Braintree\Result\UsBankAccountVerification;
use Braintree\Transaction;
use DateInterval;
use DateTimeZone;
use Exception;
use InvalidArgumentException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use PayPal\Braintree\Gateway\Helper\SubjectReader;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class VaultDetailsHandler implements HandlerInterface
{
    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    /**
     * @var PaymentTokenFactoryInterface
     */
    public PaymentTokenFactoryInterface $paymentTokenFactory;

    /**
     * @var OrderPaymentExtensionInterfaceFactory
     */
    private OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory;

    /**
     * @var SubjectReader
     */
    private SubjectReader $subjectReader;

    /**
     * @var DateTimeFactory
     */
    private DateTimeFactory $dateTimeFactory;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Constructor
     *
     * @param SerializerInterface $serializer
     * @param PaymentTokenFactoryInterface $paymentTokenFactory
     * @param OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory
     * @param SubjectReader $subjectReader
     * @param DateTimeFactory $dateTimeFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        SerializerInterface $serializer,
        PaymentTokenFactoryInterface $paymentTokenFactory,
        OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory,
        SubjectReader $subjectReader,
        DateTimeFactory $dateTimeFactory,
        LoggerInterface $logger
    ) {
        $this->serializer = $serializer;
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->paymentExtensionFactory = $paymentExtensionFactory;
        $this->subjectReader = $subjectReader;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     *
     * @throws Exception
     */
    public function handle(array $handlingSubject, array $response): void
    {
        $payment = $this->subjectReader->readPayment($handlingSubject)->getPayment();
        $transaction = $this->subjectReader->readTransaction($response);

        /*
         * Add vault payment token entity to extension attributes only if account has been verified on Payment Create.
         * The Payment Create process is done on the AchDataBuilder before Transaction is processed.
         */
        $paymentToken = $payment->getAdditionalInformation(UsBankAccountVerification::VERIFIED) === true ?
            $this->getVaultPaymentToken($transaction)
            : null;

        if ($paymentToken !== null) {
            $extensionAttributes = $this->getExtensionAttributes($payment);
            $extensionAttributes->setVaultPaymentToken($paymentToken);
        }
    }

    /**
     * Get vault payment token entity
     *
     * @param Transaction $transaction
     * @return PaymentTokenInterface|null
     * @throws Exception
     */
    private function getVaultPaymentToken(Transaction $transaction): ?PaymentTokenInterface
    {
        // No token if it does not exist.
        if (!isset($transaction->usBankAccount, $transaction->usBankAccount->token)
            || empty($transaction->usBankAccount->token)
        ) {
            return null;
        }

        $paymentToken = $this->paymentTokenFactory->create(PaymentTokenFactoryInterface::TOKEN_TYPE_ACCOUNT);
        $paymentToken->setGatewayToken($transaction->usBankAccount->token);
        $paymentToken->setExpiresAt($this->getExpirationDate());

        try {
            $details = $this->serializer->serialize([
                'customerId' => $transaction->customerDetails->id ?? null,
                'bankName' => $transaction->usBankAccount->bankName ?? null,
                'accountHolderName' => $transaction->usBankAccount->accountHolderName ?? null,
                'last4' => $transaction->usBankAccount->last4 ?? null, // Last 4 digits of bank account.
                'accountType' => $transaction->usBankAccount->accountType ?? null,
                'routingNumber' => $transaction->usBankAccount->routingNumber ?? null // Bank Routing number
            ]);
        } catch (InvalidArgumentException $ex) {
            $this->logger->error('Failed to serialize ACH vault token details with error: ' . $ex->getMessage(), [
                'payment_token' => $transaction->usBankAccount->token,
                'payment_transaction_id' => $transaction->id ?? null
            ]);

            $details = '{}';
        }

        $paymentToken->setTokenDetails($details);

        return $paymentToken;
    }

    /**
     * Get expiration date
     *
     * @return string
     * @throws Exception
     */
    private function getExpirationDate(): string
    {
        $expDate = $this->dateTimeFactory->create('now', new DateTimeZone('UTC'));
        $expDate->add(new DateInterval('P1Y'));
        return $expDate->format('Y-m-d 00:00:00');
    }

    /**
     * Get payment extension attributes
     *
     * @param InfoInterface $payment
     * @return OrderPaymentExtensionInterface
     */
    private function getExtensionAttributes(InfoInterface $payment): OrderPaymentExtensionInterface
    {
        $extensionAttributes = $payment->getExtensionAttributes();

        if ($extensionAttributes === null) {
            $extensionAttributes = $this->paymentExtensionFactory->create();
            $payment->setExtensionAttributes($extensionAttributes);
        }

        return $extensionAttributes;
    }
}
