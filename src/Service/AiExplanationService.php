<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserPreferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AiExplanationService
{
    private const FALLBACK_EXPLANATION = 'FALLBACK_TRIGGERED';
    private const GROQ_ENDPOINT = 'https://api.groq.com/openai/v1/chat/completions';
    private const GROQ_MODEL = 'llama-3.1-8b-instant';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly UserPreferenceRepository $userPreferenceRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly bool $aiEnabled,
        private readonly string $groqApiKey,
    ) {
    }

    public function generateExplanation(User $user, string $postTag, array $scoreBreakdown, string $mode = 'short'): string
    {
        if (!$this->aiEnabled) {
            return self::FALLBACK_EXPLANATION;
        }

        if (trim($this->groqApiKey) === '') {
            $this->logger->warning('AI explanation enabled but GROQ_API_KEY is missing.');

            return self::FALLBACK_EXPLANATION;
        }

        $userPreferenceTags = $this->getUserPreferenceTags((int) $user->getId());
        $userBehaviorTags = $this->getUserBehaviorTags((int) $user->getId());

        $prompt = $this->buildPrompt(
            $userPreferenceTags,
            $userBehaviorTags,
            $this->normalizeTag($postTag),
            $scoreBreakdown,
            $mode
        );

        $payload = [
            'model' => 'llama-3.1-8b-instant',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'temperature' => 0.5,
            'max_tokens' => 80,
            'stream' => false,
        ];

        $response = null;
        try {
            $response = $this->httpClient->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->groqApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            $statusCode = $response->getStatusCode();
            $this->logger->info('Groq explanation response received.', [
                'status_code' => $statusCode,
                'model' => self::GROQ_MODEL,
            ]);

            $data = $response->toArray();

            $content = $data['choices'][0]['message']['content'] ?? null;

            return is_string($content) ? trim($content) : '';
        } catch (\Throwable $e) {
            $this->logger->error('Failed to generate AI explanation via Groq.', [
                'exception' => $e,
                'response_body' => $response?->getContent(false),
                'model' => self::GROQ_MODEL,
                'user_id' => $user->getId(),
                'post_tag' => $postTag,
            ]);

            return self::FALLBACK_EXPLANATION;
        }
    }

    private function buildPrompt(array $userPreferenceTags, array $userBehaviorTags, string $postTag, array $scoreBreakdown, string $mode): string
    {
        $tagSimilarity = (float) ($scoreBreakdown['tag_similarity'] ?? 0);
        $behaviorSimilarity = (float) ($scoreBreakdown['behavior_similarity'] ?? 0);
        $engagementScore = (float) ($scoreBreakdown['engagement_score'] ?? 0);
        $recencyDecay = (float) ($scoreBreakdown['recency_decay'] ?? 0);

        $interactionFlag = $behaviorSimilarity > 0.4 ? 'oui' : 'non';
        $preferenceFlag = $tagSimilarity > 0.4 ? 'oui' : 'non';
        $trendFlag = ($recencyDecay > 0.5 || $engagementScore > 0.5) ? 'oui' : 'non';

        return sprintf(
            "Tu es un moteur d’explication de recommandation.\n\nContexte :\n\nInteraction passée avec ce thème : %s\nPrésent dans vos préférences : %s\nContenu récent ou populaire : %s\n\nConsignes strictes :\n\n- Explique pourquoi ce contenu est recommandé.\n- Utilise uniquement les signaux marqués \"oui\".\n- Si INTERACTION_FLAG = non, ne parle jamais d’interaction.\n- Si PREFERENCE_FLAG = non, ne parle jamais de préférence.\n- Si TREND_FLAG = non, ne parle jamais de popularité ou récence.\n- Si TREND_FLAG = oui et INTERACTION_FLAG = non et PREFERENCE_FLAG = non, base l’explication uniquement sur la récence ou la popularité.\n- Dans ce cas, ne mentionne jamais interaction ni préférence.\n- Maximum 3 phrases.\n- Ton professionnel et naturel.\n- Ne mentionne jamais de hashtag.\n- Ne mentionne jamais d’autre thème.\n- Ne rajoute aucune information.\n\nRépond uniquement par l’explication finale.",
            $interactionFlag,
            $preferenceFlag,
            $trendFlag
        );
    }

    /**
     * @return string[]
     */
    private function getUserPreferenceTags(int $userId): array
    {
        $preferences = $this->userPreferenceRepository->findByUser($userId);
        $tags = [];

        foreach ($preferences as $preference) {
            foreach ($this->splitTags((string) $preference->getTags()) as $tag) {
                $normalized = $this->normalizeTag($tag);
                if ($normalized !== '') {
                    $tags[] = $normalized;
                }
            }
        }

        return $tags;
    }

    /**
     * @return string[]
     */
    private function getUserBehaviorTags(int $userId): array
    {
        $connection = $this->entityManager->getConnection();

        $tags = [];

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
            if ($normalized !== '') {
                $tags[] = sprintf('%s(x%d)', $normalized, (int) ($row['c'] ?? 0));
            }
        }

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
            if ($normalized !== '') {
                $tags[] = sprintf('%s(x%d)', $normalized, (int) ($row['c'] ?? 0));
            }
        }

        return $tags;
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

    private function formatTags(array $tags): string
    {
        if ($tags === []) {
            return '(none)';
        }

        return implode(', ', $tags);
    }

    private function normalizeBehaviorTag(string $tag): string
    {
        $baseTag = preg_replace('/\(x\d+\)$/', '', trim($tag));

        return $this->normalizeTag((string) $baseTag);
    }
}
