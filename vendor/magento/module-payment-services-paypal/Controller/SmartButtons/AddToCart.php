<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Controller\SmartButtons;

use Magento\Checkout\Helper\Data;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Cart;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\Filter\LocalizedToNormalized;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Session\Generic;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Escaper;
use Magento\Quote\Api\Data\CartInterfaceFactory;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class AddToCart implements HttpPostActionInterface, CsrfAwareActionInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var Generic
     */
    private $paypalSession;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var CartInterfaceFactory
     */
    private $quoteFactory;

    /**
     * @var Cart
     */
    private $cart;

    /**
     * @var Validator
     */
    private $formKeyValidator;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ResolverInterface
     */
    private $localeResolver;

    /**
     * @var MessageManagerInterface
     */
    private $messageManager;

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Data
     */
    private $checkoutHelper;

    /**
     * @var QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;

    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param CheckoutSession $checkoutSession
     * @param CustomerSession $customerSession
     * @param Generic $paypalSession
     * @param CartInterfaceFactory $quoteFactory
     * @param Cart $cart
     * @param ProductRepositoryInterface $productRepository
     * @param Validator $formKeyValidator
     * @param StoreManagerInterface $storeManager
     * @param ResolverInterface $localeResolver
     * @param MessageManagerInterface $messageManager
     * @param ManagerInterface $eventManager
     * @param Escaper $escaper
     * @param UrlInterface $url
     * @param LoggerInterface $logger
     * @param Data $checkoutHelper
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        RequestInterface           $request,
        ResponseInterface          $response,
        CheckoutSession            $checkoutSession,
        CustomerSession            $customerSession,
        Generic                    $paypalSession,
        CartInterfaceFactory       $quoteFactory,
        Cart                       $cart,
        ProductRepositoryInterface $productRepository,
        Validator                  $formKeyValidator,
        StoreManagerInterface      $storeManager,
        ResolverInterface          $localeResolver,
        MessageManagerInterface    $messageManager,
        ManagerInterface           $eventManager,
        Escaper                    $escaper,
        UrlInterface               $url,
        LoggerInterface            $logger,
        Data                       $checkoutHelper,
        QuoteIdMaskFactory         $quoteIdMaskFactory
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->formKeyValidator = $formKeyValidator;
        $this->quoteFactory = $quoteFactory;
        $this->cart = $cart;
        $this->productRepository = $productRepository;
        $this->paypalSession = $paypalSession;
        $this->storeManager = $storeManager;
        $this->localeResolver = $localeResolver;
        $this->messageManager = $messageManager;
        $this->escaper = $escaper;
        $this->logger = $logger;
        $this->url = $url;
        $this->eventManager = $eventManager;
        $this->checkoutHelper = $checkoutHelper;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
    }

    /**
     * Retrieve product
     *
     * @return ProductInterface|null
     * @throws NoSuchEntityException
     */
    private function initializeProduct() :? ProductInterface
    {
        $productId = (int) $this->request->getParam('product');
        if ($productId) {
            $storeId = $this->storeManager->getStore()->getId();
            try {
                return $this->productRepository->getById($productId, false, $storeId);
            } catch (NoSuchEntityException $e) {
            }
        }
        return null;
    }

    /**
     * For checkout with Buttons from product page only
     *
     * Add displayed product to the cart
     *
     * @return ResponseInterface
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute() : ResponseInterface
    {
        if (!$this->formKeyValidator->validate($this->request)) {
            return $this->prepareErrorResponse(
                __('Your session has expired. Please refresh the page and try again.')->getText()
            );
        }
        $quote = $this->quoteFactory->create(
            [
                'data' => [
                    'store_id' => $this->storeManager->getStore()->getId()
                ]
            ]
        );
        if ($this->customerSession->getCustomerId()) {
            $quote->setCustomerId($this->customerSession->getCustomerId());
            $quote->setCustomerEmail($this->customerSession->getCustomer()->getEmail());
        }
        $origQuoteId = $this->checkoutSession->getQuoteId();
        $this->checkoutSession->replaceQuote($quote);
        $params = $this->request->getParams();
        try {
            if (isset($params['qty'])) {
                $filter = new LocalizedToNormalized(['locale' => $this->localeResolver->getLocale()]);
                $params['qty'] = $filter->filter($params['qty']);
            }
            $product = $this->initializeProduct();
            $related = $this->request->getParam('related_product');
            if (!$product || !$product->getIsSalable()) {
                return $this->prepareErrorResponse(__('Product is out of stock. Please try again later.')->getText());
            }
            $this->cart->addProduct($product, $params);
            if (!empty($related)) {
                $this->cart->addProductsByIds(explode(',', $related));
            }
            $this->cart->save();
            $this->eventManager->dispatch(
                'checkout_cart_add_product_complete',
                [
                    'product' => $product,
                    'request' => $this->request,
                    'response' => $this->response
                ]
            );
            if (!$this->customerSession->getCustomerId() &&
                !$this->checkoutHelper->isAllowedGuestCheckout($this->checkoutSession->getQuote())
            ) {
                $this->checkoutSession->setQuoteId($origQuoteId);
                return $this->prepareErrorResponse(
                    __('To check out, please sign in with your email address.')
                    ->getText()
                );
            }
            $this->paypalSession->setQuoteId($this->checkoutSession->getQuote()->getId());
            $this->paypalSession->setCustomerQuoteId($origQuoteId);
            $this->checkoutSession->setQuoteId($origQuoteId);
            if (!$this->cart->getQuote()->getHasError()) {
                $quoteIdMask = $this->getOrCreateQuoteIdMask($this->cart->getQuote()->getId());

                return $this->response->representJson(
                    json_encode(['success' => [
                        'quoteIdMask' => $quoteIdMask,
                        'quoteId' => $this->cart->getQuote()->getId(),
                    ]])
                );
            }
            $errors = $this->cart->getQuote()->getErrors();
            $displayErrors = [];
            foreach ($errors as $error) {
                $displayErrors[] = $error->getText();
            }
            $this->prepareErrorResponse(implode(' ', $displayErrors));
        } catch (LocalizedException $e) {
            $this->prepareErrorResponse($this->escaper->escapeHtml($e->getMessage()));
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->prepareErrorResponse(
                __('We can\'t add this item to your shopping cart right now. Please try again later.')->getText()
            );
        }
        $this->checkoutSession->setQuoteId($origQuoteId);
        return $this->response;
    }

    /**
     * @inheritdoc
     */
    public function createCsrfValidationException(RequestInterface $request) :? InvalidRequestException
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function validateForCsrf(RequestInterface $request) :? bool
    {
        return true;
    }

    /**
     * Build error response JSON
     *
     * @param string $error
     * @return ResponseInterface
     */
    private function prepareErrorResponse(string $error) : ResponseInterface
    {
        return $this->response->representJson(
            json_encode(
                [
                    'error' => $error
                ]
            )
        );
    }

    /**
     * Get or create, if not exists, a quote id mask
     *
     * @param string $quoteId
     * @return string
     */
    private function getOrCreateQuoteIdMask(string $quoteId) : string
    {
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($quoteId, 'quote_id');

        if (!$quoteIdMask->getMaskedId()) {
            $quoteIdMask->setQuoteId($quoteId)->save();
        }

        return $quoteIdMask->getMaskedId();
    }
}
