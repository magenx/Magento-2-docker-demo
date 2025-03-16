<?php

declare(strict_types=1);

namespace Qameta\Allure\Test\Attribute;

use PHPUnit\Framework\TestCase;
use Qameta\Allure\Attribute\Tag;

/**
 * @covers \Qameta\Allure\Attribute\Tag
 * @covers \Qameta\Allure\Attribute\AbstractLabel
 */
class TagTest extends TestCase
{
    use AnnotationTestTrait;

    public function testGetName_Always_ReturnsMatchingValue(): void
    {
        $epic = $this->getTagInstance('demoWithValue');
        self::assertSame('tag', $epic->getName());
    }

    public function testGetValue_WithValue_ReturnsSameString(): void
    {
        $epic = $this->getTagInstance('demoWithValue');
        self::assertSame('a', $epic->getValue());
    }

    #[Tag("a")]
    protected function demoWithValue(): void
    {
    }

    private function getTagInstance(string $methodName): Tag
    {
        return $this->getAttributeInstance(Tag::class, $methodName);
    }
}
