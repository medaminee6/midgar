<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProfileSecurityController extends AbstractController
{
    #[Route('/profil/securite/visage', name: 'face_setup', methods: ['GET'])]
    public function faceSetup(): Response
    {
        // 🔒 Si l'utilisateur n'est pas connecté, redirige vers login
        if (!$this->getUser()) {
            return $this->redirectToRoute('login');
        }

        return $this->render('security/face_setup.html.twig');
    }

    #[Route('/profil/securite/visage/save', name: 'face_save', methods: ['POST'])]
    public function saveFace(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Non connecté'
            ], 401);
        }

        $data = json_decode($request->getContent(), true);
        $descriptor = $data['descriptor'] ?? null;

        if (!$descriptor || !is_array($descriptor)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Descripteur invalide'
            ]);
        }

        $user->setFaceDescriptor(json_encode($descriptor));
        $user->setFaceEnabled(true);

        $em->flush();

        return new JsonResponse(['success' => true]);
    }
}
