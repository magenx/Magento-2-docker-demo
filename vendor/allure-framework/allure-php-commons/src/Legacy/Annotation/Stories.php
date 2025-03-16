<?php

declare(strict_types=1);

namespace Yandex\Allure\Adapter\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;
use Qameta\Allure\Attribute;
use Qameta\Allure\Legacy\Annotation\LegacyAnnotationInterface;

use function array_map;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 * @deprecated Use native PHP attribute {@see Attribute\Story}
 * @psalm-suppress MissingConstructor
 */
class Stories implements LegacyAnnotationInterface
{
    /**
     * @var array
     * @psalm-var list<string>
     * @Required
     */
    public array $stories;

    public function getStories(): array
    {
        return $this->stories;
    }

    /**
     * @return list<Attribute\Story>
     */
    public function convert(): array
    {
        return array_map(
            fn (string $value) => new Attribute\Story($value),
            $this->stories,
        );
    }
}
