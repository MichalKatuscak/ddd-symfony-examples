<?php

declare(strict_types=1);

namespace App\Chapter07_Sagas\Warehouse\Application\Handler;

use App\Chapter07_Sagas\Warehouse\Application\Command\ReleaseStock;
use App\Chapter07_Sagas\Warehouse\Domain\StockService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'messenger.bus.command')]
final readonly class ReleaseStockHandler
{
    public function __construct(private StockService $stock) {}

    public function __invoke(ReleaseStock $command): void
    {
        // Kompenzace nevydává událost: na uvolnění rezervace nikdo nečeká.
        $this->stock->release($command->orderId);
    }
}
