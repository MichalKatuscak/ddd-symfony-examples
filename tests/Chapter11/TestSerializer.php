<?php

declare(strict_types=1);

namespace App\Tests\Chapter11;

use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\UidNormalizer;
use Symfony\Component\Serializer\Serializer;

/** Serializer se stejnými normalizéry, jaké pro outbox používá framework. */
final class TestSerializer
{
    public static function create(): Serializer
    {
        return new Serializer([
            new UidNormalizer(),
            new DateTimeNormalizer(),
            new ArrayDenormalizer(),
            new ObjectNormalizer(),
        ]);
    }
}
