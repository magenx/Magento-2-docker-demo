<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;

class SdkButtonShape implements ArrayInterface
{
    /**
     * Possible Button Shapes.
     */
    private const PILL_SHAPE = 'pill';

    private const RECT_SHAPE = 'rect';

    // phpcs:disable
    /**
     * @return array
     */
    public function toOptionArray() : array
    {
        return [
            [
                'value' => self::RECT_SHAPE,
                'label' => __('rectangular'),
            ],
            [
                'value' => self::PILL_SHAPE,
                'label' => __('pill'),
            ]
        ];
    }
}
