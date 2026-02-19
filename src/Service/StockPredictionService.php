<?php

namespace App\Service;

use App\Entity\Produit;
use App\Entity\StockPrediction;
use App\Repository\CommandeRepository;
use App\Repository\ProduitRepository;
use App\Repository\StockPredictionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Phpml\Regression\LeastSquares;

class StockPredictionService
{
    private int $forecastDays = 30;
    private int $minDataPoints = 3;

    public function __construct(
        private CommandeRepository $commandeRepository,
        private ProduitRepository $produitRepository,
        private StockPredictionRepository $predictionRepository,
        private EntityManagerInterface $em
    ) {}

    /**
     * Calcule les prédictions pour tous les produits
     */
    public function predictAllProducts(): array
    {
        $products = $this->produitRepository->findAll();
        $results = [
            'predicted' => 0,
            'skipped' => 0,
            'critical' => 0,
        ];

        foreach ($products as $product) {
            if ($this->predictProductStock($product)) {
                $results['predicted']++;
            } else {
                $results['skipped']++;
            }
        }

        return $results;
    }

    /**
     * Prédit le stock pour un produit donné
     */
    public function predictProductStock(Produit $product): bool
    {
        $currentStock = (int) $product->getQuantiteDisponible();
        
        // Récupérer l'historique des 30 derniers jours
        $dailySales = $this->getSalesData($product, $this->forecastDays);

        if (count($dailySales) < $this->minDataPoints) {
            return false;
        }

        // Préparer les données pour la régression
        $x = [];
        $y = [];
        foreach ($dailySales as $day => $quantity) {
            $x[] = [$day];
            $y[] = $quantity;
        }

        try {
            // Linear Regression avec php-ml
            $regression = new LeastSquares();
            $regression->train($x, $y);

            // Calculer la pente (consommation journalière prédite)
            $prediction = $regression->predict([30]);
            $dailyConsumption = max(0, (float) $prediction);

            // Prédire quand le stock sera 0
            $daysUntilStockout = $dailyConsumption > 0 
                ? (int) ceil($currentStock / $dailyConsumption)
                : 999;

            // Limiter à max 999 jours
            $daysUntilStockout = min($daysUntilStockout, 999);

            // Calculer la confiance basée sur:
            // - Nombre de points de données (plus = confiance )
            // - Variance des données
            $confidence = $this->calculateConfidence(count($dailySales), $y);

            // Date prédite de rupture
            $stockoutDate = null;
            if ($daysUntilStockout < 999) {
                $stockoutDate = (new \DateTimeImmutable())->modify("+{$daysUntilStockout} days");
            }

            // Créer/Mettre à jour la prédiction
            $prediction = $this->predictionRepository->findOneBy(['product' => $product]);
            if (!$prediction) {
                $prediction = new StockPrediction();
                $prediction->setProduct($product);
            }

            $prediction->setCurrentStock($currentStock);
            $prediction->setDailyConsumption($dailyConsumption);
            $prediction->setDaysUntilStockout($daysUntilStockout);
            $prediction->setPredictedStockoutDate($stockoutDate);
            $prediction->setConfidence($confidence);
            $prediction->setMetadata([
                'data_points' => count($dailySales),
                'forecast_window' => $this->forecastDays,
                'calculated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ]);

            $this->em->persist($prediction);
            $this->em->flush();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Récupère les ventes journalières pour les N derniers jours
     */
    private function getSalesData(Produit $product, int $days): array
    {
        $cutoffDate = (new \DateTimeImmutable())->modify("-{$days} days");

        // Requête pour obtenir toutes les commandes du produit
        $orders = $this->commandeRepository->createQueryBuilder('c')
            ->select('c.date_commande, c.quantite')
            ->join('c.produit', 'p')
            ->where('p.id = :productId')
            ->andWhere('c.date_commande >= :cutoff')
            ->setParameter('productId', $product->getId())
            ->setParameter('cutoff', $cutoffDate)
            ->orderBy('c.date_commande', 'ASC')
            ->getQuery()
            ->getResult();

        // Convertir en array jour => quantité
        $dailySales = [];
        foreach ($orders as $order) {
            $dateStr = $order['date_commande']->format('Y-m-d');
            if (!isset($dailySales[$dateStr])) {
                $dailySales[$dateStr] = 0;
            }
            $dailySales[$dateStr] += (int) $order['quantite'];
        }

        // Remplir les jours manquants avec 0
        $allDays = [];
        $currentDate = $cutoffDate;
        $endDate = new \DateTimeImmutable();

        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $allDays[$dateStr] = $dailySales[$dateStr] ?? 0;
            $currentDate = $currentDate->modify('+1 day');
        }

        return $allDays;
    }

    /**
     * Calcule un score de confiance (0-1)
     */
    private function calculateConfidence(int $dataPoints, array $values): float
    {
        // Base: nombre de points de données
        $pointsConfidence = min($dataPoints / 30, 1.0);

        // Bonus: si les valeurs ne varient pas trop
        if (count($values) > 1) {
            $mean = array_sum($values) / count($values);
            $variance = array_sum(array_map(function($val) use ($mean) {
                return pow($val - $mean, 2);
            }, $values)) / count($values);

            $stdDev = sqrt($variance);
            $cv = $mean > 0 ? $stdDev / $mean : 1.0; // Coefficient de variation

            // Moins de variation = plus de confiance
            $varianceConfidence = max(0, 1.0 - ($cv / 2));
        } else {
            $varianceConfidence = 0.5;
        }

        return ($pointsConfidence * 0.6) + ($varianceConfidence * 0.4);
    }

    /**
     * Obtient toutes les prédictions critiques (risque rupture dans 30 jours)
     */
    public function getCriticalPredictions(int $daysThreshold = 30): array
    {
        return $this->predictionRepository->findCriticalProducts($daysThreshold);
    }
}
