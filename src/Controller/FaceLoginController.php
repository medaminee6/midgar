<?php
// src/Controller/FaceLoginController.php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class FaceLoginController extends AbstractController
{
    private TokenStorageInterface $tokenStorage;
    private RequestStack $requestStack;
    private EntityManagerInterface $entityManager;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        RequestStack $requestStack,
        EntityManagerInterface $entityManager
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->requestStack = $requestStack;
        $this->entityManager = $entityManager;
    }

    #[Route('/login/face', name: 'login_face_page', methods: ['GET'])]
    public function faceLoginPage(): Response
    {
        return $this->render('security/face_login.html.twig');
    }

    #[Route('/login/face', name: 'login_face', methods: ['POST'])]
    public function loginFace(Request $request, UserRepository $userRepo): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $descriptor = $data['descriptor'] ?? null;

            if (!$descriptor || !is_array($descriptor)) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Descripteur manquant ou invalide.'
                ]);
            }

            $firewallName = 'main';
            $bestMatch = null;
            $bestDistance = PHP_FLOAT_MAX;

            // Parcourir tous les utilisateurs
            foreach ($userRepo->findAll() as $user) {
                if (!$user->isFaceEnabled() || !$user->getFaceDescriptor()) {
                    continue;
                }

                $storedDescriptor = json_decode($user->getFaceDescriptor(), true);
                if (!$storedDescriptor || !is_array($storedDescriptor)) {
                    continue;
                }

                $distance = $this->euclideanDistance($descriptor, $storedDescriptor);

                if ($distance < $bestDistance) {
                    $bestDistance = $distance;
                    $bestMatch = $user;
                }
            }

            if ($bestMatch && $bestDistance < 0.6) {
                $roles = $bestMatch->getRoles();
                
                // Créer le token
                $token = new UsernamePasswordToken(
                    $bestMatch,
                    $firewallName,
                    $roles
                );

                // Stocker le token
                $this->tokenStorage->setToken($token);
                
                // Sauvegarder dans la session via RequestStack
                $session = $this->requestStack->getSession();
                $session->set('_security_' . $firewallName, serialize($token));

                return new JsonResponse([
                    'success' => true,
                    'message' => 'Connexion réussie'
                ]);
            }

            return new JsonResponse([
                'success' => false,
                'message' => $bestMatch ? 'Seuil de confiance trop bas' : 'Aucun visage correspondant'
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur serveur : ' . $e->getMessage()
            ]);
        }
    }

    private function euclideanDistance(array $a, array $b): float
    {
        if (count($a) !== count($b)) {
            return PHP_FLOAT_MAX;
        }
        
        $sum = 0.0;
        for ($i = 0; $i < count($a); $i++) {
            $sum += ($a[$i] - $b[$i]) ** 2;
        }
        return sqrt($sum);
    }

    #[Route('/face/register', name: 'face_register', methods: ['POST'])]
    public function registerFace(Request $request, UserRepository $userRepo): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $descriptor = $data['descriptor'] ?? null;
            $userId = $data['userId'] ?? null;

            if (!$descriptor || !is_array($descriptor) || !$userId) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Données invalides'
                ]);
            }

            $user = $userRepo->find($userId);
            if (!$user) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé'
                ]);
            }

            $user->setFaceDescriptor(json_encode($descriptor));
            $user->setFaceEnabled(true);
            
            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Visage enregistré avec succès'
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage()
            ]);
        }
    }
}