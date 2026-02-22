<?php

namespace App\Service;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

class AnalyticsService
{
    private Connection $connection;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->connection = $entityManager->getConnection();
    }

    public function getRecommendedTagDistribution(): array
    {
        $rows = $this->connection->fetchAllAssociative(
            "SELECT tag, COUNT(*) AS count
             FROM (
                SELECT tag FROM oeuvres WHERE tag IS NOT NULL AND tag <> ''
                UNION ALL
                SELECT tag FROM artefacts WHERE tag IS NOT NULL AND tag <> ''
                UNION ALL
                SELECT tag FROM personnage WHERE tag IS NOT NULL AND tag <> ''
                UNION ALL
                SELECT tag FROM universe WHERE tag IS NOT NULL AND tag <> ''
             ) tags
             GROUP BY tag
             ORDER BY count DESC"
        );

        $data = [['Tag', 'Count']];
        foreach ($rows as $row) {
            $data[] = [(string) $row['tag'], (int) $row['count']];
        }

        return $data;
    }

    public function getEngagementPerTag(): array
    {
        $rows = $this->connection->fetchAllAssociative(
            "SELECT tag, SUM(score) AS engagement
             FROM (
                SELECT o.tag AS tag, COUNT(f.id) AS score
                FROM oeuvres o
                LEFT JOIN favoris f ON f.oeuvre_id = o.id
                WHERE o.tag IS NOT NULL AND o.tag <> ''
                GROUP BY o.tag

                UNION ALL

                SELECT a.tag AS tag, COUNT(f.id) AS score
                FROM artefacts a
                LEFT JOIN favoris f ON f.artefact_id = a.id
                WHERE a.tag IS NOT NULL AND a.tag <> ''
                GROUP BY a.tag

                UNION ALL

                SELECT o.tag AS tag, COUNT(c.id) * 2 AS score
                FROM oeuvres o
                LEFT JOIN commentaires c ON c.oeuvre_id = o.id
                WHERE o.tag IS NOT NULL AND o.tag <> ''
                GROUP BY o.tag

                UNION ALL

                SELECT a.tag AS tag, COUNT(c.id) * 2 AS score
                FROM artefacts a
                LEFT JOIN commentaires c ON c.artefact_id = a.id
                WHERE a.tag IS NOT NULL AND a.tag <> ''
                GROUP BY a.tag
             ) engagement_parts
             GROUP BY tag
             ORDER BY engagement DESC"
        );

        $data = [['Tag', 'Engagement']];
        foreach ($rows as $row) {
            $data[] = [(string) $row['tag'], (int) $row['engagement']];
        }

        return $data;
    }

    public function getUserActivityTrend(): array
    {
        $startDate = new \DateTimeImmutable('today -6 days');

        $rows = $this->connection->fetchAllAssociative(
            "SELECT day, SUM(total) AS interactions
             FROM (
                SELECT DATE(created_at) AS day, COUNT(*) AS total
                FROM favoris
                WHERE created_at >= :startDate
                GROUP BY DATE(created_at)

                UNION ALL

                SELECT DATE(created_at) AS day, COUNT(*) AS total
                FROM commentaires
                WHERE created_at >= :startDate
                GROUP BY DATE(created_at)
             ) activity
             GROUP BY day
             ORDER BY day ASC",
            ['startDate' => $startDate->format('Y-m-d 00:00:00')]
        );

        $indexed = [];
        foreach ($rows as $row) {
            $indexed[(string) $row['day']] = (int) $row['interactions'];
        }

        $data = [['Date', 'Interactions']];
        for ($i = 0; $i < 7; ++$i) {
            $day = $startDate->modify('+' . $i . ' days')->format('Y-m-d');
            $data[] = [$day, $indexed[$day] ?? 0];
        }

        return $data;
    }
}
