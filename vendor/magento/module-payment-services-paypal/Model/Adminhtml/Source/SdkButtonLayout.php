<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;

class SdkButtonLayout implements ArrayInterface
{
    /**
     * Possible Button Layouts.
     */
    private const VERTICAL_LAYOUT = 'vertical';

    private const HORIZONTAL_LAYOUT = 'horizontal';

    // phpcs:disable
    /**
     * @return array
     */
    public function toOptionArray() : array
    {
        return [
            [
                'value' => self::VERTICAL_LAYOUT,
                'label' => __('vertical'),
            ],
            [
                'value' => self::HORIZONTAL_LAYOUT,
                'label' => __('horizontal'),
            ],
        ];
    }
}
