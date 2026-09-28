<?php

declare(strict_types=1);

namespace App\Chapter07_Sagas\Ordering\Infrastructure\Command;

use App\Chapter07_Sagas\Ordering\Application\Saga\OrderSaga;
use App\Chapter07_Sagas\Ordering\Application\Saga\OrderSagaRepository;
use App\Chapter07_Sagas\Ordering\Application\Saga\OrderSagaStatus;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Detekce zaseklých ság podle 14.11. V ukázce drží stav ság repozitář
 * v paměti, takže nový proces začíná bez ság; logiku prahů ověřuje
 * CheckStaleSagasCommandTest nad naplněným repozitářem.
 */
#[AsCommand(name: 'app:saga:check-stale', description: 'Najde ságy, které v mezistavu stojí déle, než jejich stav dovoluje')]
final class CheckStaleSagasCommand extends Command
{
    public function __construct(
        private readonly OrderSagaRepository $sagaRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $now = new \DateTimeImmutable();

        // Repozitář vrátí kandidáty podle nejkratšího prahu, přesný práh
        // pak určí stav ságy.
        $staleSagas = array_values(array_filter(
            $this->sagaRepository->findStale($now->modify('-5 minutes')),
            fn (OrderSaga $saga): bool => $saga->updatedAt()
                < $now->modify('-' . $this->maxIdle($saga->status())),
        ));

        if (count($staleSagas) === 0) {
            $io->success('Žádné zaseklé ságy.');

            return Command::SUCCESS;
        }

        $io->warning(sprintf('Nalezeno %d zaseklých ság:', count($staleSagas)));

        foreach ($staleSagas as $saga) {
            $io->writeln(sprintf(
                '  [%s] %s – stav: %s, poslední aktivita: %s',
                $saga->correlationId(),
                $saga->sagaType(),
                $saga->status()->value,
                $saga->updatedAt()->format('Y-m-d H:i:s'),
            ));
        }

        return Command::FAILURE;
    }

    /**
     * Práh = timeout kroku z 14.08 plus rezerva. Jeden práh pro všechny
     * stavy nestačí: zásilka smí čekat na dopravce celý den, rezervace
     * skladu jen sekundy. Sága zaseklá i po prahu znamená, že selhal
     * i naplánovaný CheckSagaTimeout.
     */
    private function maxIdle(OrderSagaStatus $status): string
    {
        return match ($status) {
            OrderSagaStatus::AwaitingStockReservation => '5 minutes',
            OrderSagaStatus::AwaitingPayment => '15 minutes',
            OrderSagaStatus::AwaitingShipment => '26 hours',
            default => '30 minutes', // Compensating
        };
    }
}
