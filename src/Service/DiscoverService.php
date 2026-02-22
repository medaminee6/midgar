<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserPreferenceRepository;
use Doctrine\ORM\EntityManagerInterface;

class DiscoverService
{
    private const WEIGHT_TAG = 0.40;
    private const WEIGHT_ENGAGEMENT = 0.25;
    private const WEIGHT_RECENCY = 0.20;
    private const WEIGHT_BEHAVIOR = 0.15;
    private const NEUTRAL_UNSUPPORTED_COMPONENT_SCORE = 0.5;

    public function __construct(
        private readonly UserPreferenceRepository $userPreferenceRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getRankedPostsForUser(User $user, ?int $limit = null, bool $debug = false): array
    {
        $posts = $this->loadAllPosts();
        if ($posts === []) {
            return [];
        }

        $userTagFrequencyMap = $this->buildUserTagFrequencyMap((int) $user->getId());
        $maxUserTagFrequency = $this->maxFrequency($userTagFrequencyMap);

        [$engagementMap, $maxEngagementRaw, $engagementSupportedTypes] = $this->loadEngagementStats();

        [$behaviorTagFrequencyMap, $behaviorSupportedTypes] = $this->loadBehaviorTagFrequencyMap((int) $user->getId());
        $maxBehaviorTagFrequency = $this->maxFrequency($behaviorTagFrequencyMap);

        $now = new \DateTimeImmutable();
        $recencyRawByKey = [];
        foreach ($posts as $post) {
            $postKey = $this->postKey($post['type'], $post['id']);
            $recencyRawByKey[$postKey] = $this->calculateRawRecency($post['createdAt'], $now);
        }

        $minRecencyRaw = $recencyRawByKey === [] ? 0.0 : (float) min($recencyRawByKey);
        $maxRecencyRaw = $recencyRawByKey === [] ? 1.0 : (float) max($recencyRawByKey);

        $ranked = [];

        foreach ($posts as $post) {
            $postTag = (string) $post['tag'];
            $type = (string) $post['type'];
            $id = (int) $post['id'];

            $tagSimilarity = $this->calculateTagSimilarity($postTag, $userTagFrequencyMap, $maxUserTagFrequency);
            $engagementScore = $this->calculateEngagementScore(
                $type,
                $id,
                $engagementMap,
                $maxEngagementRaw,
                $engagementSupportedTypes
            );
            $recencyDecay = $this->calculateRecencyDecay($post['createdAt'], $now, $minRecencyRaw, $maxRecencyRaw);
            $behaviorSimilarity = $this->calculateBehaviorSimilarity(
                $postTag,
                $type,
                $behaviorTagFrequencyMap,
                $maxBehaviorTagFrequency,
                $behaviorSupportedTypes
            );

            $finalScore =
                (self::WEIGHT_TAG * $tagSimilarity) +
                (self::WEIGHT_ENGAGEMENT * $engagementScore) +
                (self::WEIGHT_RECENCY * $recencyDecay) +
                (self::WEIGHT_BEHAVIOR * $behaviorSimilarity);

            $isRecent = (($now->getTimestamp() - $post['createdAt']->getTimestamp()) < 172800);
            if ($isRecent) {
                $finalScore = max($finalScore, 0.25);
            }

            if ($recencyDecay > 0.7) {
                $finalScore += 0.1;
            }

            if ($finalScore <= 0.2) {
                continue;
            }

            if (
                $tagSimilarity == 0.0 &&
                $behaviorSimilarity == 0.0 &&
                $engagementScore == 0.0 &&
                !$isRecent
            ) {
                continue;
            }

            $ranked[] = [
                'type' => $type,
                'id' => $id,
                'tag' => $postTag,
                'score' => round($this->clamp01($finalScore), 6),
                'score_breakdown' => [
                    'tag_similarity' => round($tagSimilarity, 6),
                    'engagement_score' => round($engagementScore, 6),
                    'recency_decay' => round($recencyDecay, 6),
                    'behavior_similarity' => round($behaviorSimilarity, 6),
                ],
            ];
        }

        if ($debug) {
            // DEBUG ONLY: remove after debugging
            $normalizedComparisons = [];
            foreach ($posts as $post) {
                $normalizedComparisons[] = [
                    'type' => $post['type'],
                    'id' => $post['id'],
                    'post_tag_normalized' => $post['tag'],
                    'matches_user_tag' => isset($userTagFrequencyMap[(string) $post['tag']]),
                ];
            }

            dd([
                'user_id' => $user->getId(),
                'userTagFrequencyMap' => $userTagFrequencyMap,
                'posts_loaded' => $posts,
                'normalized_tags_comparison' => $normalizedComparisons,
                'final_ranked_array_before_sort' => $ranked,
            ]);
        }

        usort($ranked, static fn (array $a, array $b) => $b['score'] <=> $a['score']);

        if ($limit !== null && $limit > 0) {
            return array_slice($ranked, 0, $limit);
        }

        return $ranked;
    }

    /**
     * @return array<int, array{type:string,id:int,tag:string,createdAt:\DateTimeInterface}>
     */
    private function loadAllPosts(): array
    {
        $connection = $this->entityManager->getConnection();
        $posts = [];

        $oeuvres = $connection->fetchAllAssociative('SELECT id, tag, created_at FROM oeuvres');
        foreach ($oeuvres as $row) {
            $posts[] = [
                'type' => 'oeuvre',
                'id' => (int) ($row['id'] ?? 0),
                'tag' => $this->normalizeTag((string) ($row['tag'] ?? '')),
                'createdAt' => $this->dateFromDb($row['created_at'] ?? null),
            ];
        }

        $artefacts = $connection->fetchAllAssociative('SELECT id, tag, created_at FROM artefacts');
        foreach ($artefacts as $row) {
            $posts[] = [
                'type' => 'artefact',
                'id' => (int) ($row['id'] ?? 0),
                'tag' => $this->normalizeTag((string) ($row['tag'] ?? '')),
                'createdAt' => $this->dateFromDb($row['created_at'] ?? null),
            ];
        }

        $personnages = $connection->fetchAllAssociative('SELECT id, tag, created_at FROM personnage');
        foreach ($personnages as $row) {
            $posts[] = [
                'type' => 'personnage',
                'id' => (int) ($row['id'] ?? 0),
                'tag' => $this->normalizeTag((string) ($row['tag'] ?? '')),
                'createdAt' => $this->dateFromDb($row['created_at'] ?? null),
            ];
        }

        $universes = $connection->fetchAllAssociative('SELECT id, tag, created_at FROM universe');
        foreach ($universes as $row) {
            $posts[] = [
                'type' => 'universe',
                'id' => (int) ($row['id'] ?? 0),
                'tag' => $this->normalizeTag((string) ($row['tag'] ?? '')),
                'createdAt' => $this->dateFromDb($row['created_at'] ?? null),
            ];
        }

        return $posts;
    }

    /**
     * @return array<string,int>
     */
    private function buildUserTagFrequencyMap(int $userId): array
    {
        $preferences = $this->userPreferenceRepository->findByUser($userId);

        $frequency = [];
        foreach ($preferences as $preference) {
            $tags = $this->splitTags((string) $preference->getTags());
            foreach ($tags as $tag) {
                $normalized = $this->normalizeTag($tag);
                if ($normalized === '') {
                    continue;
                }
                $frequency[$normalized] = ($frequency[$normalized] ?? 0) + 1;
            }
        }

        return $frequency;
    }

    /**
     * @return array{0:array<string,float>,1:float,2:array<string,bool>}
     */
    private function loadEngagementStats(): array
    {
        $connection = $this->entityManager->getConnection();

        $favorisColumns = $this->getTableColumns('favoris');
        $commentairesColumns = $this->getTableColumns('commentaires');

        $supportedTypes = [
            'oeuvre' => isset($favorisColumns['oeuvre_id']) || isset($commentairesColumns['oeuvre_id']),
            'artefact' => isset($favorisColumns['artefact_id']) || isset($commentairesColumns['artefact_id']),
            'personnage' => isset($favorisColumns['personnage_id']) || isset($commentairesColumns['personnage_id']),
            'universe' => isset($favorisColumns['universe_id']) || isset($commentairesColumns['universe_id']),
        ];

        $engagementRaw = [];

        if (isset($favorisColumns['oeuvre_id'])) {
            $oeuvreLikes = $connection->fetchAllAssociative(
                'SELECT oeuvre_id AS id, COUNT(id) AS c FROM favoris WHERE oeuvre_id IS NOT NULL GROUP BY oeuvre_id'
            );
            foreach ($oeuvreLikes as $row) {
                $key = $this->postKey('oeuvre', (int) $row['id']);
                $engagementRaw[$key] = ($engagementRaw[$key] ?? 0.0) + (float) $row['c'];
            }
        }

        if (isset($favorisColumns['artefact_id'])) {
            $artefactLikes = $connection->fetchAllAssociative(
                'SELECT artefact_id AS id, COUNT(id) AS c FROM favoris WHERE artefact_id IS NOT NULL GROUP BY artefact_id'
            );
            foreach ($artefactLikes as $row) {
                $key = $this->postKey('artefact', (int) $row['id']);
                $engagementRaw[$key] = ($engagementRaw[$key] ?? 0.0) + (float) $row['c'];
            }
        }

        if (isset($commentairesColumns['oeuvre_id'])) {
            $oeuvreComments = $connection->fetchAllAssociative(
                'SELECT oeuvre_id AS id, COUNT(id) AS c FROM commentaires WHERE oeuvre_id IS NOT NULL GROUP BY oeuvre_id'
            );
            foreach ($oeuvreComments as $row) {
                $key = $this->postKey('oeuvre', (int) $row['id']);
                $engagementRaw[$key] = ($engagementRaw[$key] ?? 0.0) + (2.0 * (float) $row['c']);
            }
        }

        if (isset($commentairesColumns['artefact_id'])) {
            $artefactComments = $connection->fetchAllAssociative(
                'SELECT artefact_id AS id, COUNT(id) AS c FROM commentaires WHERE artefact_id IS NOT NULL GROUP BY artefact_id'
            );
            foreach ($artefactComments as $row) {
                $key = $this->postKey('artefact', (int) $row['id']);
                $engagementRaw[$key] = ($engagementRaw[$key] ?? 0.0) + (2.0 * (float) $row['c']);
            }
        }

        if (isset($favorisColumns['personnage_id'])) {
            $personnageLikes = $connection->fetchAllAssociative(
                'SELECT personnage_id AS id, COUNT(id) AS c FROM favoris WHERE personnage_id IS NOT NULL GROUP BY personnage_id'
            );
            foreach ($personnageLikes as $row) {
                $key = $this->postKey('personnage', (int) $row['id']);
                $engagementRaw[$key] = ($engagementRaw[$key] ?? 0.0) + (float) $row['c'];
            }
        }

        if (isset($commentairesColumns['personnage_id'])) {
            $personnageComments = $connection->fetchAllAssociative(
                'SELECT personnage_id AS id, COUNT(id) AS c FROM commentaires WHERE personnage_id IS NOT NULL GROUP BY personnage_id'
            );
            foreach ($personnageComments as $row) {
                $key = $this->postKey('personnage', (int) $row['id']);
                $engagementRaw[$key] = ($engagementRaw[$key] ?? 0.0) + (2.0 * (float) $row['c']);
            }
        }

        if (isset($favorisColumns['universe_id'])) {
            $universeLikes = $connection->fetchAllAssociative(
                'SELECT universe_id AS id, COUNT(id) AS c FROM favoris WHERE universe_id IS NOT NULL GROUP BY universe_id'
            );
            foreach ($universeLikes as $row) {
                $key = $this->postKey('universe', (int) $row['id']);
                $engagementRaw[$key] = ($engagementRaw[$key] ?? 0.0) + (float) $row['c'];
            }
        }

        if (isset($commentairesColumns['universe_id'])) {
            $universeComments = $connection->fetchAllAssociative(
                'SELECT universe_id AS id, COUNT(id) AS c FROM commentaires WHERE universe_id IS NOT NULL GROUP BY universe_id'
            );
            foreach ($universeComments as $row) {
                $key = $this->postKey('universe', (int) $row['id']);
                $engagementRaw[$key] = ($engagementRaw[$key] ?? 0.0) + (2.0 * (float) $row['c']);
            }
        }

        $maxRaw = $engagementRaw === [] ? 0.0 : (float) max($engagementRaw);

        return [$engagementRaw, $maxRaw, $supportedTypes];
    }

    /**
     * @return array{0:array<string,int>,1:array<string,bool>}
     */
    private function loadBehaviorTagFrequencyMap(int $userId): array
    {
        $connection = $this->entityManager->getConnection();
        $favorisColumns = $this->getTableColumns('favoris');

        $supportedTypes = [
            'oeuvre' => isset($favorisColumns['oeuvre_id']),
            'artefact' => isset($favorisColumns['artefact_id']),
            'personnage' => isset($favorisColumns['personnage_id']),
            'universe' => isset($favorisColumns['universe_id']),
        ];

        $tagFrequency = [];

        if (isset($favorisColumns['oeuvre_id'])) {
            $oeuvreRows = $connection->fetchAllAssociative(
                'SELECT o.tag AS tag, COUNT(f.id) AS c
                 FROM favoris f
                 INNER JOIN oeuvres o ON o.id = f.oeuvre_id
                 WHERE f.user_id = :userId AND f.oeuvre_id IS NOT NULL
                 GROUP BY o.tag',
                ['userId' => $userId]
            );

            foreach ($oeuvreRows as $row) {
                $normalized = $this->normalizeTag((string) ($row['tag'] ?? ''));
                if ($normalized === '') {
                    continue;
                }
                $tagFrequency[$normalized] = ($tagFrequency[$normalized] ?? 0) + (int) $row['c'];
            }
        }

        if (isset($favorisColumns['artefact_id'])) {
            $artefactRows = $connection->fetchAllAssociative(
                'SELECT a.tag AS tag, COUNT(f.id) AS c
                 FROM favoris f
                 INNER JOIN artefacts a ON a.id = f.artefact_id
                 WHERE f.user_id = :userId AND f.artefact_id IS NOT NULL
                 GROUP BY a.tag',
                ['userId' => $userId]
            );

            foreach ($artefactRows as $row) {
                $normalized = $this->normalizeTag((string) ($row['tag'] ?? ''));
                if ($normalized === '') {
                    continue;
                }
                $tagFrequency[$normalized] = ($tagFrequency[$normalized] ?? 0) + (int) $row['c'];
            }
        }

        if (isset($favorisColumns['personnage_id'])) {
            $personnageRows = $connection->fetchAllAssociative(
                'SELECT p.tag AS tag, COUNT(f.id) AS c
                 FROM favoris f
                 INNER JOIN personnage p ON p.id = f.personnage_id
                 WHERE f.user_id = :userId AND f.personnage_id IS NOT NULL
                 GROUP BY p.tag',
                ['userId' => $userId]
            );

            foreach ($personnageRows as $row) {
                $normalized = $this->normalizeTag((string) ($row['tag'] ?? ''));
                if ($normalized === '') {
                    continue;
                }
                $tagFrequency[$normalized] = ($tagFrequency[$normalized] ?? 0) + (int) $row['c'];
            }
        }

        if (isset($favorisColumns['universe_id'])) {
            $universeRows = $connection->fetchAllAssociative(
                'SELECT u.tag AS tag, COUNT(f.id) AS c
                 FROM favoris f
                 INNER JOIN universe u ON u.id = f.universe_id
                 WHERE f.user_id = :userId AND f.universe_id IS NOT NULL
                 GROUP BY u.tag',
                ['userId' => $userId]
            );

            foreach ($universeRows as $row) {
                $normalized = $this->normalizeTag((string) ($row['tag'] ?? ''));
                if ($normalized === '') {
                    continue;
                }
                $tagFrequency[$normalized] = ($tagFrequency[$normalized] ?? 0) + (int) $row['c'];
            }
        }

        return [$tagFrequency, $supportedTypes];
    }

    private function calculateTagSimilarity(string $postTag, array $userTagFrequencyMap, int $maxTagFrequency): float
    {
        $normalizedTag = $this->normalizeTag($postTag);
        if ($normalizedTag === '' || $maxTagFrequency <= 0) {
            return 0.0;
        }

        $frequency = (int) ($userTagFrequencyMap[$normalizedTag] ?? 0);

        return $this->clamp01($frequency / $maxTagFrequency);
    }

    private function calculateEngagementScore(
        string $type,
        int $id,
        array $engagementMap,
        float $maxEngagementRaw,
        array $supportedTypes
    ): float
    {
        if (!(bool) ($supportedTypes[$type] ?? false)) {
            return self::NEUTRAL_UNSUPPORTED_COMPONENT_SCORE;
        }

        if ($maxEngagementRaw <= 0.0) {
            return 0.0;
        }

        $raw = (float) ($engagementMap[$this->postKey($type, $id)] ?? 0.0);

        return $this->clamp01($raw / $maxEngagementRaw);
    }

    private function calculateRecencyDecay(
        \DateTimeInterface $createdAt,
        \DateTimeImmutable $now,
        float $minRaw,
        float $maxRaw
    ): float {
        $raw = $this->calculateRawRecency($createdAt, $now);

        if ($maxRaw <= $minRaw) {
            return 1.0;
        }

        return $this->clamp01(($raw - $minRaw) / ($maxRaw - $minRaw));
    }

    private function calculateBehaviorSimilarity(
        string $postTag,
        string $type,
        array $behaviorTagFrequencyMap,
        int $maxBehaviorTagFrequency,
        array $supportedTypes
    ): float
    {
        if (!(bool) ($supportedTypes[$type] ?? false)) {
            return self::NEUTRAL_UNSUPPORTED_COMPONENT_SCORE;
        }

        $normalizedTag = $this->normalizeTag($postTag);
        if ($normalizedTag === '' || $maxBehaviorTagFrequency <= 0) {
            return 0.0;
        }

        $frequency = (int) ($behaviorTagFrequencyMap[$normalizedTag] ?? 0);

        return $this->clamp01($frequency / $maxBehaviorTagFrequency);
    }

    private function calculateRawRecency(\DateTimeInterface $createdAt, \DateTimeImmutable $now): float
    {
        $ageInSeconds = max(0, $now->getTimestamp() - $createdAt->getTimestamp());
        $ageInDays = $ageInSeconds / 86400;

        $halfLifeDays = 30.0;

        return exp(-$ageInDays / $halfLifeDays);
    }

    /**
     * @return string[]
     */
    private function splitTags(string $tags): array
    {
        if (trim($tags) === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $tags)), static fn (string $tag) => $tag !== ''));
    }

    private function normalizeTag(string $tag): string
    {
        $normalized = mb_strtolower(trim($tag));

        if ($normalized === '') {
            return '';
        }

        if (!str_starts_with($normalized, '#')) {
            $normalized = '#' . $normalized;
        }

        return $normalized;
    }

    private function postKey(string $type, int $id): string
    {
        return $type . ':' . $id;
    }

    /**
     * @param array<string,int> $frequencyMap
     */
    private function maxFrequency(array $frequencyMap): int
    {
        if ($frequencyMap === []) {
            return 0;
        }

        return (int) max($frequencyMap);
    }

    private function clamp01(float $value): float
    {
        if ($value < 0.0) {
            return 0.0;
        }

        if ($value > 1.0) {
            return 1.0;
        }

        return $value;
    }

    /**
     * @return array<string,bool>
     */
    private function getTableColumns(string $tableName): array
    {
        $schemaManager = $this->entityManager->getConnection()->createSchemaManager();
        $columns = [];

        foreach ($schemaManager->listTableColumns($tableName) as $column) {
            $columns[strtolower($column->getName())] = true;
        }

        return $columns;
    }

    private function dateFromDb(mixed $value): \DateTimeInterface
    {
        if ($value instanceof \DateTimeInterface) {
            return $value;
        }

        if (is_string($value) && trim($value) !== '') {
            try {
                return new \DateTimeImmutable($value);
            } catch (\Throwable) {
                return new \DateTimeImmutable('1970-01-01');
            }
        }

        return new \DateTimeImmutable('1970-01-01');
    }
}
