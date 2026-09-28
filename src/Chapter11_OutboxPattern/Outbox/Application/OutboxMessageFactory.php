<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\Outbox\Application;

use App\Chapter11_OutboxPattern\Ordering\Application\IntegrationEvent\OrderPlacedIntegrationEvent;
use App\Chapter11_OutboxPattern\Outbox\Domain\OutboxMessage;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final readonly class OutboxMessageFactory
{
    /**
     * Whitelist typů, které smí relay vytvořit. Bez něj by o tom, jakou
     * třídu aplikace instancuje, rozhodoval message_type z databáze.
     *
     * @var array<string, class-string>
     */
    private const ALLOWED = [
        OrderPlacedIntegrationEvent::class => OrderPlacedIntegrationEvent::class,
    ];

    public function __construct(
        private DenormalizerInterface $denormalizer,
    ) {}

    public function reconstitute(OutboxMessage $message): object
    {
        $class = self::ALLOWED[$message->messageType] ?? null;

        if ($class === null) {
            throw new \RuntimeException(
                sprintf('Unknown message_type "%s" in outbox.', $message->messageType),
            );
        }

        return $this->denormalizer->denormalize($message->payload, $class, 'json');
    }
}
