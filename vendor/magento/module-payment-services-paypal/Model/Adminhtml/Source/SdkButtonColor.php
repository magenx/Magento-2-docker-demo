<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Model\Adminhtml\Source;

use Magento\Framework\Data\OptionSourceInterface;

class SdkButtonColor implements OptionSourceInterface
{
    /**
     * Possible Button Colors.
     */
    private const BLUE_COLOR = 'blue';

    private const GOLD_COLOR = 'gold';

    private const SILVER_COLOR = 'silver';

    private const WHITE_COLOR = 'white';

    private const BLACK_COLOR = 'black';

    // phpcs:disable
    /**
     * @return array
     */
    public function toOptionArray() : array
    {
        return [
            [
                'value' => self::BLUE_COLOR,
                'label' => __('blue'),
            ],
            [
                'value' => self::GOLD_COLOR,
                'label' => __('gold'),
            ],
            [
                'value' => self::SILVER_COLOR,
                'label' => __('silver'),
            ],
            [
                'value' => self::WHITE_COLOR,
                'label' => __('white'),
            ],
            [
                'value' => self::BLACK_COLOR,
                'label' => __('black'),
            ]
        ];
    }
}
