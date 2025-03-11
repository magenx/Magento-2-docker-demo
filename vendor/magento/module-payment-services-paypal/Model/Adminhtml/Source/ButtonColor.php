<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Model\Adminhtml\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ButtonColor implements OptionSourceInterface
{
    /**
     * @return array
     */
    public function toOptionArray() : array
    {
        return [
            [
                'value' => 'default',
                'label' => __('Default'),
            ],
            [
                'value' => 'black',
                'label' => __('Black'),
            ],
            [
                'value' => 'white',
                'label' => __('White'),
            ],
        ];
    }
}
