<?php

declare(strict_types=1);

namespace Qameta\Allure\Model;

use function is_scalar;
use function is_string;
use function str_starts_with;
use function strlen;
use function strtolower;
use function substr;

final class EnvProvider implements ModelProviderInterface
{
    use ModelProviderTrait;

    private const ENV_LABEL_PREFIX = 'ALLURE_LABEL_';

    /**
     * @var array<string, string>
     */
    private array $env;

    public function __construct(array $env)
    {
        $this->env = $this->normalizeEnv($env);
    }

    /**
     * @param array $env
     * @return array<string, string>
     */
    private function normalizeEnv(array $env): array
    {
        $normalizedEnv = [];
        /** @psalm-var mixed $value */
        foreach ($env as $key => $value) {
            if (is_string($key) && is_scalar($value)) {
                $normalizedEnv[$key] = (string) $value;
            }
        }

        return $normalizedEnv;
    }

    /**
     * @return list<Label>
     */
    public function getLabels(): array
    {
        $labels = [];
        $prefixLength = strlen(self::ENV_LABEL_PREFIX);
        foreach ($this->env as $key => $value) {
            $name = str_starts_with($key, self::ENV_LABEL_PREFIX)
                ? substr($key, $prefixLength)
                : '';
            if ('' != $name) {
                $labels[] = new Label(strtolower($name), $value);
            }
        }

        return $labels;
    }
}
