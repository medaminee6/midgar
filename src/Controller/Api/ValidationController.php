<?php
// src/Controller/Api/ValidationController.php

namespace App\Controller\Api;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api', name: 'api_')]
class ValidationController extends AbstractController
{
    private array $suffixes = [
        '123', '42', '007', '2024', '2025', '2026', '2027',
        '_official', '_fan', '_legend', '_master', '_hunter',
        '_warrior', '_mage', '_wizard', '_knight', '_hero',
        '1', '2', '3', '7', '99', '01', '02', '03', '04',
        '_pro', '_real', '_original', '_world', '_game',
        '_player', '_gamer', '_champion', '_elite', '_shadow'
    ];

    #[Route('/check-username', name: 'check_username', methods: ['POST'])]
    public function checkUsername(Request $request, UserRepository $userRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $username = $data['username'] ?? '';
        
        if (empty($username)) {
            return $this->json([
                'available' => false,
                'message' => 'Le nom d\'utilisateur est requis',
                'suggestions' => []
            ]);
        }

        // Validation des règles de base
        $errors = [];
        if (strlen($username) < 3) {
            $errors[] = 'minimum 3 caractères';
        }
        if (strlen($username) > 20) {
            $errors[] = 'maximum 20 caractères';
        }
        if (!preg_match('/^[a-zA-Z0-9]+$/', $username)) {
            $errors[] = 'lettres et chiffres uniquement';
        }

        if (!empty($errors)) {
            return $this->json([
                'available' => false,
                'message' => 'Format invalide: ' . implode(', ', $errors),
                'suggestions' => []
            ]);
        }

        // Vérifier si l'utilisateur existe
        $existingUser = $userRepository->findOneBy(['username' => $username]);
        
        if ($existingUser) {
            $suggestions = $this->generateSuggestions($username, $userRepository);
            return $this->json([
                'available' => false,
                'message' => 'Ce nom d\'utilisateur est déjà pris',
                'suggestions' => $suggestions
            ]);
        }

        return $this->json([
            'available' => true,
            'message' => 'Nom d\'utilisateur disponible',
            'suggestions' => []
        ]);
    }

    #[Route('/check-email', name: 'check_email', methods: ['POST'])]
    public function checkEmail(Request $request, UserRepository $userRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? '';
        
        if (empty($email)) {
            return $this->json([
                'available' => false,
                'message' => 'L\'email est requis'
            ]);
        }

        // Validation du format email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json([
                'available' => false,
                'message' => 'Format d\'email invalide'
            ]);
        }

        // Domaines temporaires bloqués
        $blockedDomains = ['yopmail.com', 'tempmail.com', 'guerrillamail.com'];
        $domain = substr(strrchr($email, "@"), 1);
        if (in_array($domain, $blockedDomains)) {
            return $this->json([
                'available' => false,
                'message' => 'Les emails temporaires ne sont pas autorisés'
            ]);
        }

        // Vérifier si l'email existe
        $existingUser = $userRepository->findOneBy(['email' => $email]);
        
        if ($existingUser) {
            return $this->json([
                'available' => false,
                'message' => 'Cet email est déjà utilisé'
            ]);
        }

        return $this->json([
            'available' => true,
            'message' => 'Email disponible'
        ]);
    }

    private function generateSuggestions(string $baseUsername, UserRepository $repository): array
    {
        $suggestions = [];
        $maxSuggestions = 6;
        $tried = [];
        
        // Nettoyer le nom de base
        $base = preg_replace('/[^a-zA-Z0-9]/', '', $baseUsername);
        
        // Essayer différentes combinaisons
        for ($i = 0; $i < 30 && count($suggestions) < $maxSuggestions; $i++) {
            $suggestion = '';
            
            // Alterner entre différents types de suggestions
            $type = $i % 4;
            
            switch ($type) {
                case 0: // Suffixe numérique
                    $suggestion = $base . rand(10, 999);
                    break;
                case 1: // Préfixe + base
                    $prefixes = ['the', 'mr', 'ms', 'real', 'official', 'its', 'thisis'];
                    $suggestion = $prefixes[array_rand($prefixes)] . $base;
                    break;
                case 2: // Base + suffixe prédéfini
                    $suggestion = $base . $this->suffixes[array_rand($this->suffixes)];
                    break;
                case 3: // Base + année
                    $years = ['20', '21', '22', '23', '24', '2024', '2025', '2026'];
                    $suggestion = $base . $years[array_rand($years)];
                    break;
            }
            
            // S'assurer que la suggestion respecte les règles
            $suggestion = substr($suggestion, 0, 20);
            $suggestion = preg_replace('/[^a-zA-Z0-9]/', '', $suggestion);
            
            if (strlen($suggestion) < 3) {
                continue;
            }
            
            // Vérifier que la suggestion n'existe pas déjà et n'a pas été essayée
            if (!in_array($suggestion, $tried) && !$repository->findOneBy(['username' => $suggestion])) {
                $suggestions[] = $suggestion;
                $tried[] = $suggestion;
            }
        }
        
        return array_slice($suggestions, 0, $maxSuggestions);
    }
}