<?php

declare(strict_types=1);

namespace Qameta\Allure\Test\Attribute;

use PHPUnit\Framework\TestCase;
use Qameta\Allure\Attribute\ParentSuite;

/**
 * @covers \Qameta\Allure\Attribute\ParentSuite
 * @covers \Qameta\Allure\Attribute\AbstractLabel
 */
class ParentSuiteTest extends TestCase
{
    use AnnotationTestTrait;

    public function testGetName_Always_ReturnsMatchingValue(): void
    {
        $epic = $this->getParentSuiteInstance('demoWithValue');
        self::assertSame('parentSuite', $epic->getName());
    }

    public function testGetValue_WithValue_ReturnsSameString(): void
    {
        $epic = $this->getParentSuiteInstance('demoWithValue');
        self::assertSame('a', $epic->getValue());
    }

    #[ParentSuite("a")]
    protected function demoWithValue(): void
    {
    }

    private function getParentSuiteInstance(string $methodName): ParentSuite
    {
        return $this->getAttributeInstance(ParentSuite::class, $methodName);
    }
}
