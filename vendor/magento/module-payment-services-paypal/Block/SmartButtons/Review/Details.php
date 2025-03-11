<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Block\SmartButtons\Review;

use Magento\Sales\Model\ConfigInterface;
use Magento\Sales\Model\Order\Address;
use Magento\Framework\View\Element\Template\Context;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\PaymentServicesPaypal\Model\SmartButtons\Checkout;

/**
 * @api
 */
class Details extends \Magento\Checkout\Block\Cart\Totals
{
    /**
     * @var Address
     */
    private $address;

    /**
     * @param Context $context
     * @param CustomerSession $customerSession
     * @param CheckoutSession $checkoutSession
     * @param ConfigInterface $salesConfig
     * @param Checkout $checkout
     * @param array $data
     * @param array $layoutProcessors
     */
    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        CheckoutSession $checkoutSession,
        ConfigInterface $salesConfig,
        Checkout $checkout,
        array $data = [],
        array $layoutProcessors = []
    ) {
        parent::__construct(
            $context,
            $customerSession,
            $checkoutSession,
            $salesConfig,
            $layoutProcessors,
            $data
        );
        $customerQuoteId = $checkoutSession->getQuoteId();
        $checkoutSession->replaceQuote($checkout->getQuote());
        $checkoutSession->setQuoteId($customerQuoteId);
    }

    /**
     * @return Address
     */
    public function getAddress()
    {
        if (empty($this->address)) {
            $this->address = $this->getQuote()->getShippingAddress();
        }
        return $this->address;
    }

    /**
     * @return array
     */
    public function getTotals()
    {
        return $this->getQuote()->getTotals();
    }
}
