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
 * @deprecated Use native PHP attribute {@see Attribute\Features}
 * @psalm-suppress MissingConstructor
 */
class Features implements LegacyAnnotationInterface
{
    /**
     * @var list<string>
     * @Required
     */
    public array $featureNames;

    public function getFeatureNames(): array
    {
        return $this->featureNames;
    }

    /**
     * @return list<Attribute\Feature>
     */
    public function convert(): array
    {
        return array_map(
            fn (string $name) => new Attribute\Feature($name),
            $this->featureNames,
        );
    }
}
