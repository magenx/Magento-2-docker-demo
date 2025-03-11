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

interface VaultPaymentSourceDetailsInterface
{
    public const CARD = 'card';

    /**
     * Get card details
     *
     * @return \Magento\PaymentServicesPaypal\Api\Data\VaultCardDetailsInterface
     */
    public function getCard();

    /**
     * Set card details
     *
     * @param \Magento\PaymentServicesPaypal\Api\Data\VaultCardDetailsInterface $card
     * @return $this
     */
    public function setCard($card);
}
