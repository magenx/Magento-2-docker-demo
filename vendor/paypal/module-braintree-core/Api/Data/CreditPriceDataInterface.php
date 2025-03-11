<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Api\Data;

/**
 * Interface CreditPriceDataInterface
 **/
interface CreditPriceDataInterface
{
    public const ID = 'id';
    public const PRODUCT_ID = 'product_id';
    public const TERM = 'term';
    public const MONTHLY_PAYMENT = 'monthly_payment';
    public const INSTALMENT_RATE = 'instalment_rate';
    public const COST_OF_PURCHASE = 'cost_of_purchase';
    public const TOTAL_INC_INTEREST = 'total_inc_interest';

    /**
     * Get id
     *
     * @return int|null
     */
    public function getId();

    /**
     * Set id
     *
     * @param int $value
     * @return self
     */
    public function setId($value): CreditPriceDataInterface;

    /**
     * Get product id
     *
     * @return int
     */
    public function getProductId(): int;

    /**
     * Set product id
     *
     * @param int $value
     * @return self
     */
    public function setProductId($value): CreditPriceDataInterface;

    /**
     * Get term
     *
     * @return int
     */
    public function getTerm(): int;

    /**
     * Set term
     *
     * @param int $value
     * @return self
     */
    public function setTerm($value): CreditPriceDataInterface;

    /**
     * Get monthly payment amount
     *
     * @return float
     */
    public function getMonthlyPayment(): float;

    /**
     * Set monthly payment amount
     *
     * @param float $value
     * @return self
     */
    public function setMonthlyPayment($value): CreditPriceDataInterface;

    /**
     * Get installment rate
     *
     * @return float
     */
    public function getInstalmentRate(): float;

    /**
     * Set installment rate
     *
     * @param float $value
     * @return self
     */
    public function setInstalmentRate($value): CreditPriceDataInterface;

    /**
     * Get cost of purchase
     *
     * @return float
     */
    public function getCostOfPurchase(): float;

    /**
     * Set cost of purchase
     *
     * @param float $value
     * @return self
     */
    public function setCostOfPurchase($value): CreditPriceDataInterface;

    /**
     * Get total including interest
     *
     * @return float
     */
    public function getTotalIncInterest(): float;

    /**
     * Set total including interest
     *
     * @param float $value
     * @return self
     */
    public function setTotalIncInterest($value): CreditPriceDataInterface;
}
