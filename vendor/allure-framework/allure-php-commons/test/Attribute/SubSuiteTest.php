<?php

declare(strict_types=1);

namespace Qameta\Allure\Test\Attribute;

use PHPUnit\Framework\TestCase;
use Qameta\Allure\Attribute\SubSuite;

/**
 * @covers \Qameta\Allure\Attribute\SubSuite
 * @covers \Qameta\Allure\Attribute\AbstractLabel
 */
class SubSuiteTest extends TestCase
{
    use AnnotationTestTrait;

    public function testGetName_Always_ReturnsMatchingValue(): void
    {
        $epic = $this->getSubSuiteInstance('demoWithValue');
        self::assertSame('subSuite', $epic->getName());
    }

    public function testGetValue_WithValue_ReturnsSameString(): void
    {
        $epic = $this->getSubSuiteInstance('demoWithValue');
        self::assertSame('a', $epic->getValue());
    }

    #[SubSuite("a")]
    protected function demoWithValue(): void
    {
    }

    private function getSubSuiteInstance(string $methodName): SubSuite
    {
        return $this->getAttributeInstance(SubSuite::class, $methodName);
    }
}
