<?php

declare(strict_types=1);

use Codeception\Module\Sequence;

function sq(int|string $id = null): string
{
    if ($id && isset(Sequence::$hash[$id])) {
        return Sequence::$hash[$id];
    }

    $prefix = str_replace('{id}', (string)$id, Sequence::$prefix);
    $sequence = $prefix . uniqid((string)$id);
    if ($id) {
        Sequence::$hash[$id] = $sequence;
    }

    return $sequence;
}

function sqs(int|string $id = null): string
{
    if ($id && isset(Sequence::$suiteHash[$id])) {
        return Sequence::$suiteHash[$id];
    }

    $prefix = str_replace('{id}', (string)$id, Sequence::$prefix);
    $sequence = $prefix . uniqid((string)$id);
    if ($id) {
        Sequence::$suiteHash[$id] = $sequence;
    }

    return $sequence;
}
