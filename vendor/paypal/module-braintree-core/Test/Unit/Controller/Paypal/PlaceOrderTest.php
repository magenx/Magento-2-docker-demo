<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace PayPal\Braintree\Test\Unit\Controller\Paypal;

use Magento\Framework\Exception\NotFoundException;
use PayPal\Braintree\Controller\Paypal\PlaceOrder;
use PayPal\Braintree\Gateway\Config\PayPal\Config;
use PayPal\Braintree\Model\Paypal\Helper\OrderPlace;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @see PlaceOrder
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PlaceOrderTest extends TestCase
{
    /**
     * @var OrderPlace|MockObject
     */
    private OrderPlace|MockObject $orderPlaceMock;

    /**
     * @var Config|MockObject
     */
    private Config|MockObject $configMock;

    /**
     * @var Session|MockObject
     */
    private Session|MockObject $checkoutSessionMock;

    /**
     * @var RequestInterface|MockObject
     */
    private RequestInterface|MockObject $requestMock;

    /**
     * @var ResultFactory|MockObject
     */
    private ResultFactory|MockObject $resultFactoryMock;

    /**
     * @var ManagerInterface|MockObject
     */
    protected ManagerInterface|MockObject $messageManagerMock;

    /**
     * @var PlaceOrder
     */
    private PlaceOrder $placeOrder;

    protected function setUp(): void
    {
        /** @var Context|MockObject $contextMock */
        $contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getPostValue'])
            ->getMockForAbstractClass();
        $this->resultFactoryMock = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->checkoutSessionMock = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderPlaceMock = $this->getMockBuilder(OrderPlace::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->messageManagerMock = $this->getMockBuilder(ManagerInterface::class)
            ->getMockForAbstractClass();

        $contextMock->expects(self::once())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $contextMock->expects(self::once())
            ->method('getResultFactory')
            ->willReturn($this->resultFactoryMock);
        $contextMock->expects(self::once())
            ->method('getMessageManager')
            ->willReturn($this->messageManagerMock);

        $this->placeOrder = new PlaceOrder(
            $contextMock,
            $this->configMock,
            $this->checkoutSessionMock,
            $this->orderPlaceMock
        );
    }

    /**
     * @throws NotFoundException
     */
    public function testExecute()
    {
        $agreement = ['test-data'];

        $quoteMock = $this->getQuoteMock();
        $quoteMock->expects(self::once())
            ->method('getItemsCount')
            ->willReturn(1);

        $resultMock = $this->getResultMock();
        $resultMock->expects(self::once())
            ->method('setPath')
            ->with('checkout/onepage/success')
            ->willReturnSelf();

        $this->resultFactoryMock->expects(self::once())
            ->method('create')
            ->with(ResultFactory::TYPE_REDIRECT)
            ->willReturn($resultMock);

        $this->requestMock->expects(self::once())
            ->method('getPostValue')
            ->with('agreement', [])
            ->willReturn($agreement);

        $this->checkoutSessionMock->expects(self::once())
            ->method('getQuote')
            ->willReturn($quoteMock);

        $this->orderPlaceMock->expects(self::once())
            ->method('execute')
            ->with($quoteMock, [0]);

        $this->messageManagerMock->expects(self::never())
            ->method('addExceptionMessage');

        self::assertEquals($this->placeOrder->execute(), $resultMock);
    }

    /**
     * @throws NotFoundException
     */
    public function testExecuteException()
    {
        $agreement = ['test-data'];

        $quote = $this->getQuoteMock();
        $quote->expects(self::once())
            ->method('getItemsCount')
            ->willReturn(0);

        $resultMock = $this->getResultMock();
        $resultMock->expects(self::once())
            ->method('setPath')
            ->with('checkout/cart')
            ->willReturnSelf();

        $this->resultFactoryMock->expects(self::once())
            ->method('create')
            ->with(ResultFactory::TYPE_REDIRECT)
            ->willReturn($resultMock);

        $this->requestMock->expects(self::once())
            ->method('getPostValue')
            ->with('agreement', [])
            ->willReturn($agreement);

        $this->checkoutSessionMock->expects(self::once())
            ->method('getQuote')
            ->willReturn($quote);

        $this->orderPlaceMock->expects(self::never())
            ->method('execute');

        $this->messageManagerMock->expects(self::once())
            ->method('addExceptionMessage')
            ->with(self::isInstanceOf('Exception'));

        self::assertEquals($this->placeOrder->execute(), $resultMock);
    }

    /**
     * @return ResultInterface|MockObject
     */
    private function getResultMock(): ResultInterface|MockObject
    {
        return $this->getMockBuilder(ResultInterface::class)
            ->setMethods(['setPath'])
            ->getMockForAbstractClass();
    }

    /**
     * @return Quote|MockObject
     */
    private function getQuoteMock(): Quote|MockObject
    {
        return $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
