<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Gateway\Response\GooglePay;

use Braintree\Transaction;
use DateInterval;
use DateTime;
use DateTimeZone;
use Exception;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use PayPal\Braintree\Gateway\Response\Handler;

class VaultDetailsHandler extends Handler implements HandlerInterface
{
    /**
     * Handles response
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     * @throws InputException
     * @throws NoSuchEntityException
     * @throws Exception
     */
    public function handle(
        array $handlingSubject,
        array $response
    ): void {
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
     * Get vault payment token entity.
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
        $token = $transaction->googlePayCardDetails->token;

        if (empty($token) || empty($transaction->googlePayCardDetails->expirationYear)) {
            return null;
        }

        $paymentToken = $this->paymentTokenFactory->create(PaymentTokenFactoryInterface::TOKEN_TYPE_CREDIT_CARD);
        $paymentToken->setGatewayToken($token);
        $paymentToken->setExpiresAt($this->getExpirationDate($transaction));

        $paymentToken->setTokenDetails($this->convertDetailsToJSON([
            // Card Type has a prefix, eg `Google Pay - MasterCard`, so needs removing.
            'type' => $this->getCreditCardType(
                str_replace('Google Pay - ', '', $transaction->googlePayCardDetails->sourceCardType)
            ),
            'maskedCC' => $transaction->googlePayCardDetails->sourceCardLast4,
            'expirationDate' => $transaction->googlePayCardDetails->expirationMonth . '/' .
                $transaction->googlePayCardDetails->expirationYear
        ]));

        return $paymentToken;
    }

    /**
     * Get expiration date
     *
     * @param Transaction $transaction
     * @return string
     * @throws Exception
     */
    private function getExpirationDate(Transaction $transaction): string
    {
        $expDate = $this->dateTimeFactory->create(
            $transaction->googlePayCardDetails->expirationYear
            . '-'
            . $transaction->googlePayCardDetails->expirationMonth
            . '-'
            . '01'
            . ' '
            . '00:00:00',
            new DateTimeZone('UTC')
        );
        $expDate->add(new DateInterval('P1M'));
        return $expDate->format('Y-m-d 00:00:00');
    }

    /**
     * Get type of credit card mapped from Braintree.
     *
     * We remove the `Google Pay - ` prefix and then pass the rest to the standard cc mapper the module provides.
     *
     * @param string $type
     * @return string
     * @throws InputException
     * @throws NoSuchEntityException
     */
    private function getCreditCardType(string $type): string
    {
        $replaced = str_replace(' ', '-', strtolower($type));
        $mapper = $this->config->getCcTypesMapper();

        return $mapper[$replaced];
    }
}
