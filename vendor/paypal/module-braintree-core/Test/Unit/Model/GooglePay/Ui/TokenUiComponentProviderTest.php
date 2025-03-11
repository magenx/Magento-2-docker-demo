<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Test\Unit\Model\GooglePay\Ui;

use Magento\Framework\Serialize\SerializerInterface;
use PayPal\Braintree\Model\GooglePay\Ui\TokenUiComponentProvider;
use Magento\Framework\UrlInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterfaceFactory;
use PayPal\Braintree\Model\GooglePay\Ui\ConfigProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @see TokenUiComponentProvider
 */
class TokenUiComponentProviderTest extends TestCase
{
    /**
     * @var TokenUiComponentInterfaceFactory|MockObject
     */
    private TokenUiComponentInterfaceFactory|MockObject $componentFactory;

    /**
     * @var UrlInterface|MockObject
     */
    private UrlInterface|MockObject $urlBuilder;

    /**
     * @var SerializerInterface|MockObject
     */
    private SerializerInterface|MockObject $serializer;

    /**
     * @var TokenUiComponentProvider
     */
    private TokenUiComponentProvider $tokenUiComponentProvider;

    protected function setUp(): void
    {
        $this->componentFactory = $this->getMockBuilder(TokenUiComponentInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $this->urlBuilder = $this->createMock(UrlInterface::class);

        $this->serializer = $this->createMock(SerializerInterface::class);

        $this->tokenUiComponentProvider = new TokenUiComponentProvider(
            $this->componentFactory,
            $this->urlBuilder,
            $this->serializer
        );
    }

    /**
     * @covers \PayPal\Braintree\Model\GooglePay\Ui\TokenUiComponentProvider::getComponentForToken
     */
    public function testGetComponentForToken(): void
    {
        $nonceUrl = 'https://payment/adminhtml/nonce/url';
        $type = 'VI';
        $maskedCC = '1111';
        $expirationDate = '11/2021';

        $expected = [
            'code' => ConfigProvider::METHOD_VAULT_CODE,
            'nonceUrl' => $nonceUrl,
            'details' => [
                'type' => $type,
                'maskedCC' => $maskedCC,
                'expirationDate' => $expirationDate
            ]
        ];

        $paymentToken = $this->createMock(PaymentTokenInterface::class);
        $paymentToken->expects(self::once())
            ->method('getTokenDetails')
            ->willReturn('{"type":"VI","maskedCC":"1111","expirationDate":"11\/2021"}');
        $paymentToken->expects(self::once())
            ->method('getPublicHash')
            ->willReturn('1986xa4d');

        $this->serializer->expects(self::once())
            ->method('unserialize')
            ->with('{"type":"VI","maskedCC":"1111","expirationDate":"11\/2021"}')
            ->willReturn($expected['details']);

        $this->urlBuilder->expects(static::once())
            ->method('getUrl')
            ->willReturn($nonceUrl);

        $tokenComponent = $this->createMock(TokenUiComponentInterface::class);
        $tokenComponent->expects(self::once())
            ->method('getConfig')
            ->willReturn($expected);

        $this->componentFactory->expects(self::once())
            ->method('create')
            ->willReturn($tokenComponent);

        $component = $this->tokenUiComponentProvider->getComponentForToken($paymentToken);
        self::assertEquals($tokenComponent, $component);
        self::assertEquals($expected, $component->getConfig());
    }
}
