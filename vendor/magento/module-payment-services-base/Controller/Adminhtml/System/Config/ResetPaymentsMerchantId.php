<?php
/**
 * ADOBE CONFIDENTIAL
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
 */
declare(strict_types=1);

namespace Magento\PaymentServicesBase\Controller\Adminhtml\System\Config;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpDeleteActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\PaymentServicesBase\Model\MerchantService;

class ResetPaymentsMerchantId extends Action implements HttpDeleteActionInterface
{

    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Magento_PaymentServicesBase::paymentservicesbase';

    private const HTTP_STATUS_CODE_OK = 200;
    private const HTTP_STATUS_CODE_ERROR = 500;

    /**
     * @var MerchantService
     */
    private $merchantService;

    /**
     * @param MerchantService $merchantService
     * @param Context $context
     */
    public function __construct(
        MerchantService $merchantService,
        Context $context
    ) {
        parent::__construct($context);
        $this->merchantService = $merchantService;
    }

    /**
     * Dispatch the reset merchant request with Environment param
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $merchantIdEnvironment = $this->getRequest()->getParam('environment');

        $response = $this->merchantService->delete($merchantIdEnvironment);

        if (!isset($response['is_successful']) || !$response['is_successful']) {
            $result->setHttpResponseCode(self::HTTP_STATUS_CODE_ERROR);
            $this->messageManager->addErrorMessage(__("Payment Services ID can't be reset right now"));
        } else {
            $result->setHttpResponseCode(self::HTTP_STATUS_CODE_OK);
            $this->messageManager->addSuccessMessage(__("Payment Services ID was reset successfully"));
        }

        return $result;
    }
}
