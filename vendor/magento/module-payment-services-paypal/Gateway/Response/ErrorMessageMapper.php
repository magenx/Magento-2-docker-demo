<?php
/************************************************************************
 *
 * Copyright 2024 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Gateway\Response;

use Magento\Payment\Gateway\ErrorMapper\ErrorMessageMapperInterface;

class ErrorMessageMapper implements ErrorMessageMapperInterface
{

    private const DENIED_RESPONSE = "PAYMENT_DENIED";

    private const DECLINED_RESPONSE = "Payment was declined.";

    private const CAPTURE_ERRORS = [
        'INVALID_CURRENCY_CODE' => 'Currency code should be a three-character currency code.',
        // phpcs:disable Magento2.Files.LineLength, Generic.Files.LineLength
        'CANNOT_BE_ZERO_OR_NEGATIVE' => 'Must be greater than zero. If the currency supports decimals, only two decimal place precision is supported.',
        'DECIMAL_PRECISION' => 'The value of the field should not be more than two decimal places.',
        'DECIMALS_NOT_SUPPORTED' => 'Currency does not support decimals.',
        'TRANSACTION_REFUSED' => 'PayPal\'s internal controls prevent authorization from being captured.',
        'AUTHORIZATION_VOIDED' => 'A voided authorization cannot be captured or reauthorized.',
        // phpcs:disable Magento2.Files.LineLength, Generic.Files.LineLength
        'MAX_CAPTURE_COUNT_EXCEEDED' => 'Maximum number of allowable captures has been reached. No additional captures are possible for this authorization. Please contact customer service or your account manager to change the number of captures that be made for a given authorization.',
        // phpcs:disable Magento2.Files.LineLength, Generic.Files.LineLength
        'DUPLICATE_INVOICE_ID' => 'Requested invoice number has been previously captured. Possible duplicate transaction.',
        'AUTH_CAPTURE_CURRENCY_MISMATCH' => 'Currency of capture must be the same as currency of authorization.',
        'AUTHORIZATION_ALREADY_CAPTURED' => 'Authorization has already been captured.',
        'PAYER_CANNOT_PAY' => 'Payer cannot pay for this transaction.',
        'AUTHORIZATION_EXPIRED' => 'An expired authorization cannot be captured.',
        'MAX_CAPTURE_AMOUNT_EXCEEDED' => 'Capture amount exceeds allowable limit.',
        'PAYEE_ACCOUNT_LOCKED_OR_CLOSED' => 'Transaction could not complete because payee account is locked or closed.',
        'PAYER_ACCOUNT_LOCKED_OR_CLOSED' => 'The payer account cannot be used for this transaction.'
    ];

    /**
     * @inheritdoc
     */
    public function getMessage(string $code)
    {
        if ($code === self::DENIED_RESPONSE || $code === self::DECLINED_RESPONSE) {
            return __(
                'Your payment was not successful. '
                . 'Ensure you have entered your details correctly and try again, '
                . 'or try a different payment method. If you have continued problems, '
                . 'contact the issuing bank for your payment method.'
            );
        }

        return __(self::CAPTURE_ERRORS[$code]
            ?? 'Error happened when processing the request. Please try again later.');
    }

}
