<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Gateway\Response\Venmo;

use Braintree\Transaction;
use DateInterval;
use DateTimeZone;
use Exception;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use PayPal\Braintree\Gateway\Response\Handler;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class VaultDetailsHandler extends Handler implements HandlerInterface
{
    /**
     * @inheritdoc
     */
    public function handle(array $handlingSubject, array $response): void
    {
        $paymentDO = $this->subjectReader->readPayment($handlingSubject);
        $transaction = $this->subjectReader->readTransaction($response);
        $payment = $paymentDO->getPayment();

        // add vault payment token entity to extension attributes
        $paymentToken = $this->getVaultPaymentToken($transaction);
        if (null !== $paymentToken) {
            $extensionAttributes = $this->getExtensionAttributes($payment);
            $extensionAttributes->setVaultPaymentToken($paymentToken);
        }
    }

    /**
     * Get vault payment token entity
     *
     * @param Transaction $transaction
     * @return PaymentTokenInterface|null
     * @throws InputException
     * @throws NoSuchEntityException
     * @throws Exception
     */
    protected function getVaultPaymentToken(Transaction $transaction): ?PaymentTokenInterface
    {
        // Check token existing in gateway response
        if (empty($transaction->venmoAccountDetails->token)) {
            return null;
        }

        $paymentToken = $this->paymentTokenFactory->create(PaymentTokenFactoryInterface::TOKEN_TYPE_ACCOUNT);
        $paymentToken->setGatewayToken($transaction->venmoAccountDetails->token);
        $paymentToken->setExpiresAt($this->getExpirationDate());

        $paymentToken->setTokenDetails($this->convertDetailsToJSON([
            'username' => $transaction->venmoAccountDetails->username,
        ]));

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
        /**
         * Vaulted Venmo tokens don't expire and need to have an expiration date in the future to show in the checkout
         * lets just say it will expire after 10 years from when it gets stored
         */
        $expDate = $this->dateTimeFactory->create('now', new DateTimeZone('UTC'));
        $expDate->add(new DateInterval('P10Y'));
        return $expDate->format('Y-m-d 00:00:00');
    }
}
