<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Model\Adminhtml\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ThreeDS implements OptionSourceInterface
{
    public const WHEN_REQUIRED = 'SCA_WHEN_REQUIRED';

    public const ALWAYS = 'SCA_ALWAYS';

    /**
     * @inheritdoc
     */
    public function toOptionArray() : array
    {
        return [
            [
                'value' => 0,
                'label' => __('Off'),
            ],
            [
                'value' => self::WHEN_REQUIRED,
                'label' => __('When Required'),
            ],
            [
                'value' => self::ALWAYS,
                'label' => __('Always'),
            ]
        ];
    }
}
