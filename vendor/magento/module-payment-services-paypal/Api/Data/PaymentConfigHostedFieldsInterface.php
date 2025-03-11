<?php
/*************************************************************************
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2023 Adobe
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

interface PaymentConfigHostedFieldsInterface extends PaymentConfigItemInterface
{
    public const PAYMENT_SOURCE = 'payment_source';
    public const THREE_DS = 'three_ds';
    public const CC_VAULT_CODE = 'cc_vault_code';
    public const VAULT_ENABLED = 'is_vault_enabled';
    public const REQUIRES_CARD_DETAILS = 'requires_card_details';

    /**
     * Get Payment Source
     *
     * @return string
     */
    public function getPaymentSource();

    /**
     * Set Payment Source
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

    /**
     * Get hasIsVaultEnabled
     *
     * @return bool
     */
    public function hasIsVaultEnabled();

    /**
     * Set hasIsVaultEnabled
     *
     * @param bool $hasIsVaultEnabled
     * @return void
     */
    public function setHasIsVaultEnabled($hasIsVaultEnabled);

    /**
     * Get ccVaultCode
     *
     * @return string
     */
    public function getCcVaultCode();

    /**
     * Set ccVaultCode
     *
     * @param string $ccVaultCode
     * @return void
     */
    public function setCcVaultCode($ccVaultCode);

    /**
     * Get requiresCardDetails
     *
     * @return bool
     */
    public function isRequiresCardDetails();

    /**
     * Set requiresCardDetails
     *
     * @param bool $requiresCardDetails
     * @return void
     */
    public function setRequiresCardDetails($requiresCardDetails);
}
