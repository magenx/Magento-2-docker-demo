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

interface PaymentConfigSmartButtonsMessageStylesInterface
{
    public const LAYOUT = 'layout';

    public const LOGO = 'logo';

    /**
     * Get canDisplayMessage
     *
     * @return string
     */

    public function getLayout();
    /**
     * Set canDisplayMessage
     *
     * @param string $layout
     * @return $this
     */
    public function setLayout($layout);
    /**
     * Get messageStyles
     *
     * @return \Magento\PaymentServicesPaypal\Api\Data\PaymentConfigSmartButtonsLogoInterface
     */
    public function getLogo();

    /**
     * Set messageStyles
     *
     * @param \Magento\PaymentServicesPaypal\Api\Data\PaymentConfigSmartButtonsLogoInterface $logo
     * @return $this
     */
    public function setLogo($logo);
}
