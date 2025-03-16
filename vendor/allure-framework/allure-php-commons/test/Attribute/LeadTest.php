<?php

declare(strict_types=1);

namespace Qameta\Allure\Test\Attribute;

use PHPUnit\Framework\TestCase;
use Qameta\Allure\Attribute\Lead;

/**
 * @covers \Qameta\Allure\Attribute\Lead
 * @covers \Qameta\Allure\Attribute\AbstractLabel
 */
class LeadTest extends TestCase
{
    use AnnotationTestTrait;

    public function testGetName_Always_ReturnsMatchingValue(): void
    {
        $epic = $this->getLeadInstance('demoWithValue');
        self::assertSame('lead', $epic->getName());
    }

    public function testGetValue_WithValue_ReturnsSameString(): void
    {
        $epic = $this->getLeadInstance('demoWithValue');
        self::assertSame('a', $epic->getValue());
    }

    #[Lead("a")]
    protected function demoWithValue(): void
    {
    }

    private function getLeadInstance(string $methodName): Lead
    {
        return $this->getAttributeInstance(Lead::class, $methodName);
    }
}
