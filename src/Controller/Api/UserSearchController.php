<?php

namespace App\Controller\Api;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class UserSearchController extends AbstractController
{
    #[Route('/search/users', name: 'user_search', methods: ['GET'])]
    public function search(Request $request, UserRepository $repo): JsonResponse
    {
        $q = trim($request->query->get('q', ''));

        if (strlen($q) < 2) {
            return $this->json([]);
        }

        $users = $repo->searchPublicUsers($q);

        return $this->json(array_map(fn($u) => [
            'id' => $u->getId(),
            'username' => $u->getUsername(),
            'avatar' => $u->getAvatar(),
        ], $users));
    }
}
