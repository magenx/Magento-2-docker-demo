<?php
/**
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
 */
declare(strict_types=1);

namespace Magento\SaaSCommon\Cron;

use Magento\SaaSCommon\Model\Exception\UnableSendData;
use Magento\ServicesConnector\Exception\PrivateKeySignException;

/**
 * Class to execute submitting data feed
 */
interface SubmitFeedInterface
{
    /**
     * Submit feed data
     *
     * @param array $data
     * @return bool
     * @throws UnableSendData|PrivateKeySignException
     *
     * TODO: Remove when all feeds are migrated to immediate export
     */
    public function submitFeed(array $data) : bool;

    /**
     *  Execute feed data submission
     *
     * @throws \Zend_Db_Statement_Exception
     */
    public function execute();
}
