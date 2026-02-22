<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserPreferenceRepository;
use App\Service\AiExplanationService;
use App\Service\DiscoverService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class WhyController extends AbstractController
{
    public function __construct(
        private readonly DiscoverService $discoverService,
        private readonly AiExplanationService $aiExplanationService,
        private readonly UserPreferenceRepository $userPreferenceRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/why/{type}/{id}', name: 'discover_why', methods: ['GET'])]
    public function why(string $type, int $id): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $normalizedType = $this->normalizeType($type);
        $rankedPosts = $this->discoverService->getRankedPostsForUser($user);

        $selectedPost = null;
        foreach ($rankedPosts as $post) {
            if ((string) ($post['type'] ?? '') === $normalizedType && (int) ($post['id'] ?? 0) === $id) {
                $selectedPost = $post;
                break;
            }
        }

        if ($selectedPost === null) {
            throw $this->createNotFoundException('Ce contenu recommandé est introuvable pour votre profil actuel.');
        }

        $scoreBreakdown = is_array($selectedPost['score_breakdown'] ?? null) ? $selectedPost['score_breakdown'] : [];

        $topPreferenceTags = $this->extractTopUserPreferenceTags($user);
        $topBehaviorTags = $this->extractTopUserBehaviorTags($user);

        $aiExplanation = $this->aiExplanationService->generateExplanation(
            $user,
            (string) ($selectedPost['tag'] ?? ''),
            $scoreBreakdown,
            'long'
        );

        $postDetails = $this->resolvePostDetails($normalizedType, $id);

        return $this->render('discover/why.html.twig', [
            'post' => $selectedPost,
            'postDetails' => $postDetails,
            'aiExplanation' => $aiExplanation,
            'topPreferenceTags' => $topPreferenceTags,
            'topBehaviorTags' => $topBehaviorTags,
            'aiEnabled' => filter_var($_ENV['AI_EXPLANATION_ENABLED'] ?? false, FILTER_VALIDATE_BOOL),
        ]);
    }

    /**
     * @return string[]
     */
    private function extractTopUserPreferenceTags(User $user): array
    {
        $preferences = $this->userPreferenceRepository->findByUser((int) $user->getId());
        $counts = [];

        foreach ($preferences as $preference) {
            foreach ($this->splitTags((string) $preference->getTags()) as $tag) {
                $normalizedTag = $this->normalizeTag($tag);
                if ($normalizedTag === '') {
                    continue;
                }
                $counts[$normalizedTag] = ($counts[$normalizedTag] ?? 0) + 1;
            }
        }

        arsort($counts);

        return array_slice(array_keys($counts), 0, 5);
    }

    /**
     * @return string[]
     */
    private function extractTopUserBehaviorTags(User $user): array
    {
        $connection = $this->entityManager->getConnection();
        $userId = (int) $user->getId();

        $counts = [];

        $oeuvreRows = $connection->fetchAllAssociative(
            'SELECT o.tag AS tag, COUNT(f.id) AS c
             FROM favoris f
             INNER JOIN oeuvres o ON o.id = f.oeuvre_id
             WHERE f.user_id = :userId AND f.oeuvre_id IS NOT NULL
             GROUP BY o.tag',
            ['userId' => $userId]
        );

        foreach ($oeuvreRows as $row) {
            $normalizedTag = $this->normalizeTag((string) ($row['tag'] ?? ''));
            if ($normalizedTag === '') {
                continue;
            }
            $counts[$normalizedTag] = ($counts[$normalizedTag] ?? 0) + (int) ($row['c'] ?? 0);
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
            $normalizedTag = $this->normalizeTag((string) ($row['tag'] ?? ''));
            if ($normalizedTag === '') {
                continue;
            }
            $counts[$normalizedTag] = ($counts[$normalizedTag] ?? 0) + (int) ($row['c'] ?? 0);
        }

        arsort($counts);

        return array_slice(array_keys($counts), 0, 5);
    }

    /**
     * @return array{title:string,description:string,typeLabel:string}
     */
    private function resolvePostDetails(string $type, int $id): array
    {
        $connection = $this->entityManager->getConnection();

        if ($type === 'oeuvre') {
            $row = $connection->fetchAssociative('SELECT title, description FROM oeuvres WHERE id = :id', ['id' => $id]) ?: [];
            return [
                'title' => (string) ($row['title'] ?? ('Œuvre #' . $id)),
                'description' => (string) ($row['description'] ?? ''),
                'typeLabel' => 'Œuvre',
            ];
        }

        if ($type === 'artefact') {
            $row = $connection->fetchAssociative('SELECT name, origins FROM artefacts WHERE id = :id', ['id' => $id]) ?: [];
            return [
                'title' => (string) ($row['name'] ?? ('Artefact #' . $id)),
                'description' => (string) ($row['origins'] ?? ''),
                'typeLabel' => 'Artefact',
            ];
        }

        if ($type === 'personnage') {
            $row = $connection->fetchAssociative('SELECT name, history_context FROM personnage WHERE id = :id', ['id' => $id]) ?: [];
            return [
                'title' => (string) ($row['name'] ?? ('Personnage #' . $id)),
                'description' => (string) ($row['history_context'] ?? ''),
                'typeLabel' => 'Personnage',
            ];
        }

        $row = $connection->fetchAssociative('SELECT name, short_description FROM universe WHERE id = :id', ['id' => $id]) ?: [];
        return [
            'title' => (string) ($row['name'] ?? ('Univers #' . $id)),
            'description' => (string) ($row['short_description'] ?? ''),
            'typeLabel' => 'Univers',
        ];
    }

    private function normalizeType(string $type): string
    {
        $value = mb_strtolower(trim($type));
        if ($value === 'univers') {
            return 'universe';
        }

        return $value;
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
}
