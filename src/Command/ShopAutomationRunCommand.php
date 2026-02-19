<?php

namespace App\Command;

use App\Service\ShopAutomationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:shop-automation:run',
    description: 'Run shop automation tasks (restock and stock alerts).'
)]
class ShopAutomationRunCommand extends Command
{
    public function __construct(private ShopAutomationService $automationService)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $restock = $this->automationService->runStockReplenishment();
        $alerts = $this->automationService->runStockAlerts();
        $zeroStock = $this->automationService->runStockZeroEmails();

        $output->writeln(sprintf(
            'Restock processed: %d, skipped: %d (threshold=%d, amount=%d)',
            $restock['processed'],
            $restock['skipped'],
            $restock['threshold'],
            $restock['restock_amount']
        ));

        $output->writeln(sprintf(
            'Stock alerts created: %d, skipped: %d (threshold=%d)',
            $alerts['created'],
            $alerts['skipped'],
            $alerts['threshold']
        ));

        $output->writeln(sprintf(
            'Stock zero emails sent: %d, skipped: %d',
            $zeroStock['sent'],
            $zeroStock['skipped']
        ));

        return Command::SUCCESS;
    }
}
