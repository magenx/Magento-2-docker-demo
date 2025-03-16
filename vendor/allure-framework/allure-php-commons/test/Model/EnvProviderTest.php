<?php

declare(strict_types=1);

namespace Qameta\Allure\Test\Model;

use PHPUnit\Framework\TestCase;
use Qameta\Allure\Model\EnvProvider;

use function json_encode;

/**
 * @covers \Qameta\Allure\Model\EnvProvider
 */
class EnvProviderTest extends TestCase
{
    /**
     * @param array $env
     * @return void
     * @dataProvider providerInvalidLabels
     */
    public function testInvalidLabels(array $env): void
    {
        $provider = new EnvProvider($env);
        self::assertEmpty($provider->getLabels());
    }

    /**
     * @return iterable<string, array{array}>
     */
    public static function providerInvalidLabels(): iterable
    {
        return [
            'Absent' => [[]],
            'Empty name' => [['' => 'a']],
            'Nothing after prefix' => [['ALLURE_LABEL_' => 'a']],
            'Text before prefix' => [['AALLURE_LABEL_B' => 'c']],
            'Prefix with wrong case' => [['Allure_Label_A' => 'b']],
            'Non-scalar value' => [['ALLURE_LABEL_A' => []]],
            'Null value' => [['ALLURE_LABEL_A' => null]],
        ];
    }

    /**
     * @param array  $env
     * @param string $expectedValue
     * @return void
     * @dataProvider providerValidLabels
     */
    public function testValidLabels(array $env, string $expectedValue): void
    {
        $provider = new EnvProvider($env);
        $actualValue = json_encode($provider->getLabels());
        self::assertJsonStringEqualsJsonString($expectedValue, $actualValue);
    }

    /**
     * @return iterable<string, array{array, string}>
     */
    public static function providerValidLabels(): iterable
    {
        return [
            'Single label' => [
                ['ALLURE_LABEL_A' => 'b'],
                <<<JSON
[
  {"name": "a", "value": "b"}
]
JSON,
            ],
            'Two labels' => [
                ['ALLURE_LABEL_A' => 'b', 'ALLURE_LABEL_C' => 'd'],
                <<<JSON
[
  {"name": "a", "value": "b"},
  {"name": "c", "value": "d"}
]
JSON,
            ],
        ];
    }
}
