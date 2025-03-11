<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace PayPal\Braintree\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Color implements OptionSourceInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'gold', 'label' => __('Gold')],
            ['value' => 'blue', 'label' => __('Blue')],
            ['value' => 'silver', 'label' => __('Silver')],
            ['value' => 'white', 'label' => __('White')],
            ['value' => 'black', 'label' => __('Black')]
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'gold' => __('Gold'),
            'blue' => __('Blue'),
            'silver' => __('Silver'),
            'white' => __('White'),
            'black' => __('Black')
        ];
    }

    /**
     * Values in the format needed for the PayPal JS SDK
     *
     * @return array
     */
    public function toRawValues(): array
    {
        return [
            'gold' => 'gold',
            'blue' => 'blue',
            'silver' => 'silver',
            'white' => 'white',
            'black' => 'black',
        ];
    }
}
