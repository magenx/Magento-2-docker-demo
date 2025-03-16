<?php

declare(strict_types=1);

namespace Qameta\Allure\Test\Attribute;

use PHPUnit\Framework\TestCase;
use Qameta\Allure\Attribute;
use Qameta\Allure\Attribute\AttributeParser;
use Qameta\Allure\Attribute\AttributeSetInterface;
use Qameta\Allure\Setup\LinkTemplateCollectionInterface;

use function json_encode;

/**
 * @covers \Qameta\Allure\Attribute\AttributeParser
 */
class AttributeParserTest extends TestCase
{
    public function testGetLinks_ConstructedWithoutLinkAttributes_ReturnsEmptyList(): void
    {
        $parser = new AttributeParser(
            attributes: [
            ],
            linkTemplates: $this->createStub(LinkTemplateCollectionInterface::class),
        );

        self::assertEmpty($parser->getLinks());
    }

    public function testGetLinks_ConstructedWithLinkAttributes_ReturnsMatchingLinkModels(): void
    {
        $parser = new AttributeParser(
            attributes: [
                new Attribute\Link('a', 'b', Attribute\Link::TMS),
                new Attribute\Link('c', 'd', Attribute\Link::ISSUE),
            ],
            linkTemplates: $this->createStub(LinkTemplateCollectionInterface::class),
        );

        $expectedValue = <<<EOF
[
    {"name": "a", "url": "b", "type": "tms"},
    {"name": "c", "url": "d", "type": "issue"}
]
EOF;
        self::assertJsonStringEqualsJsonString(
            $expectedValue,
            json_encode($parser->getLinks()),
        );
    }

    public function testGetLabels_ConstructedWithoutLabelAttributes_ReturnsEmptyList(): void
    {
        $parser = new AttributeParser(
            attributes: [
            ],
            linkTemplates: $this->createStub(LinkTemplateCollectionInterface::class),
        );

        self::assertEmpty($parser->getLabels());
    }

    public function testGetLabels_ConstructedWithLabelAttributes_ReturnsMatchingLabelModelsInReverseOrder(): void
    {
        $parser = new AttributeParser(
            attributes: [
                new Attribute\Label('a', 'b'),
                new Attribute\Label('c', 'd'),
            ],
            linkTemplates: $this->createStub(LinkTemplateCollectionInterface::class),
        );

        $expectedValue = <<<EOF
[
    {"name": "c", "value": "d"},
    {"name": "a", "value": "b"}
]
EOF;
        self::assertJsonStringEqualsJsonString(
            $expectedValue,
            json_encode($parser->getLabels()),
        );
    }

    public function testGetLabels_ConstructedWithSetOfLabels_ReturnsMatchingLabelModelsInReverseOrder(): void
    {
        $attributeSet = $this->createStub(AttributeSetInterface::class);
        $attributeSet
            ->method('getAttributes')
            ->willReturn(
                [
                    new Attribute\Label('a', 'b'),
                    new Attribute\Label('c', 'd'),
                ],
            );
        $parser = new AttributeParser(
            attributes: [$attributeSet],
            linkTemplates: $this->createStub(LinkTemplateCollectionInterface::class),
        );

        $expectedValue = <<<EOF
[
    {"name": "c", "value": "d"},
    {"name": "a", "value": "b"}
]
EOF;
        self::assertJsonStringEqualsJsonString(
            $expectedValue,
            json_encode($parser->getLabels()),
        );
    }

    public function testGetParameters_ConstructedWithoutParameterAttributes_ReturnsEmptyList(): void
    {
        $parser = new AttributeParser(
            attributes: [
            ],
            linkTemplates: $this->createStub(LinkTemplateCollectionInterface::class),
        );

        self::assertEmpty($parser->getParameters());
    }

    public function testGetParameters_ConstructedWithParameterAttributes_ReturnsMatchingParams(): void
    {
        $parser = new AttributeParser(
            attributes: [
                new Attribute\Parameter('a', 'b', mode: Attribute\ParameterMode::HIDDEN),
                new Attribute\Parameter('c', 'd', excluded: true),
            ],
            linkTemplates: $this->createStub(LinkTemplateCollectionInterface::class),
        );

        $expectedValue = <<<EOF
[
    {"name": "a", "value": "b", "excluded": null, "mode": "hidden"},
    {"name": "c", "value": "d", "excluded": true, "mode": null}
]
EOF;
        self::assertJsonStringEqualsJsonString(
            $expectedValue,
            json_encode($parser->getParameters()),
        );
    }

    public function testGetParameters_ConstructedWithSetOfParameters_ReturnsMatchingParams(): void
    {
        $attributeSet = $this->createStub(AttributeSetInterface::class);
        $attributeSet
            ->method('getAttributes')
            ->willReturn(
                [
                    new Attribute\Parameter('a', 'b', mode: Attribute\ParameterMode::HIDDEN),
                    new Attribute\Parameter('c', 'd', excluded: true),
                ],
            );
        $parser = new AttributeParser(
            attributes: [$attributeSet],
            linkTemplates: $this->createStub(LinkTemplateCollectionInterface::class),
        );

        $expectedValue = <<<EOF
[
    {"name": "a", "value": "b", "excluded": null, "mode": "hidden"},
    {"name": "c", "value": "d", "excluded": true, "mode": null}
]
EOF;
        self::assertJsonStringEqualsJsonString(
            $expectedValue,
            json_encode($parser->getParameters()),
        );
    }

    public function testGetDisplayName_ConstructedWithoutDisplayNameAttribute_ReturnsNull(): void
    {
        $parser = new AttributeParser(
            attributes: [
            ],
            linkTemplates: $this->createStub(LinkTemplateCollectionInterface::class),
        );

        self::assertNull($parser->getDisplayName());
    }

    public function testGetDisplayName_ConstructedWithDisplayNameAttribute_ReturnsMatchingValue(): void
    {
        $parser = new AttributeParser(
            attributes: [
                new Attribute\DisplayName('a'),
            ],
            linkTemplates: $this->createStub(LinkTemplateCollectionInterface::class),
        );

        self::assertSame('a', $parser->getDisplayName());
    }


    public function testGetDescription_ConstructedWithoutDescriptionAttribute_ReturnsNull(): void
    {
        $parser = new AttributeParser(
            attributes: [
            ],
            linkTemplates: $this->createStub(LinkTemplateCollectionInterface::class),
        );

        self::assertNull($parser->getDescription());
    }

    public function testGetDescription_ConstructedWithDescriptionAttribute_ReturnsMatchingValue(): void
    {
        $parser = new AttributeParser(
            attributes: [
                new Attribute\Description('a'),
            ],
            linkTemplates: $this->createStub(LinkTemplateCollectionInterface::class),
        );

        self::assertSame('a', $parser->getDescription());
    }

    public function testGetDescription_ConstructedWithDescriptionHtmlAttribute_ReturnsNull(): void
    {
        $parser = new AttributeParser(
            attributes: [
                new Attribute\Description('a', isHtml: true),
            ],
            linkTemplates: $this->createStub(LinkTemplateCollectionInterface::class),
        );

        self::assertNull($parser->getDescription());
    }

    public function testGetDescriptionHtml_ConstructedWithoutDescriptionAttribute_ReturnsNull(): void
    {
        $parser = new AttributeParser(
            attributes: [
            ],
            linkTemplates: $this->createStub(LinkTemplateCollectionInterface::class),
        );

        self::assertNull($parser->getDescriptionHtml());
    }

    public function testGetDescriptionHtml_ConstructedWithDescriptionHtmlAttribute_ReturnsMatchingValue(): void
    {
        $parser = new AttributeParser(
            attributes: [
                new Attribute\Description('a', isHtml: true),
            ],
            linkTemplates: $this->createStub(LinkTemplateCollectionInterface::class),
        );

        self::assertSame('a', $parser->getDescriptionHtml());
    }

    public function testGetDescriptionHtml_ConstructedWithDescriptionAttribute_ReturnsNull(): void
    {
        $parser = new AttributeParser(
            attributes: [
                new Attribute\Description('a', isHtml: false),
            ],
            linkTemplates: $this->createStub(LinkTemplateCollectionInterface::class),
        );

        self::assertNull($parser->getDescriptionHtml());
    }
}
