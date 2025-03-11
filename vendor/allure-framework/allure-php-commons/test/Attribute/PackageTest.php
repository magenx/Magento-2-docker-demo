<?php

declare(strict_types=1);

namespace Qameta\Allure\Test\Attribute;

use PHPUnit\Framework\TestCase;
use Qameta\Allure\Attribute\Package;

/**
 * @covers \Qameta\Allure\Attribute\Package
 * @covers \Qameta\Allure\Attribute\AbstractLabel
 */
class PackageTest extends TestCase
{
    use AnnotationTestTrait;

    public function testGetName_Always_ReturnsMatchingValue(): void
    {
        $epic = $this->getPackageInstance('demoWithValue');
        self::assertSame('package', $epic->getName());
    }

    public function testGetValue_WithValue_ReturnsSameString(): void
    {
        $epic = $this->getPackageInstance('demoWithValue');
        self::assertSame('a', $epic->getValue());
    }

    #[Package("a")]
    protected function demoWithValue(): void
    {
    }

    private function getPackageInstance(string $methodName): Package
    {
        return $this->getAttributeInstance(Package::class, $methodName);
    }
}
