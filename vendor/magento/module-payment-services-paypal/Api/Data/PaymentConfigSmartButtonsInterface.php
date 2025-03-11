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

use Magento\PaymentServicesPaypal\Model\Data\PaymentConfigSmartButtonsMessageStyles;
use Magento\PaymentServicesPaypal\Model\Data\PaymentConfigButtonStyles;

interface PaymentConfigSmartButtonsInterface extends PaymentConfigItemInterface
{
    public const DISPLAY_MESSAGE = 'display_message';
    public const MESSAGE_STYLES = 'message_styles';
    public const BUTTON_STYLES = 'button_styles';
    public const DISPLAY_VENMO = 'display_venmo';

    /**
     * Get canDisplayMessage
     *
     * @return bool
     */
    public function hasDisplayMessage();

    /**
     * Set canDisplayMessage
     *
     * @param bool $canDisplayMessage
     * @return $this
     */
    public function setHasDisplayMessage($canDisplayMessage);

    /**
     * Get Venmo
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function hasDisplayVenmo();

    /**
     * Set Venmo
     *
     * @param bool $canDisplayVenmo
     * @return $this
     */
    public function setHasDisplayVenmo($canDisplayVenmo);

    /**
     * Get messageStyles
     *
     * @return \Magento\PaymentServicesPaypal\Api\Data\PaymentConfigSmartButtonsMessageStylesInterface
     */
    public function getMessageStyles();

    /**
     * Set messageStyles
     *
     * @param \Magento\PaymentServicesPaypal\Api\Data\paymentConfigSmartButtonsMessageStylesFactory $messageStyles
     * @return $this
     */
    public function setMessageStyles($messageStyles);

    /**
     * Get buttonStyles
     *
     * @return \Magento\PaymentServicesPaypal\Api\Data\PaymentConfigButtonStylesInterface
     */
    public function getButtonStyles();

    /**
     * Set buttonStyles
     *
     * @param array $buttonStyles
     * @return $this
     */
    public function setButtonStyles($buttonStyles);
}
