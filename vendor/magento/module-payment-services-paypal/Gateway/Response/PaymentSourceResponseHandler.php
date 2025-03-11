<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Gateway\Response;

use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Psr\Log\LoggerInterface;

class PaymentSourceResponseHandler implements HandlerInterface
{
    public const AUTH_TXN = 'authorization';
    public const AUTH_CAPTURE_TXN = 'auth_capture';

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    private EncryptorInterface $encryptor;

    /**
     * @param LoggerInterface $logger
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     */
    public function __construct(
        LoggerInterface $logger,
        EncryptorInterface $encryptor
    ) {
        $this->logger = $logger;
        $this->encryptor = $encryptor;
    }

    /**
     * Handles Authorization Responses
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        if (!isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = $handlingSubject['payment'];

        $payment = $paymentDO->getPayment();

        if ($response['mp-transaction']['type'] === self::AUTH_TXN
            || $response['mp-transaction']['type'] === self::AUTH_CAPTURE_TXN) {

            if (isset($response['mp-transaction']['processor_response'])) {
                $processorResponse = $response['mp-transaction']['processor_response'];
                $payment->setCcAvsStatus($processorResponse['avs_code'] ?? null);
                $payment->setCcCidStatus($processorResponse['cvv_code'] ?? null);
            } else {
                $this->logger->warning('No processor response found in payment source response', $response);
            }

            $card = null;

            if (isset($response['mp-transaction']['payment_source_details']['card'])) {
                $card = $response['mp-transaction']['payment_source_details']['card'];
            } elseif (isset($response['mp-transaction']['payment_source_details']['applePay']['card'])) {
                $card = $response['mp-transaction']['payment_source_details']['applePay']['card'];
            }

            if ($card) {
                $name = $card['name'] ?? null;
                $bin = isset($card['bin_details']['bin']) ?
                    $this->encryptor->encrypt($card['bin_details']['bin']) :
                    null;
                $lastDigits = $card['last_digits'] ?? null;
                $ccType = $card['brand'] ?? null;
                $expMonth = $card['card_expiry_month'] ?? null;
                $expYear = $card['card_expiry_year'] ?? null;

                $payment->setCcOwner($name);
                $payment->setCcNumberEnc($bin);
                $payment->setCcLast4($lastDigits);
                $payment->setCcType($ccType);
                $payment->setCcExpMonth($expMonth);
                $payment->setCcExpYear($expYear);

                $payment->setAdditionalInformation(OrderPaymentInterface::CC_OWNER, $name);
                $payment->setAdditionalInformation(OrderPaymentInterface::CC_LAST_4, $lastDigits);
                $payment->setAdditionalInformation(OrderPaymentInterface::CC_TYPE, $ccType);
                $payment->setAdditionalInformation(OrderPaymentInterface::CC_EXP_MONTH, $expMonth);
                $payment->setAdditionalInformation(OrderPaymentInterface::CC_EXP_YEAR, $expYear);
            } else {
                $this->logger->warning('No card details found in payment source response', $response);
            }
        }
    }
}
