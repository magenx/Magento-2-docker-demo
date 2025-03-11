<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Model\Payment\Checks;

use Magento\Framework\Authorization;
use Magento\Payment\Model\Checks\SpecificationInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Model\Quote;

/**
 * Check if user has permissions.
 */
class CanUseForUser implements SpecificationInterface
{
    /**
     * @var Authorization
     */
    private Authorization $authorization;

    /**
     * @var array
     */
    private array $methods;

    /**
     * @param Authorization $authorization
     * @param array $methods
     */
    public function __construct(
        Authorization $authorization,
        array $methods = []
    ) {
        $this->authorization = $authorization;
        $this->methods = $methods;
    }

    /**
     * @inheritdoc
     */
    public function isApplicable(MethodInterface $paymentMethod, Quote $quote): bool
    {
        if (!in_array($paymentMethod->getCode(), $this->methods)) {
            return true;
        }
        return $this->authorization->isAllowed('Magento_PaymentServicesPaypal::ordercreate');
    }
}
