<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Controller\Paypal;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Exception\LocalizedException;
use PayPal\Braintree\Model\Paypal\CreditApi;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Webapi\Exception;

class Cart extends Action implements HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * @var CreditApi
     */
    private CreditApi $creditApi;

    /**
     * Cart constructor.
     * @param Context $context
     * @param CreditApi $creditApi
     */
    public function __construct(
        Context $context,
        CreditApi $creditApi
    ) {
        parent::__construct($context);
        $this->creditApi = $creditApi;
    }

    /**
     * @inheritdoc
     */
    public function execute(): Json|ResultInterface|ResponseInterface
    {
        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $amount = number_format((float)$this->getRequest()->getParam('amount', 0), 2, '.', '');

        if (!$amount || $amount <= 0) {
            return $this->processBadRequest($response);
        }

        try {
            $results = $this->creditApi->getPriceOptions((float)$amount);
            $options = [];
            foreach ($results as $priceOption) {
                $options[] = [
                    'term' => $priceOption['term'],
                    'monthlyPayment' => $priceOption['monthly_payment'],
                    'apr' => $priceOption['instalment_rate'],
                    'cost' => $priceOption['cost_of_purchase'],
                    'costIncInterest' => $priceOption['total_inc_interest']
                ];
            }

            // Sort $options by term, ascending.
            usort($options, static function ($a, $b) {
                return $a['term'] <=> $b['term'];
            });

            $response->setData($options);
        } catch (LocalizedException|Exception $exception) {
            return $this->processBadRequest($response, $exception);
        }

        return $response;
    }

    /**
     * Return response for bad request
     *
     * @param ResultInterface $response
     * @param LocalizedException|Exception|null $exception
     * @return ResultInterface
     */
    private function processBadRequest(
        ResultInterface $response,
        LocalizedException|Exception $exception = null
    ): ResultInterface {
        $response->setHttpResponseCode(Exception::HTTP_BAD_REQUEST);
        if ($exception === null || empty($exception->getMessage())) {
            $response->setData([
                'message' => __('No Credit Options available')
            ]);
        } else {
            $response->setData([
                'message' => __($exception->getMessage())
            ]);
        }

        return $response;
    }
}
