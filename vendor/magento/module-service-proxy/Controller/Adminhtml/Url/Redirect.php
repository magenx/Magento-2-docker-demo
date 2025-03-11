<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ServiceProxy\Controller\Adminhtml\Url;

use Magento\Backend\App\AbstractAction;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Backend\Model\UrlInterface;
use Magento\Backend\Helper\Data;

/**
 * Redirect to requested path
 */
class Redirect extends AbstractAction implements HttpGetActionInterface
{
    const ADMIN_RESOURCE = 'Magento_ServiceProxy::services';

    const ACTION_PATH = 'services/url/redirect/';

    /**
     * @var Data
     */
    private $backendData;

    /**
     * @var UrlInterface
     */
    private $backendUrl;

    /**
     * @param Context $context
     * @param Data $backendData
     * @param BackendUrl $backendUrl
     */
    public function __construct(
        Context $context,
        Data $backendData,
        UrlInterface $backendUrl
    ) {
        parent::__construct($context);
        $this->backendData = $backendData;
        $this->backendUrl = $backendUrl;
    }

    /**
     * Redirect to the specified admin path
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $actionUrl = str_replace($this->backendUrl->getRouteUrl(), '', $this->backendUrl->getUrl(self::ACTION_PATH));
        $redirectPath = explode($actionUrl, $this->backendUrl->getCurrentUrl())[1];
        if ($redirectPath === '') {
            return $resultRedirect->setPath($this->backendData->getHomePageUrl());
        }
        return $resultRedirect->setPath($redirectPath);
    }
}
