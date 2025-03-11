<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Block\Adminhtml\Form;

use Magento\Framework\View\Element\Template;

class AdminVault extends Template
{
    /**
     * Map the credit card brand received from PayPal to the Commerce standard
     *
     * @param string $paypalCardBrand
     * @return string
     */
    public function mapCardBrand(string $paypalCardBrand): string
    {
        $brandMapping = [
            'AMEX' => 'AE',
            'DINERS' => 'DN',
            'DISCOVER' => 'DI',
            'ELO' => 'ELO',
            'JCB' => 'JCB',
            'MASTER_CARD' => 'MC',
            'MASTERCARD' => 'MC',
            'MAESTRO' => 'MI',
            'HIPER' => 'HC',
            'VISA' => 'VI'
        ];
        if (isset($brandMapping[$paypalCardBrand])) {
            return $brandMapping[$paypalCardBrand];
        }

        return '';
    }
}
