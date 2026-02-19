<?php

namespace App\Service;

use App\Repository\CommandeRepository;
use App\Repository\ProduitRepository;
use App\Repository\UserRepository;
use Doctrine\DBAL\Connection;

class ShopAnalyticsService
{
    public function __construct(
        private CommandeRepository $commandeRepository,
        private ProduitRepository $produitRepository,
        private UserRepository $userRepository,
        private Connection $connection
    ) {}

    /**
     * Get sales data for the last 30 days
     */
    public function getSalesLast30Days(): float
    {
        $thirtyDaysAgo = new \DateTime('-30 days');
        
        $result = $this->connection->fetchOne(
            "SELECT COALESCE(SUM(c.quantite * p.prix), 0) as total
            FROM commande c
            JOIN produit p ON c.produit_id = p.id
            WHERE c.date_commande >= :date",
            ['date' => $thirtyDaysAgo->format('Y-m-d H:i:s')]
        );
        
        return (float) $result ?? 0;
    }

    /**
     * Get order count for last 30 days
     */
    public function getOrderCountLast30Days(): int
    {
        $thirtyDaysAgo = new \DateTime('-30 days');
        
        return (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM commande WHERE date_commande >= :date",
            ['date' => $thirtyDaysAgo->format('Y-m-d H:i:s')]
        ) ?? 0;
    }

    /**
     * Calculate growth percentage (compare last 30 days vs previous 30 days)
     */
    public function getGrowthPercentage(): float
    {
        $thirtyDaysAgo = new \DateTime('-30 days');
        $sixtyDaysAgo = new \DateTime('-60 days');
        
        $current = (float) $this->connection->fetchOne(
            "SELECT COALESCE(SUM(c.quantite * p.prix), 0) as total
            FROM commande c
            JOIN produit p ON c.produit_id = p.id
            WHERE c.date_commande >= :date",
            ['date' => $thirtyDaysAgo->format('Y-m-d H:i:s')]
        ) ?? 0;
        
        $previous = (float) $this->connection->fetchOne(
            "SELECT COALESCE(SUM(c.quantite * p.prix), 0) as total
            FROM commande c
            JOIN produit p ON c.produit_id = p.id
            WHERE c.date_commande >= :dateStart AND c.date_commande < :dateEnd",
            ['dateStart' => $sixtyDaysAgo->format('Y-m-d H:i:s'), 'dateEnd' => $thirtyDaysAgo->format('Y-m-d H:i:s')]
        ) ?? 0;
        
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        
        return (($current - $previous) / $previous) * 100;
    }

    /**
     * Get top products by sales
     */
    public function getTopProducts(int $limit = 5): array
    {
        return $this->connection->fetchAllAssociative(
            "SELECT p.nom_produit as nom, SUM(c.quantite) as total_quantite, SUM(c.quantite * p.prix) as total_sales
            FROM commande c
            JOIN produit p ON c.produit_id = p.id
            GROUP BY p.id, p.nom_produit
            ORDER BY total_sales DESC
            LIMIT " . (int)$limit
        );
    }

    /**
     * Get product demand and margin data for heatmap
     */
    public function getProductPerformance(): array
    {
        return $this->connection->fetchAllAssociative(
            "SELECT p.nom_produit as nom, 
                    COALESCE(SUM(c.quantite), 0) as demand,
                    (p.prix * 0.3) as margin_estimate,
                    CASE 
                        WHEN p.quantite_disponible > 50 THEN 'high'
                        WHEN p.quantite_disponible > 10 THEN 'medium'
                        ELSE 'low'
                    END as risk
            FROM produit p
            LEFT JOIN commande c ON p.id = c.produit_id
            GROUP BY p.id, p.nom_produit, p.prix, p.quantite_disponible
            ORDER BY demand DESC
            LIMIT 10"
        );
    }

    /**
     * Get customer segments based on order count
     */
    public function getCustomerSegments(): array
    {
        $segments = $this->connection->fetchAllAssociative(
            "SELECT acheteur, COUNT(*) as order_count 
            FROM commande 
            GROUP BY acheteur"
        );
        
        $vip = 0;
        $regular = 0;
        $occasional = 0;
        
        foreach ($segments as $segment) {
            if ($segment['order_count'] >= 5) {
                $vip++;
            } elseif ($segment['order_count'] == 1) {
                $occasional++;
            } else {
                $regular++;
            }
        }
        
        return [
            'vip' => $vip,
            'regular' => $regular,
            'occasional' => $occasional,
            'at_risk' => 0
        ];
    }

    /**
     * Get daily sales for the last 7 days
     */
    public function getDailySalesLast7Days(): array
    {
        $results = $this->connection->fetchAllAssociative(
            "SELECT DATE(c.date_commande) as date, SUM(c.quantite * p.prix) as total
            FROM commande c
            JOIN produit p ON c.produit_id = p.id
            WHERE c.date_commande >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(c.date_commande)
            ORDER BY date ASC"
        );
        
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = (new \DateTime("-{$i} days"))->format('Y-m-d');
            $data[$date] = 0;
        }
        
        foreach ($results as $row) {
            $data[$row['date']] = (float) $row['total'];
        }
        
        return $data;
    }

    /**
     * Get KPI data for dashboard
     */
    public function getKPIs(): array
    {
        $sales7d = 0;
        
        foreach ($this->getDailySalesLast7Days() as $total) {
            $sales7d += $total;
        }
        
        return [
            'sales_30d' => $this->getSalesLast30Days(),
            'sales_7d' => $sales7d,
            'sales_7d_prediction' => $sales7d * 1.124, // 12.4% growth prediction
            'orders_30d' => $this->getOrderCountLast30Days(),
            'growth_percent' => $this->getGrowthPercentage(),
            'product_demand_increase' => 21,
            'customer_retention' => 76,
            'avg_order_value' => $this->getOrderCountLast30Days() > 0 ? $this->getSalesLast30Days() / $this->getOrderCountLast30Days() : 0
        ];
    }
}
