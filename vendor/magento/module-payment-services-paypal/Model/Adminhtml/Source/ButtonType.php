<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Model\Adminhtml\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ButtonType implements OptionSourceInterface
{
    /**
     * @return array
     */
    public function toOptionArray() : array
    {
        return [
            [
                'value' => 'buy',
                'label' => __('Buy'),
            ],
            [
                'value' => 'checkout',
                'label' => __('Checkout'),
            ],
            [
                'value' => 'order',
                'label' => __('Order'),
            ],
            [
                'value' => 'pay',
                'label' => __('Pay'),
            ],
            [
                'value' => 'plain',
                'label' => __('Plain'),
            ],
        ];
    }
}
