<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\Application\DispatchOutbox;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Symfony console command — relay/worker proces, který poll-uje outbox
 * a publikuje pending zprávy.
 *
 * Spuštění:
 *
 *   php bin/console app:outbox:dispatch
 *
 * V produkci by se tato úloha pouštěla buď ze supervisord/systemd v nekonečné
 * smyčce (s krátkou pauzou mezi cykly), nebo přes cron/k8s CronJob.
 */
#[AsCommand(
    name: 'app:outbox:dispatch',
    description: 'Vezme pending řádky z outbox tabulky a publikuje je subscriberům.',
)]
final class DispatchOutboxCommand extends Command
{
    public function __construct(
        private readonly OutboxRelay $relay,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'batch-size',
            null,
            InputOption::VALUE_REQUIRED,
            'Maximální počet zpráv v jedné dávce',
            '100',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $batchSize = max(1, (int) $input->getOption('batch-size'));

        $result = $this->relay->dispatchPending($batchSize);

        $io->success(sprintf(
            'Outbox relay hotov. Publikováno: %d, selhání: %d',
            $result['processed'],
            $result['failed'],
        ));

        return Command::SUCCESS;
    }
}
