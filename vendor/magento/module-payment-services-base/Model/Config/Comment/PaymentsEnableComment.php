<?php
/**
 * ADOBE CONFIDENTIAL
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
 */
declare(strict_types=1);

namespace Magento\PaymentServicesBase\Model\Config\Comment;

use Magento\Config\Model\Config\CommentInterface;
use Magento\Framework\UrlInterface;

class PaymentsEnableComment implements CommentInterface
{
    /**
     * @var UrlInterface
     */
    protected $urlInterface;

    /**
     * @param UrlInterface $urlInterface
     */
    public function __construct(UrlInterface $urlInterface)
    {
        $this->urlInterface = $urlInterface;
    }

    /**
     * Retrieve element comment by element value
     *
     * @param string $elementValue
     * @return string
     */
    public function getCommentText($elementValue)
    {
        $url = $this->urlInterface->getUrl('paymentservicesdashboard/dashboard/index');
        return 'Do not change this value to ‘Yes’ until PayPal onboarding is '
            . 'done from the <a href="' . $url . '">Payment Services dashboard</a>.';
    }
}
