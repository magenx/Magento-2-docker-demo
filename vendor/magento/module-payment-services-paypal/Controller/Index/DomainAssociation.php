<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Controller\Index;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\PaymentServicesPaypal\Model\Config;

/**
 * Processes request to domain association file and returns content as result
 */
class DomainAssociation implements HttpGetActionInterface
{
    /**
     * @var ResultFactory
     */
    private $resultPageFactory;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param ResultFactory $resultPageFactory
     * @param Config $config
     */
    public function __construct(
        ResultFactory $resultPageFactory,
        Config $config
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->config = $config;
    }

    /**
     * Generates security.txt data and returns it as result
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $result = $this->resultPageFactory->create(ResultFactory::TYPE_RAW);
        $result->setHeader('Content-Type', 'text/plain');
        $content = $this->config->getDomainAssociation();
        $result->setContents($content);
        return $result;
    }
}
