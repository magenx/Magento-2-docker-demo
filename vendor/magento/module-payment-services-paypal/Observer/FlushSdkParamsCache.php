<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\App\CacheInterface;
use Magento\PaymentServicesPaypal\Model\SdkService;

class FlushSdkParamsCache implements ObserverInterface
{
    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @param CacheInterface $cache
     */
    public function __construct(
        CacheInterface $cache
    ) {
        $this->cache = $cache;
    }

    /**
     * Flush Payment Services cache upon settings' change
     *
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        $configData = $observer->getData('configData');
        $configSection = $configData['section'];
        if ($configSection === 'payment') {
            $this->cache->clean([SdkService::CACHE_TYPE_TAG]);
        }
    }
}
