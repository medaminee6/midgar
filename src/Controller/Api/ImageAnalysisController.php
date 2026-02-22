<?php

namespace App\Controller\Api;

use App\Service\ImageAnalysisService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mime\MimeTypes;

#[Route('/api/ai', name: 'api_ai_')]
class ImageAnalysisController extends AbstractController
{
    private ImageAnalysisService $imageAnalysisService;

    public function __construct(ImageAnalysisService $imageAnalysisService)
    {
        $this->imageAnalysisService = $imageAnalysisService;
    }

    /**
     * Analyze an uploaded image and generate descriptions
     * 
     * POST /api/ai/analyze-image
     * 
     * Parameters:
     * - image: Uploaded image file (required)
     * - entityType: 'oeuvre' or 'artefact' (optional, default: 'oeuvre')
     * - language: Language code 'fr' or 'en' (optional, default: 'fr')
     */
    #[Route('/analyze-image', name: 'analyze_image', methods: ['POST'])]
    public function analyzeImage(Request $request): JsonResponse
    {
        // Check if service is configured
        if (!$this->imageAnalysisService->isConfigured()) {
            return $this->json([
                'success' => false,
                'error' => 'Le service IA n\'est pas configuré. Veuillez contacter l\'administrateur.',
                'descriptions' => [],
            ], 503);
        }

        // Get parameters
        $entityType = $request->request->get('entityType', 'oeuvre');
        $language = $request->request->get('language', 'fr');

        // Validate entity type
        if (!in_array($entityType, ['oeuvre', 'artefact'])) {
            $entityType = 'oeuvre';
        }

        // Validate language
        if (!in_array($language, ['fr', 'en'])) {
            $language = 'fr';
        }

        /** @var UploadedFile|null $imageFile */
        $imageFile = $request->files->get('image');

        if (!$imageFile) {
            return $this->json([
                'success' => false,
                'error' => 'Aucune image fournie',
                'descriptions' => [],
            ], 400);
        }

        // Validate image
        $allowedMimeTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
        ];

        $mimeType = $imageFile->getMimeType() ?? $imageFile->getClientMimeType();

        if (!in_array($mimeType, $allowedMimeTypes)) {
            return $this->json([
                'success' => false,
                'error' => 'Format d\'image non autorisé. Utilisez JPG, PNG, GIF ou WebP.',
                'descriptions' => [],
            ], 400);
        }

        // Check file size (max 10MB)
        if ($imageFile->getSize() > 10 * 1024 * 1024) {
            return $this->json([
                'success' => false,
                'error' => 'L\'image est trop volumineuse. Taille maximale: 10MB',
                'descriptions' => [],
            ], 400);
        }

        try {
            // Get uploads directory from container parameter
            $uploadsDirectory = $this->getParameter('uploads_directory');

            // Save the uploaded file temporarily
            $extension = $imageFile->guessExtension() ?: 'jpg';
            $filename = 'temp_' . uniqid() . '.' . $extension;
            $tempPath = $uploadsDirectory . '/' . $filename;

            // Ensure uploads directory exists
            if (!is_dir($uploadsDirectory)) {
                mkdir($uploadsDirectory, 0755, true);
            }

            $imageFile->move($uploadsDirectory, $filename);

            // Analyze the image
            $descriptions = $this->imageAnalysisService->analyzeImage(
                $tempPath,
                $entityType,
                $language
            );

            // Clean up temp file
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }

            return $this->json([
                'success' => true,
                'descriptions' => $descriptions,
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Erreur lors de l\'analyse de l\'image: ' . $e->getMessage(),
                'descriptions' => [],
            ], 500);
        }
    }

    /**
     * Analyze an image from base64 data (for drawn images)
     * 
     * POST /api/ai/analyze-base64
     */
    #[Route('/analyze-base64', name: 'analyze_base64', methods: ['POST'])]
    public function analyzeFromBase64(Request $request): JsonResponse
    {
        // Check if service is configured
        if (!$this->imageAnalysisService->isConfigured()) {
            return $this->json([
                'success' => false,
                'error' => 'Le service IA n\'est pas configuré. Veuillez contacter l\'administrateur.',
                'descriptions' => [],
            ], 503);
        }

        // Get parameters
        $entityType = $request->request->get('entityType', 'oeuvre');
        $language = $request->request->get('language', 'fr');
        $imageData = $request->request->get('imageData');

        // Validate entity type
        if (!in_array($entityType, ['oeuvre', 'artefact'])) {
            $entityType = 'oeuvre';
        }

        // Validate language
        if (!in_array($language, ['fr', 'en'])) {
            $language = 'fr';
        }

        if (empty($imageData)) {
            return $this->json([
                'success' => false,
                'error' => 'Aucune donnée d\'image fournie',
                'descriptions' => [],
            ], 400);
        }

        // Extract base64 data and mime type
        if (preg_match('/^data:(image\/\w+);base64,(.+)$/', $imageData, $matches)) {
            $mimeType = $matches[1];
            $base64Data = $matches[2];
        } else {
            // Default to PNG if no mime type found
            $mimeType = 'image/png';
            $base64Data = $imageData;
        }

        // Validate mime type
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($mimeType, $allowedMimeTypes)) {
            return $this->json([
                'success' => false,
                'error' => 'Format d\'image non autorisé',
                'descriptions' => [],
            ], 400);
        }

        try {
            $descriptions = $this->imageAnalysisService->analyzeFromBase64(
                $base64Data,
                $mimeType,
                $entityType,
                $language
            );

            return $this->json([
                'success' => true,
                'descriptions' => $descriptions,
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Erreur lors de l\'analyse de l\'image: ' . $e->getMessage(),
                'descriptions' => [],
            ], 500);
        }
    }

    /**
     * Check if the AI service is configured and available
     */
    #[Route('/status', name: 'status', methods: ['GET'])]
    public function status(): JsonResponse
    {
        return $this->json([
            'success' => true,
            'configured' => $this->imageAnalysisService->isConfigured(),
            'provider' => $this->imageAnalysisService->getApiProvider(),
        ]);
    }
}
