<?php
/*************************************************************************
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2023 Adobe
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
 **************************************************************************/
declare(strict_types=1);
namespace Magento\PaymentServicesPaypal\Api\Data;

interface PaymentConfigSdkParamsInterface
{
    public const PARAM_NAME = 'name';
    public const PARAM_VALUE = 'value';

    /**
     * Get code
     *
     * @return string
     */
    public function getName();

    /**
     * Set code
     *
     * @param string $name
     * @return \Magento\Framework\DataObject
     */
    public function setName($name);

    /**
     * Get config
     *
     * @return string
     */
    public function getValue();

    /**
     * Set config
     *
     * @param string $value
     * @return \Magento\Framework\DataObject
     */
    public function setValue($value);
}
