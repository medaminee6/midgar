<?php

namespace App\Controller;

use App\Repository\OeuvreRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ValidationController extends AbstractController
{
    #[Route('/validate-field', name: 'validate_field', methods: ['POST'])]
    public function validateField(
        Request $request, 
        OeuvreRepository $oeuvreRepository
    ): JsonResponse
    {
        $fieldName = $request->request->get('fieldName');
        $value = $request->request->get('value');

        switch ($fieldName) {
            case 'description':
                return $this->validateDescription($value);
            case 'artworkId':
                return $this->validateArtworkId($value, $oeuvreRepository);
            default:
                return new JsonResponse(['valid' => true]);
        }
    }

    private function validateDescription(?string $value): JsonResponse
    {
        if (empty(trim($value ?? ''))) {
            return new JsonResponse([
                'valid' => false,
                'message' => 'La description est requise.'
            ]);
        }

        if (strlen($value) < 5) {
            return new JsonResponse([
                'valid' => false,
                'message' => 'La description doit contenir au moins 5 caractères.'
            ]);
        }

        if (strlen($value) > 5000) {
            return new JsonResponse([
                'valid' => false,
                'message' => 'La description ne peut pas dépasser 5000 caractères.'
            ]);
        }

        return new JsonResponse(['valid' => true]);
    }

    private function validateArtworkId(?string $value, OeuvreRepository $oeuvreRepository): JsonResponse
    {
        // If empty, it's optional (but we check if at least one option is provided on submit)
        if (empty($value)) {
            return new JsonResponse(['valid' => true]); // Optional field
        }

        if (!is_numeric($value) || (int)$value < 0) {
            return new JsonResponse([
                'valid' => false,
                'message' => 'L\'ID doit être un nombre positif.'
            ]);
        }

        // Check if artwork exists
        $oeuvre = $oeuvreRepository->find((int)$value);
        if (!$oeuvre) {
            return new JsonResponse([
                'valid' => false,
                'message' => 'L\'œuvre avec l\'ID ' . (int)$value . ' n\'existe pas.'
            ]);
        }

        return new JsonResponse(['valid' => true]);
    }
}
