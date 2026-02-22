<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AiImageController extends AbstractController
{
    #[Route('/api/ai-image-generate', name: 'ai_image_generate', methods: ['POST'])]
    public function generateImage(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $prompt = $data['prompt'] ?? '';
        
        if (empty($prompt)) {
            return new JsonResponse(['error' => 'Prompt is required'], 400);
        }

        // Try using Hugging Face Inference API (free tier)
        $hfToken = $_ENV['HUGGING_FACE_TOKEN'] ?? null;
        
        if ($hfToken) {
            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://api-inference.huggingface.co/models/stabilityai/stable-diffusion-2-1');
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                    'inputs' => $prompt,
                ]));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $hfToken,
                    'Content-Type: application/json'
                ]);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 120);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode === 200 && $response) {
                    $base64 = base64_encode($response);
                    $imageUrl = 'data:image/png;base64,' . $base64;
                    
                    return new JsonResponse([
                        'imageUrl' => $imageUrl,
                    ]);
                }
            } catch (\Exception $e) {
                // Continue to fallback
            }
        }

        // If no API works, return error to use local fallback
        return new JsonResponse([
            'error' => 'AI image generation not configured. Add HUGGING_FACE_TOKEN to .env file.',
            'useLocalFallback' => true,
        ], 200);
    }
}
