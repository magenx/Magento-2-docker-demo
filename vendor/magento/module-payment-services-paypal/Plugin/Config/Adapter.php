<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Plugin\Config;

use Magento\PaymentServicesPaypal\Model\ApplePayConfigProvider;
use Magento\PaymentServicesPaypal\Model\GooglePayConfigProvider;
use Magento\PaymentServicesPaypal\Model\HostedFieldsConfigProvider;
use Magento\PaymentServicesPaypal\Model\SmartButtonsConfigProvider;
use Magento\Payment\Model\Method\Adapter as PaymentAdapter;
use Magento\PaymentServicesPaypal\Model\Config;

class Adapter
{
    private const SUPPORTED_PAYMENT_METHODS = [
        HostedFieldsConfigProvider::CODE,
        SmartButtonsConfigProvider::CODE,
        ApplePayConfigProvider::CODE,
        GooglePayConfigProvider::CODE
    ];

    /**
     * @var Config
     */
    private $config;

    /**
     * @param Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * Return isActive for payment methods based on whether payments is enabled
     *
     * @param PaymentAdapter $subject
     * @param bool $result
     * @param string|null $storeId
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterIsActive(PaymentAdapter $subject, $result, $storeId = null): bool
    {
        if ($this->isSupportedPaymentMethod($subject)) {
            return $this->config->isEnabled($storeId);
        } elseif ($this->isVaultedCardMethod($subject)) {
            return $this->config->isEnabled($storeId) && $this->config->isVaultEnabled($storeId);
        } else {
            return $result;
        }
    }

    /**
     * Return canCapture for payment methods based on whether order is in payment review state
     *
     * @param PaymentAdapter $subject
     * @param bool $result
     * @return bool
     */
    public function afterCanCapture(PaymentAdapter $subject, bool $result): bool
    {
        $payment = $subject->getInfoInstance();
        if ($payment instanceof \Magento\Sales\Model\Order\Payment) {
            if ($this->isInPaymentReviewState($payment)
                && ($this->isSupportedPaymentMethod($subject) || $this->isVaultedCardMethod($subject))
            ) {
                return false;
            }
        }

        return $result;
    }

    /**
     * Make canReviewPayment return true for Payment Services payment methods
     * when async payment status updates are enabled
     *
     * @param PaymentAdapter $subject
     * @param bool $result
     * @return bool
     */
    public function afterCanReviewPayment(PaymentAdapter $subject, bool $result): bool
    {
        $payment = $subject->getInfoInstance();
        if ($payment instanceof \Magento\Sales\Model\Order\Payment) {
            if ($this->isSupportedPaymentMethod($subject)) {
                return $this->config->isAsyncPaymentStatusUpdatesEnabled();
            }
        }

        return $result;
    }

    /**
     * Check if order is in payment review state
     *
     * @param \Magento\Sales\Model\Order\Payment $payment
     * @return bool
     */
    private function isInPaymentReviewState(\Magento\Sales\Model\Order\Payment $payment): bool
    {
        return $payment->getOrder()->getState() === \Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW;
    }

    /**
     * Check if payment method belongs to Payment Services
     *
     * @param PaymentAdapter $subject
     * @return bool
     */
    private function isSupportedPaymentMethod(PaymentAdapter $subject): bool
    {
        return in_array($subject->getCode(), self::SUPPORTED_PAYMENT_METHODS);
    }

    /**
     * Check if payment method is vaulted card
     *
     * @param PaymentAdapter $subject
     * @return bool
     */
    private function isVaultedCardMethod(PaymentAdapter $subject): bool
    {
        return $subject->getCode() === HostedFieldsConfigProvider::CC_VAULT_CODE;
    }
}
