<?php

declare(strict_types=1);

namespace Qameta\Allure\Test\Attribute;

use PHPUnit\Framework\TestCase;
use Qameta\Allure\Attribute\Layer;

/**
 * @covers \Qameta\Allure\Attribute\Layer
 * @covers \Qameta\Allure\Attribute\AbstractLabel
 */
class LayerTest extends TestCase
{
    use AnnotationTestTrait;

    public function testGetName_Always_ReturnsMatchingValue(): void
    {
        $epic = $this->getLayerInstance('demoWithValue');
        self::assertSame('layer', $epic->getName());
    }

    public function testGetValue_WithValue_ReturnsSameString(): void
    {
        $epic = $this->getLayerInstance('demoWithValue');
        self::assertSame('a', $epic->getValue());
    }

    #[Layer("a")]
    protected function demoWithValue(): void
    {
    }

    private function getLayerInstance(string $methodName): Layer
    {
        return $this->getAttributeInstance(Layer::class, $methodName);
    }
}
