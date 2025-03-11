<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2025 Adobe
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

namespace Magento\PaymentServicesPaypal\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event;
use Magento\PaymentServicesPaypal\Block\Message;
use Magento\Checkout\Block\QuoteShortcutButtons;
use Magento\PaymentServicesBase\Model\Config;

class AddCheckoutComponents implements ObserverInterface
{
    /**
     * @var Config $paymentConfig
     */
    private Config $paymentConfig;

    /**
     * @var array
     */
    private $blocks;

    /**
     * @param Config $paymentConfig
     * @param array $blocks
     */
    public function __construct(
        Config $paymentConfig,
        array $blocks = []
    ) {
        $this->paymentConfig = $paymentConfig;
        $this->blocks = $blocks;
    }

    /**
     * @ingeritdoc
     */
    public function execute(EventObserver $observer)
    {
        if (!$this->paymentConfig->isConfigured()) {
            return;
        }

        /** @var QuoteShortcutButtons $shortcutButtons */
        $shortcutButtons = $observer->getEvent()->getContainer();
        $smartButtons = $shortcutButtons->getLayout()->createBlock(
            $this->blocks[$this->getPageType($observer->getEvent())],
            '',
            [
                'pageType' => $this->getPageType($observer->getEvent()),
            ]
        );
        $shortcutButtons->addShortcut($smartButtons);
        $message = $shortcutButtons->getLayout()->createBlock(
            Message::class,
            '',
            [
                'pageType' => $this->getPageType($observer->getEvent()),
            ]
        );
        $shortcutButtons->addShortcut($message);
    }

    /**
     * @param Event $event
     * @return string
     */
    private function getPageType($event) : string
    {
        if ($event->getIsCatalogProduct()) {
            return 'product';
        }
        if ($event->getIsShoppingCart()) {
            return 'cart';
        }
        return 'minicart';
    }
}
