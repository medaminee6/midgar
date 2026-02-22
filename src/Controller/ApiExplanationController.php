<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\AiExplanationService;
use App\Service\DiscoverService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class ApiExplanationController extends AbstractController
{
    #[Route('/api/explanation/{type}/{id}', name: 'api_explanation', methods: ['GET'])]
    public function explain(
        string $type,
        int $id,
        DiscoverService $discoverService,
        AiExplanationService $aiExplanationService
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $normalizedType = $type === 'univers' ? 'universe' : mb_strtolower($type);
        $rankedPosts = $discoverService->getRankedPostsForUser($user);

        $selectedPost = null;
        foreach ($rankedPosts as $post) {
            if ((string) ($post['type'] ?? '') === $normalizedType && (int) ($post['id'] ?? 0) === $id) {
                $selectedPost = $post;
                break;
            }
        }

        if ($selectedPost === null) {
            return new JsonResponse(['error' => 'Post not found in ranked results'], 404);
        }

        $postTag = (string) ($selectedPost['tag'] ?? '');
        $scoreBreakdown = is_array($selectedPost['score_breakdown'] ?? null) ? $selectedPost['score_breakdown'] : [];

        $explanation = $aiExplanationService->generateExplanation($user, $postTag, $scoreBreakdown);

        return new JsonResponse([
            'explanation' => $explanation,
        ]);
    }
}
