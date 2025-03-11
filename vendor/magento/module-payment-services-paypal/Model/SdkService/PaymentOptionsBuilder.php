<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Model\SdkService;

use Magento\Framework\DataObject;

class PaymentOptionsBuilder extends DataObject
{
    public const HOSTED_FIELDS = 'hosted_fields';
    public const CARD_FIELDS = 'card_fields';

    private const BUTTONS = 'buttons';
    private const ARE_BUTTONS_ENABLED = 'buttons_enabled';
    private const IS_PAYPAL_CREDIT_ENABLED = 'paypal_credit';
    private const IS_VENMO_ENABLED = 'venmo';
    private const IS_CREDIT_CARD_ENABLED = 'credit_card';
    private const CARD_FIELDS_TYPE = 'card_fields_type';
    private const IS_APPLE_PAY_ENABLED = 'applepay';
    private const IS_GOOGLE_PAY_ENABLED = 'googlepay';
    private const IS_PAYPAL_CARD_ENABLED = 'card';
    private const IS_PAYLATER_MESSAGE_ENABLED = 'paylater_message';

    /**
     * Set is smart buttons enabled.
     *
     * @param bool $areButtonsEnabled
     * @return $this
     */
    public function setAreButtonsEnabled(bool $areButtonsEnabled)
    {
        return $this->setData(self::ARE_BUTTONS_ENABLED, $areButtonsEnabled);
    }

    /**
     * Set is pay pal credit button enabled.
     *
     * @param bool $isPayPalCreditEnabled
     * @return $this
     */
    public function setIsPayPalCreditEnabled(bool $isPayPalCreditEnabled)
    {
        return $this->setData(self::IS_PAYPAL_CREDIT_ENABLED, $isPayPalCreditEnabled);
    }

    /**
     * Set is venmo button enabled.
     *
     * @param bool $isVenmoEnabled
     * @return $this
     */
    public function setIsVenmoEnabled(bool $isVenmoEnabled)
    {
        return $this->setData(self::IS_VENMO_ENABLED, $isVenmoEnabled);
    }

    /**
     * Set is credit card enabled.
     *
     * @param bool $isCreditCardEnabled
     * @return $this
     */
    public function setIsCreditCardEnabled(bool $isCreditCardEnabled)
    {
        return $this->setData(self::IS_CREDIT_CARD_ENABLED, $isCreditCardEnabled);
    }

    /**
     * Make sure we use hosted_fields.
     *
     * @return $this
     */
    public function useHostedFieldsForCreditCard()
    {
        return $this->setData(self::CARD_FIELDS_TYPE, self::HOSTED_FIELDS);
    }

    /**
     * Make sure we use card_fields.
     *
     * @return $this
     */
    public function useCardFieldsForCreditCard()
    {
        return $this->setData(self::CARD_FIELDS_TYPE, self::CARD_FIELDS);
    }

    /**
     * Set is Apple Pay enabled.
     *
     * @param bool $isApplePayEnabled
     * @return PaymentOptionsBuilder
     */
    public function setIsApplePayEnabled(bool $isApplePayEnabled)
    {
        return $this->setData(self::IS_APPLE_PAY_ENABLED, $isApplePayEnabled);
    }

    /**
     * Set is Google Pay enabled.
     *
     * @param bool $isGooglePayEnabled
     * @return PaymentOptionsBuilder
     */
    public function setIsGooglePayEnabled(bool $isGooglePayEnabled)
    {
        return $this->setData(self::IS_GOOGLE_PAY_ENABLED, $isGooglePayEnabled);
    }

    /**
     * Set is PayPal Card enabled.
     *
     * @param bool $isPayPalCardEnabled
     * @return PaymentOptionsBuilder
     */
    public function setIsPayPalCardEnabled(bool $isPayPalCardEnabled)
    {
        return $this->setData(self::IS_PAYPAL_CARD_ENABLED, $isPayPalCardEnabled);
    }

    /**
     * Set is pay later message enabled.
     *
     * @param bool $isPaylaterMessageEnabled
     * @return PaymentOptionsBuilder
     */
    public function setIsPaylaterMessageEnabled(bool $isPaylaterMessageEnabled)
    {
        return $this->setData(self::IS_PAYLATER_MESSAGE_ENABLED, $isPaylaterMessageEnabled);
    }

    /**
     * Build result.
     *
     * @return array
     */
    public function build()
    {
        $result = [
            self::IS_CREDIT_CARD_ENABLED =>
                $this->getData(self::IS_CREDIT_CARD_ENABLED)
                    ? [
                        "enabled" => $this->getData(self::IS_CREDIT_CARD_ENABLED),
                        "type" => $this->getData(self::CARD_FIELDS_TYPE) ?? self::HOSTED_FIELDS,
                    ] : null,
            self::IS_PAYLATER_MESSAGE_ENABLED => $this->getData(self::IS_PAYLATER_MESSAGE_ENABLED),
            self::IS_GOOGLE_PAY_ENABLED => $this->getData(self::IS_GOOGLE_PAY_ENABLED),
            self::IS_APPLE_PAY_ENABLED => $this->getData(self::IS_APPLE_PAY_ENABLED),
        ];
        if ($this->getData(self::ARE_BUTTONS_ENABLED)) {
            $result[self::BUTTONS] = [
                self::IS_PAYPAL_CARD_ENABLED => $this->getData(self::IS_PAYPAL_CARD_ENABLED),
                self::IS_PAYPAL_CREDIT_ENABLED => $this->getData(self::IS_PAYPAL_CREDIT_ENABLED),
                self::IS_VENMO_ENABLED => $this->getData(self::IS_VENMO_ENABLED),
            ];
        }
        return $result;
    }
}
