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

namespace Magento\PaymentServicesPaypalGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\PaymentServicesPaypal\Api\PaymentConfigManagementInterface;
use Magento\PaymentServicesPaypal\Model\ApplePayConfigProvider;
use Magento\PaymentServicesPaypal\Model\GooglePayConfigProvider;
use Magento\PaymentServicesPaypal\Model\HostedFieldsConfigProvider;
use Magento\PaymentServicesPaypal\Model\SmartButtonsConfigProvider;

class PaymentConfig implements ResolverInterface
{
    private const PAYMENT_METHODS = [
        'apple_pay' => ApplePayConfigProvider::CODE,
        'google_pay' => GooglePayConfigProvider::CODE,
        'hosted_fields' => HostedFieldsConfigProvider::CODE,
        'smart_buttons' =>  SmartButtonsConfigProvider::CODE
    ];

    /**
     * @var PaymentConfigManagementInterface
     */
    private PaymentConfigManagementInterface $paymentConfigManagement;

    /**
     * @param PaymentConfigManagementInterface $paymentConfigManagement
     */
    public function __construct(
        PaymentConfigManagementInterface $paymentConfigManagement
    ) {
        $this->paymentConfigManagement = $paymentConfigManagement;
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
        $location = $args['location'];
        $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();
        $result = [];

        foreach ($info->getFieldSelection() as $paymentMethod => $requested) {
            if ($requested && isset(self::PAYMENT_METHODS[$paymentMethod])) {
                $result[$paymentMethod] = $this->paymentConfigManagement->getConfigItem(
                    $location,
                    self::PAYMENT_METHODS[$paymentMethod],
                    $storeId
                );
            }
        }

        return $result;
    }
}
