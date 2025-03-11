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

class MerchantIDComment implements CommentInterface
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
        $paymentsUrl = $this->urlInterface->getUrl('paymentservicesdashboard/dashboard/index');
        $servicesConnectorUrl = $this->urlInterface->getUrl('services_id');

        return 'This value will be automatically populated'
            . ' after setting up the <a href="' . $servicesConnectorUrl . '">Commerce Services Connector</a>
            and visiting the <a href="' . $paymentsUrl . '">Payment Services dashboard</a> for the first time. '
            . 'This value associates your SaaS ID to Payment Services.';
    }
}
