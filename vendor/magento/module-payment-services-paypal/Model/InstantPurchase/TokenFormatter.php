<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Model\InstantPurchase;

use Magento\InstantPurchase\PaymentMethodIntegration\PaymentTokenFormatterInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;

/**
 * Stored credit card formatter.
 */
class TokenFormatter implements PaymentTokenFormatterInterface
{
    /**
     * Most used credit card types
     * @var array
     */
    public static $baseCardTypes = [
        'AMEX' => 'American Express',
        'DINERS' => 'Diners Club',
        'DISCOVER' => 'Discover',
        'ELO' => 'Elo',
        'JCB' => 'JCB',
        'MASTER_CARD' => 'MasterCard',
        'MAESTRO' => 'Maestro',
        'HIPER' => 'Hipercard',
        'VISA' => 'Visa'
    ];

    /**
     * @inheritdoc
     */
    public function formatPaymentToken(PaymentTokenInterface $paymentToken): string
    {
        $details = json_decode($paymentToken->getTokenDetails() ?: '{}', true);
        if (!isset($details['brand'], $details['maskedCC'], $details['expirationDate'])) {
            throw new \InvalidArgumentException('Invalid PayPal Payflow Pro credit card token details.');
        }

        if (isset(self::$baseCardTypes[$details['brand']])) {
            $brand = self::$baseCardTypes[$details['brand']];
        } else {
            $brand = $details['brand'];
        }

        $formatted = sprintf(
            '%s: %s, %s: %s (%s: %s)',
            __('Credit Card'),
            $brand,
            __('ending'),
            $details['maskedCC'],
            __('expires'),
            $details['expirationDate']
        );

        return $formatted;
    }
}
