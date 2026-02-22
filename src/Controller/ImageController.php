<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Doctrine\ORM\EntityManagerInterface;

class ImageController extends AbstractController
{
    #[Route('/generate', name: 'generate_image', methods: ['GET', 'POST'])]
    public function generate(Request $request): Response
    {
        $generatedImage = null;

        if ($request->isMethod('POST')) {

            // ✅ Theme
            $theme = $request->request->get('theme', 'fantasy');
            $allowedThemes = ['fantasy', 'anime', 'cyberpunk', 'steampunk', 'dark_fantasy'];

            if (!in_array($theme, $allowedThemes, true)) {
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse(['success' => false, 'error' => 'Thème invalide']);
                }
                return new Response("Thème invalide");
            }

            // ✅ Image upload
            $uploadedFile = $request->files->get('image');
            if (!$uploadedFile) {
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse(['success' => false, 'error' => 'Aucune image envoyée']);
                }
                return new Response("Aucune image envoyée");
            }

            $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads';
            $inputName = 'input_user_image.png';
            $outputName = 'avatar_' . time() . '.png';

            try {
                $uploadedFile->move($uploadsDir, $inputName);
            } catch (FileException $e) {
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse(['success' => false, 'error' => 'Erreur upload : ' . $e->getMessage()]);
                }
                return new Response("Erreur upload : " . $e->getMessage());
            }

            $inputPath = $uploadsDir . '/' . $inputName;
            $outputPath = $uploadsDir . '/' . $outputName;

            // ✅ Appel Python AVEC input + output + theme
            $scriptPath = $this->getParameter('kernel.project_dir') . '/src/Service/generate_image.py';

            $command = sprintf(
                'python %s %s %s %s 2>&1',
                escapeshellarg($scriptPath),
                escapeshellarg($inputPath),
                escapeshellarg($outputPath),
                escapeshellarg($theme)
            );

            exec($command, $output, $returnVar);

            if ($returnVar !== 0 || !file_exists($outputPath)) {
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse([
                        'success' => false, 
                        'error' => 'Erreur génération: ' . implode("\n", $output)
                    ]);
                }
                return new Response(
                    "<pre>Erreur génération:\n" . implode("\n", $output) . "</pre>"
                );
            }

            $generatedImage = '/uploads/' . $outputName;
            
            // ✅ Si c'est une requête AJAX, retourner du JSON
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => true,
                    'image' => $generatedImage
                ]);
            }
        }

        // ✅ Si ce n'est pas une requête AJAX, afficher la page normale
        return $this->render('image/generate.html.twig', [
            'image' => $generatedImage
        ]);
    }

    #[Route('/update-avatar', name: 'update_avatar', methods: ['POST'])]
    public function updateAvatar(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['success' => false, 'error' => 'Non authentifié']);
        }
        
        $data = json_decode($request->getContent(), true);
        $avatarFile = $data['avatar'] ?? null;
        
        if (!$avatarFile) {
            return new JsonResponse(['success' => false, 'error' => 'Aucun fichier spécifié']);
        }
        
        // Vérifier que le fichier existe dans le dossier uploads
        $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads';
        if (!file_exists($uploadsDir . '/' . $avatarFile)) {
            return new JsonResponse(['success' => false, 'error' => 'Fichier non trouvé']);
        }
        
        // Mettre à jour l'avatar de l'utilisateur
        $user->setAvatar($avatarFile);
        
        $entityManager->persist($user);
        $entityManager->flush();
        
        return new JsonResponse(['success' => true]);
    }
}