<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\Outbox\Application;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final readonly class IntegrationEventSerializer
{
    public function __construct(
        private NormalizerInterface $normalizer,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function serialize(object $event): array
    {
        $payload = $this->normalizer->normalize($event, 'json');

        if (!is_array($payload)) {
            throw new \RuntimeException(
                sprintf('Integration event %s did not normalize to array.', $event::class),
            );
        }

        return $payload;
    }
}
