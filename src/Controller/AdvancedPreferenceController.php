<?php

namespace App\Controller;

use App\Entity\AdvancedPreference;
use App\Repository\AdvancedPreferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdvancedPreferenceController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AdvancedPreferenceRepository $preferences
    ) {}

    #[Route('/preferences/advanced', name: 'advanced_preferences', methods: ['GET', 'POST'])]
    public function handlePreferences(Request $request): Response
    {
        $userId = $request->query->get('user_id', 'default_user');
        $errors = [];
        $isCreating = false;

        // Check if user already has preferences
        $preference = $this->preferences->findByUserId($userId);

        if ($request->isMethod('GET')) {
            // Show form (create or edit)
            if (!$preference) {
                $isCreating = true;
                $preference = new AdvancedPreference();
            }

            return $this->render('preferences/advanced.html.twig', [
                'preference' => $preference,
                'isCreating' => $isCreating,
                'errors' => $errors,
            ]);
        }

        // POST request - create or update
        if ($request->isMethod('POST')) {
            if (!$preference) {
                // Create new
                $preference = new AdvancedPreference();
                $preference->setUserId($userId);
                $isCreating = true;
            }

            // Populate from request
            $freeDescription = $request->request->get('free_description');
            $favoriteGenre = $request->request->get('favorite_genre');
            $affinityLevel = $request->request->get('genre_affinity');
            $favoriteThemesArray = $request->request->all()['favorite_themes'] ?? [];
            $customTags = $request->request->get('custom_tags');

            // VALIDATION START

            // 1. Validate free_description: NOT empty, minimum 20 characters
            if (empty($freeDescription)) {
                $errors[] = 'La description ne peut pas être vide.';
            } elseif (strlen($freeDescription) < 20) {
                $errors[] = 'La description doit contenir au minimum 20 caractères.';
            }

            // 2. Validate favorite_genre: NOT empty
            if (empty($favoriteGenre)) {
                $errors[] = 'Le genre préféré doit être sélectionné.';
            }

            // 3. Validate genre_affinity: numeric, between 0 and 10
            if (!is_numeric($affinityLevel)) {
                $errors[] = 'Le niveau d\'affinité doit être un nombre.';
            } elseif ((int)$affinityLevel < 0 || (int)$affinityLevel > 10) {
                $errors[] = 'Le niveau d\'affinité doit être entre 0 et 10.';
            }

            // 4. Validate favorite_themes: array, at least 1 theme selected
            if (!is_array($favoriteThemesArray) || empty($favoriteThemesArray)) {
                $errors[] = 'Au moins un thème doit être sélectionné.';
            }

            // 5. Validate custom_tags: NOT empty, trim spaces
            $customTagsTrimmed = trim($customTags ?? '');
            if (empty($customTagsTrimmed)) {
                $errors[] = 'Les tags personnalisés ne peuvent pas être vides.';
            }

            // VALIDATION END

            if (empty($errors)) {
                $preference->setFreeDescription($freeDescription);
                $preference->setFavoriteGenre($favoriteGenre);
                $preference->setAffinityLevel((int)$affinityLevel);
                $preference->setFavoriteThemes(implode(', ', $favoriteThemesArray));
                $preference->setCustomTags($customTagsTrimmed);
                $preference->setUpdatedAt(new \DateTime());

                if ($isCreating) {
                    $this->entityManager->persist($preference);
                }
                $this->entityManager->flush();

                return $this->redirectToRoute('discover');
            }

            // Re-render with errors
            return $this->render('preferences/advanced.html.twig', [
                'preference' => $preference,
                'isCreating' => $isCreating,
                'errors' => $errors,
            ]);
        }

        return $this->redirectToRoute('discover');
    }

    #[Route('/preferences/advanced/delete', name: 'advanced_preferences_delete', methods: ['POST'])]
    public function deletePreferences(Request $request): Response
    {
        // CSRF protection (graceful validation)
        if (!$this->isCsrfTokenValid('delete_preferences', $request->request->get('_token'))) {
            // Token invalid, silently redirect without deletion
            return $this->redirectToRoute('discover');
        }

        $userId = $request->query->get('user_id', 'default_user');
        $preference = $this->preferences->findByUserId($userId);

        if ($preference) {
            $this->entityManager->remove($preference);
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('discover');
    }
}
