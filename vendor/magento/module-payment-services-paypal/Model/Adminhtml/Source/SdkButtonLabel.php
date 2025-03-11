<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Model\Adminhtml\Source;

use Magento\Framework\Data\OptionSourceInterface;

class SdkButtonLabel implements OptionSourceInterface
{
    /**
     * Possible Button Labels.
     */
    private const PAYPAL_LABEL = 'paypal';

    private const CHECKOUT_LABEL = 'checkout';

    private const BUYNOW_LABEL = 'buynow';

    private const PAY_LABEL = 'pay';

    private const INSTALLMENT_LABEL = 'installment';

    // phpcs:disable
    /**
     * @inheritdoc
     */
    public function toOptionArray() : array
    {
        return [
            [
                'value' => self::PAYPAL_LABEL,
                'label' => __(self::PAYPAL_LABEL),
            ],
            [
                'value' => self::CHECKOUT_LABEL,
                'label' => __(self::CHECKOUT_LABEL),
            ],
            [
                'value' => self::BUYNOW_LABEL,
                'label' => __(self::BUYNOW_LABEL),
            ],
            [
                'value' => self::PAY_LABEL,
                'label' => __(self::PAY_LABEL),
            ],
            [
                'value' => self::INSTALLMENT_LABEL,
                'label' => __(self::INSTALLMENT_LABEL),
            ]
        ];
    }
}
