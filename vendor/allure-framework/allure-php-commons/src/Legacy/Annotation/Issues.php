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
 * @deprecated Use native PHP attribute {@see Attribute\Issue}
 * @psalm-suppress MissingConstructor
 */
class Issues implements LegacyAnnotationInterface
{
    /**
     * @var list<string>
     * @Required
     */
    public array $issueKeys;

    public function getIssueKeys(): array
    {
        return $this->issueKeys;
    }

    /**
     * @return list<Attribute\Issue>
     */
    public function convert(): array
    {
        return array_map(
            fn (string $key) => new Attribute\Issue($key),
            $this->issueKeys,
        );
    }
}
