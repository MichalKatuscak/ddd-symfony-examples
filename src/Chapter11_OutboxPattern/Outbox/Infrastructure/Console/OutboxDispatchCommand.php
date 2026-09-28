<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\Outbox\Infrastructure\Console;

use App\Chapter11_OutboxPattern\Outbox\Application\OutboxRelay;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Polling Publisher: trvale běžící proces pod supervisord/systemd.
 *
 * Smyčka má časový limit; po jeho vypršení se proces čistě ukončí
 * a správce procesů ho nastartuje znovu. Relay musí běžet jako jediný
 * worker, nebo vybírat řádky přes FOR UPDATE SKIP LOCKED.
 *
 * V ukázce drží outbox data v paměti, takže nový proces začíná s prázdnou
 * tabulkou. Příkaz ukazuje tvar smyčky: php bin/console app:outbox:dispatch --time-limit=1
 */
#[AsCommand(
    name: 'app:outbox:dispatch',
    description: 'Polluje outbox tabulku a publikuje pending události.',
)]
final class OutboxDispatchCommand extends Command
{
    public function __construct(
        private readonly OutboxRelay $relay,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'time-limit',
            null,
            InputOption::VALUE_REQUIRED,
            'Po kolika sekundách se proces ukončí (správce procesů ho nastartuje znovu).',
            3600,
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $deadline = time() + (int) $input->getOption('time-limit');
        $backoff = 1; // sekundy čekání při výpadku brokera

        while (time() < $deadline) {
            $result = $this->relay->dispatchPending(100);

            if ($result['brokerUnavailable']) {
                // Výpadek brokera se nepočítá do attempts žádného řádku.
                // Čeká celý worker: 1 s, 2 s, 4 s … nejvýš 30 s.
                $output->writeln(sprintf(
                    '<error>[outbox] broker unavailable, retrying in %d s</error>',
                    $backoff,
                ));
                sleep($backoff);
                $backoff = min($backoff * 2, 30);

                continue;
            }

            $backoff = 1;

            if ($result['processed'] === 0 && $result['failed'] === 0) {
                usleep(100_000); // 100 ms polling interval

                continue;
            }

            $output->writeln(sprintf(
                '[outbox] dispatched %d, failed %d',
                $result['processed'],
                $result['failed'],
            ));
        }

        return Command::SUCCESS; // čistý exit – supervisord startuje znovu
    }
}
