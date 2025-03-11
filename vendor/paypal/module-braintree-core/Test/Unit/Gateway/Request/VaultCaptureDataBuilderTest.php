<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace PayPal\Braintree\Test\Unit\Gateway\Request;

use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use PayPal\Braintree\Gateway\Config\Config;
use PayPal\Braintree\Gateway\Helper\SubjectReader;
use PayPal\Braintree\Gateway\Request\VaultCaptureDataBuilder;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Api\Data\OrderPaymentExtension;
use Magento\Sales\Model\Order\Payment;
use Magento\Vault\Model\PaymentToken;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class VaultCaptureDataBuilderTest extends TestCase
{
    /**
     * @var VaultCaptureDataBuilder
     */
    private VaultCaptureDataBuilder $builder;

    /**
     * @var PaymentDataObjectInterface|MockObject
     */
    private PaymentDataObjectInterface|MockObject $paymentDO;

    /**
     * @var Payment|MockObject
     */
    private Payment|MockObject $payment;

    /**
     * @var SubjectReader|MockObject
     */
    private MockObject|SubjectReader $subjectReader;

    /**
     * @var Config|MockObject
     */
    private Config|MockObject $configMock;

    /**
     * @var OrderAdapterInterface|MockObject
     */
    private OrderAdapterInterface|MockObject $orderMock;

    protected function setUp(): void
    {
        $this->paymentDO = $this->createMock(PaymentDataObjectInterface::class);
        $this->payment = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderMock = $this->createMock(OrderAdapterInterface::class);

        $this->paymentDO->expects(static::once())
            ->method('getPayment')
            ->willReturn($this->payment);

        $this->paymentDO->expects(static::once())
            ->method('getOrder')
            ->willReturn($this->orderMock);

        $this->subjectReader = $this->getMockBuilder(SubjectReader::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->builder = new VaultCaptureDataBuilder($this->subjectReader, $this->configMock);
    }

    /**
     * \PayPal\Braintree\Gateway\Request\VaultCaptureDataBuilder::build
     */
    public function testBuild()
    {
        $amount = 30.00;
        $token = '5tfm4c';
        $merchantAccountId = 'test';
        $buildSubject = [
            'payment' => $this->paymentDO,
            'amount' => $amount
        ];

        $expected = [
            'amount' => $amount,
            'paymentMethodToken' => $token,
            'merchantAccountId' => $merchantAccountId
        ];

        $this->subjectReader->expects(self::once())
            ->method('readPayment')
            ->with($buildSubject)
            ->willReturn($this->paymentDO);
        $this->subjectReader->expects(self::once())
            ->method('readAmount')
            ->with($buildSubject)
            ->willReturn($amount);

        $paymentExtension = $this->getMockBuilder(OrderPaymentExtension::class)
            ->setMethods(['getVaultPaymentToken'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $paymentToken = $this->getMockBuilder(PaymentToken::class)
            ->disableOriginalConstructor()
            ->getMock();

        $paymentExtension->expects(static::once())
            ->method('getVaultPaymentToken')
            ->willReturn($paymentToken);
        $this->payment->expects(static::once())
            ->method('getExtensionAttributes')
            ->willReturn($paymentExtension);

        $paymentToken->expects(static::once())
            ->method('getGatewayToken')
            ->willReturn($token);

        $this->configMock->expects(static::once())
            ->method('getMerchantAccountId')
            ->willReturn($merchantAccountId);

        $this->orderMock->expects(static::once())
            ->method('getStoreId')
            ->willReturn(1);

        $result = $this->builder->build($buildSubject);
        static::assertEquals($expected, $result);
    }
}
