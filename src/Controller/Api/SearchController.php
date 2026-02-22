<?php
// src/Controller/Api/SearchController.php

namespace App\Controller\Api;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SearchController extends AbstractController  // Nom changé
{
    #[Route('/api/search/users', name: 'api_search_users', methods: ['GET'])]  // Route complète
    public function searchUsers(Request $request, UserRepository $userRepository): JsonResponse
    {
        $query = $request->query->get('q', '');
        
        if (strlen($query) < 2) {
            return $this->json([]);
        }
        
        $users = $userRepository->searchUsersApi($query);
        
        return $this->json($users);
    }
}