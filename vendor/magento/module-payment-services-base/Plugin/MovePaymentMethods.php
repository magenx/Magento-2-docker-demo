<?php

/************************************************************************
 *
 * Copyright 2024 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\PaymentServicesBase\Plugin;

use Magento\Paypal\Model\Config\Structure\PaymentSectionModifier;

class MovePaymentMethods
{
    private const RECOMMENDED_SOLUTIONS = 'recommended_solutions';
    private const CHILDREN = 'children';
    private const RECOMMENDED_SOLUTIONS_ALLOWED_LIST = ['magento_payments_legacy', 'magento_payments'];
    private const OTHER_PAYPAL_PAYMENT_SOLUTIONS = 'other_paypal_payment_solutions';

    /**
     * Move payment methods not allowed to other_paypal_payment_solutions
     *
     * @param PaymentSectionModifier $subject
     * @param array $result
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */

    public function afterModify(PaymentSectionModifier $subject, $result)
    {
        foreach (array_keys($result[self::RECOMMENDED_SOLUTIONS][self::CHILDREN]) as $key) {
            if (!in_array($key, self::RECOMMENDED_SOLUTIONS_ALLOWED_LIST, true)) {
                $result = $this->moveMethodToOtherPaymentSolutions($result, $key);
            }
        }

        return $result;
    }

    /**
     * Move other payment method by key to other_paypal_payment_solutions
     *
     * @param array $result
     * @param int|string $key
     * @return array
     */
    public function moveMethodToOtherPaymentSolutions(array $result, int|string $key): array
    {
        if (!isset($result[self::OTHER_PAYPAL_PAYMENT_SOLUTIONS][self::CHILDREN]) ||
            !is_array($result[self::OTHER_PAYPAL_PAYMENT_SOLUTIONS][self::CHILDREN])) {
            $result[self::OTHER_PAYPAL_PAYMENT_SOLUTIONS][self::CHILDREN] = [];
        }

        if (isset($result[self::RECOMMENDED_SOLUTIONS][self::CHILDREN][$key])) {
            $result[self::OTHER_PAYPAL_PAYMENT_SOLUTIONS][self::CHILDREN] =
                array_merge(
                    [
                        $result[self::RECOMMENDED_SOLUTIONS][self::CHILDREN][$key]
                    ],
                    $result[self::OTHER_PAYPAL_PAYMENT_SOLUTIONS][self::CHILDREN]
                );

            unset($result[self::RECOMMENDED_SOLUTIONS][self::CHILDREN][$key]);
        }
        return $result;
    }
}
