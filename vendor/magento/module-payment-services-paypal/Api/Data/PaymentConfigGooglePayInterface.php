<?php
/*************************************************************************
 * ADOBE CONFIDENTIAL
 * ___________________
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
 **************************************************************************/
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Api\Data;

interface PaymentConfigGooglePayInterface extends PaymentConfigItemInterface
{
    public const BUTTON_STYLES = 'button_styles';

    public const PAYMENT_SOURCE = 'payment_source';
    public const THREE_DS = 'three_ds';

    /**
     * Get buttonStyles
     *
     * @return \Magento\PaymentServicesPaypal\Api\Data\PaymentConfigGooglePayButtonStylesInterface
     */
    public function getButtonStyles();

    /**
     * Set buttonStyles
     *
     * @param array $buttonStyles
     * @return $this
     */
    public function setButtonStyles($buttonStyles);

    /**
     * Get paymentSource
     *
     * @return string
     */
    public function getPaymentSource();

    /**
     * Set paymentSource
     *
     * @param string $paymentSource
     * @return void
     */
    public function setPaymentSource($paymentSource);

    /**
     * Get threeDS
     *
     * @return string
     */
    public function getThreeDS();

    /**
     * Set threeDS
     *
     * @param string $threeDS
     * @return void
     */
    public function setThreeDS($threeDS);
}
