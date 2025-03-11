<?php

declare(strict_types=1);

namespace Qameta\Allure\Test\Attribute;

use PHPUnit\Framework\TestCase;
use Qameta\Allure\Attribute\Owner;

/**
 * @covers \Qameta\Allure\Attribute\Owner
 * @covers \Qameta\Allure\Attribute\AbstractLabel
 */
class OwnerTest extends TestCase
{
    use AnnotationTestTrait;

    public function testGetName_Always_ReturnsMatchingValue(): void
    {
        $epic = $this->getOwnerInstance('demoWithValue');
        self::assertSame('owner', $epic->getName());
    }

    public function testGetValue_WithValue_ReturnsSameString(): void
    {
        $epic = $this->getOwnerInstance('demoWithValue');
        self::assertSame('a', $epic->getValue());
    }

    #[Owner("a")]
    protected function demoWithValue(): void
    {
    }

    private function getOwnerInstance(string $methodName): Owner
    {
        return $this->getAttributeInstance(Owner::class, $methodName);
    }
}
