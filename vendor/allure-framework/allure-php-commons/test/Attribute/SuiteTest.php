<?php

declare(strict_types=1);

namespace Qameta\Allure\Test\Attribute;

use PHPUnit\Framework\TestCase;
use Qameta\Allure\Attribute\Suite;

/**
 * @covers \Qameta\Allure\Attribute\Suite
 * @covers \Qameta\Allure\Attribute\AbstractLabel
 */
class SuiteTest extends TestCase
{
    use AnnotationTestTrait;

    public function testGetName_Always_ReturnsMatchingValue(): void
    {
        $epic = $this->getSuiteInstance('demoWithValue');
        self::assertSame('suite', $epic->getName());
    }

    public function testGetValue_WithValue_ReturnsSameString(): void
    {
        $epic = $this->getSuiteInstance('demoWithValue');
        self::assertSame('a', $epic->getValue());
    }

    #[Suite("a")]
    protected function demoWithValue(): void
    {
    }

    private function getSuiteInstance(string $methodName): Suite
    {
        return $this->getAttributeInstance(Suite::class, $methodName);
    }
}
