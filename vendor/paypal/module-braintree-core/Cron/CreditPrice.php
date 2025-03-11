<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Cron;

use Exception;
use PayPal\Braintree\Api\CreditPriceRepositoryInterface;
use PayPal\Braintree\Api\Data\CreditPriceDataInterfaceFactory;
use PayPal\Braintree\Gateway\Config\PayPalCredit\Config as PayPalCreditConfig;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\LocalizedException;
use PayPal\Braintree\Model\Paypal\CreditApi;
use Psr\Log\LoggerInterface;

class CreditPrice
{
    /**
     * @var CreditPriceRepositoryInterface
     */
    private CreditPriceRepositoryInterface $creditPriceRepository;

    /**
     * @var ProductCollectionFactory
     */
    private ProductCollectionFactory $productCollection;

    /**
     * @var CreditPriceDataInterfaceFactory
     */
    private CreditPriceDataInterfaceFactory $creditPriceFactory;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var CreditApi
     */
    private CreditApi $creditApi;

    /**
     * @var PayPalCreditConfig
     */
    private PayPalCreditConfig $config;

    /**
     * CreditPrice constructor.
     *
     * @param CreditPriceRepositoryInterface $creditPriceRepository
     * @param CreditPriceDataInterfaceFactory $creditPriceDataInterfaceFactory
     * @param CreditApi $creditApi
     * @param ProductCollectionFactory $productCollection
     * @param LoggerInterface $logger
     * @param PayPalCreditConfig $config
     */
    public function __construct(
        CreditPriceRepositoryInterface $creditPriceRepository,
        CreditPriceDataInterfaceFactory $creditPriceDataInterfaceFactory,
        CreditApi $creditApi,
        ProductCollectionFactory $productCollection,
        LoggerInterface $logger,
        PayPalCreditConfig $config
    ) {
        $this->creditPriceRepository = $creditPriceRepository;
        $this->productCollection = $productCollection;
        $this->logger = $logger;
        $this->creditPriceFactory = $creditPriceDataInterfaceFactory;
        $this->creditApi = $creditApi;
        $this->config = $config;
    }

    /**
     * Calculate credit price
     *
     * @return $this
     * @throws AuthenticationException
     */
    public function execute(): static
    {
        if (!$this->config->isCalculatorEnabled()) {
            return $this;
        }

        // Retrieve paginated collection of product and their price
        $collection = $this->productCollection->create();
        $collection->addAttributeToSelect('price')
            ->setPageSize(100);

        $connection = $collection->getResource()->getConnection();
        $connection->beginTransaction();

        $lastPage = $collection->getLastPageNumber();
        for ($i = 1; $i <= $lastPage; $i++) {
            $collection->setCurPage($i);
            $collection->load();

            foreach ($collection as $product) {
                try {
                    // Delete by Product ID
                    $this->creditPriceRepository->deleteByProductId((int)$product->getId());

                    // Retrieve data from PayPal
                    $priceOptions = $this->creditApi->getPriceOptions((float)$product->getFinalPrice());
                    foreach ($priceOptions as $priceOption) {
                        // Populate model
                        $model = $this->creditPriceFactory->create();
                        $model->setProductId((int)$product->getId());
                        $model->setTerm($priceOption['term']);
                        $model->setMonthlyPayment($priceOption['monthly_payment']);
                        $model->setInstalmentRate($priceOption['instalment_rate']);
                        $model->setCostOfPurchase($priceOption['cost_of_purchase']);
                        $model->setTotalIncInterest($priceOption['total_inc_interest']);

                        $this->creditPriceRepository->save($model);
                    }
                } catch (AuthenticationException $e) {
                    $connection->rollBack();
                    throw $e;
                } catch (LocalizedException $e) {
                    $this->logger->critical($e->getMessage());
                } catch (Exception $e) {
                    $connection->rollBack();
                    $this->logger->critical($e->getMessage());
                    return $this;
                }
            }

            $collection->clear();
        }

        $connection->commit();

        return $this;
    }
}
