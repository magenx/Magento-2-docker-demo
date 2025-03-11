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

namespace Magento\PaymentServicesBase\Test\Unit\Model\ServicesConnector;

use Magento\ServicesConnector\Api\KeyValidationInterface;
use PHPUnit\Framework\TestCase;
use Magento\PaymentServicesBase\Model\ServicesConnector\InMemoryKeyValidor;

class InMemoryKeyValidorTest extends TestCase
{
    /**
     * @var KeyValidationInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private KeyValidationInterface|\PHPUnit\Framework\MockObject\MockObject $keyValidatorMock;

    /**
     * @var InMemoryKeyValidor
     */
    private InMemoryKeyValidor $inMemoryKeyValidor;

    /**
     * Setup the test
     */
    protected function setUp(): void
    {
        $this->keyValidatorMock = $this->createMock(KeyValidationInterface::class);
        $this->inMemoryKeyValidor = new InMemoryKeyValidor($this->keyValidatorMock);
    }

    public function testValidationWithNoCache(): void
    {
        $extension = 'Magento_PaymentServicesBase';
        $environment = 'production';
        $this->keyValidatorMock->expects($this->once())
            ->method('execute')
            ->with($extension, $environment)
            ->willReturn(true);

        $this->assertTrue($this->inMemoryKeyValidor->execute($extension, $environment));
    }

    public function testValidationWithCache(): void
    {
        $extension = 'Magento_PaymentServicesBase';
        $environment = 'production';
        $this->keyValidatorMock->expects($this->once())
            ->method('execute')
            ->with($extension, $environment)
            ->willReturn(true);

        $this->assertTrue($this->inMemoryKeyValidor->execute($extension, $environment));
        $this->assertTrue($this->inMemoryKeyValidor->execute($extension, $environment));
    }

    public function testValidationWithDifferentEnvironment(): void
    {
        $extension = 'Magento_PaymentServicesBase';
        $environment1 = 'production';
        $environment2 = 'sandbox';

        $this->keyValidatorMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturnOnConsecutiveCalls(true, false);

        $this->assertTrue($this->inMemoryKeyValidor->execute($extension, $environment1));
        $this->assertTrue($this->inMemoryKeyValidor->execute($extension, $environment1));

        $this->assertFalse($this->inMemoryKeyValidor->execute($extension, $environment2));
        $this->assertFalse($this->inMemoryKeyValidor->execute($extension, $environment2));
    }
}
