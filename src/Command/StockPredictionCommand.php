<?php

namespace App\Command;

use App\Service\StockPredictionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:stock:predict',
    description: 'Predict stock depletion for all products using AI (Linear Regression)'
)]
class StockPredictionCommand extends Command
{
    public function __construct(private StockPredictionService $predictionService)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('🤖 Stock Prediction Engine (AI - Linear Regression)');

        $io->writeln('Analyzing sales history (30-day window)...');
        $startTime = microtime(true);

        $results = $this->predictionService->predictAllProducts();

        $duration = round((microtime(true) - $startTime) * 1000, 2);

        $io->newLine();
        $io->success([
            sprintf('✅ Predictions computed: %d products', $results['predicted']),
            sprintf('⏭️ Skipped (insufficient data): %d products', $results['skipped']),
            sprintf('⏱️ Execution time: %dms', $duration),
        ]);

        // Afficher les produits critiques
        $critical = $this->predictionService->getCriticalPredictions(30);
        if (!empty($critical)) {
            $io->newLine();
            $io->warning('🚨 CRITICAL ALERTS - Stock out risk within 30 days:');
            
            $tableData = [];
            foreach ($critical as $prediction) {
                $product = $prediction->getProduct();
                $tableData[] = [
                    $product->getNomProduit(),
                    $prediction->getCurrentStock(),
                    number_format($prediction->getDailyConsumption(), 2),
                    $prediction->getDaysUntilStockout() . ' days',
                    number_format($prediction->getConfidence() * 100, 1) . '%',
                ];
            }

            $io->table(
                ['Product', 'Current Stock', 'Daily Rate', 'Stockout In', 'Confidence'],
                $tableData
            );
        }

        return Command::SUCCESS;
    }
}
