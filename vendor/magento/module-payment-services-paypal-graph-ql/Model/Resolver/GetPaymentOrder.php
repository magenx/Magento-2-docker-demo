<?php

/**
 * ADOBE CONFIDENTIAL
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
 */

declare(strict_types=1);

namespace Magento\PaymentServicesPaypalGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\PaymentServicesPaypal\Api\PaymentOrderManagementInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;

/**
 * Create Payment Order resolver, used for GraphQL mutation processing.
 */
class GetPaymentOrder implements ResolverInterface
{
    /**
     * @var PaymentOrderManagementInterface
     */
    private PaymentOrderManagementInterface $paymentOrderManagement;

    /**
     * @var MaskedQuoteIdToQuoteIdInterface
     */
    private MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId;

    /**
     * @param PaymentOrderManagementInterface $paymentOrderManagement
     * @param MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId
     */
    public function __construct(
        PaymentOrderManagementInterface $paymentOrderManagement,
        MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId
    ) {
        $this->paymentOrderManagement = $paymentOrderManagement;
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
    }
    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null
    ) {
        $customerId = $context->getUserId();
        $cartId = $this->maskedQuoteIdToQuoteId->execute($args['cartId']);
        $id = $args['id'];

        return $this->paymentOrderManagement->get(
            $cartId,
            $id,
            $customerId
        );
    }
}
