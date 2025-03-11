<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Block\Adminhtml\Form;

use Magento\PaymentServicesPaypal\Model\Adminhtml\SdkParams;
use Magento\Payment\Block\Form;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\UrlInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Backend\Model\Session\Quote;

class AdminHostedFields extends Form
{
    public const CC_SOURCE = 'cc';

    /**
     * Admin Hosted Field checkout template
     *
     * @var string
     */
    protected $_template = 'Magento_PaymentServicesPaypal::cc.phtml';

    /**
     * @var SdkParams
     */
    private $sdkParams;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @var Quote
     */
    private $sessionQuote;

    /**
     * @param Context $context
     * @param SdkParams $sdkParams
     * @param UrlInterface $url
     * @param Quote $sessionQuote
     * @param array $data
     */
    public function __construct(
        Context $context,
        SdkParams $sdkParams,
        UrlInterface $url,
        Quote $sessionQuote,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->sdkParams = $sdkParams;
        $this->url = $url;
        $this->sessionQuote = $sessionQuote;
    }

    /**
     * Get payment method code.
     *
     * @return string
     */
    public function getMethodCode()
    {
        return 'payment_services_paypal_hosted_fields';
    }

    /**
     * Get sdk params.
     *
     * @return false|string
     * @throws NoSuchEntityException
     */
    public function getSdkParams()
    {
        $websiteId = $this->sessionQuote->getQuote()->getStore()->getWebsiteId();
        $sdkParams = $this->sdkParams->getSdkParams($websiteId);
        return json_encode($sdkParams);
    }

    /**
     * Get create order url.
     *
     * @return string
     */
    public function getCreateOrderUrl()
    {
        return $this->url->getUrl('paymentservicespaypal/order/create');
    }
}
