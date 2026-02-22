<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Récupérer et vérifier le mot de passe
            $plainPassword = $form->get('plainPassword')->getData();
            $passwordConfirm = $request->request->get('password_confirm');
            if ($plainPassword !== $passwordConfirm) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $form->createView(),
                ]);
            }

            // Hasher le mot de passe
            $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));

            // Valeurs par défaut
            $user->setRole('user');
            $user->setIsBlocked(false);

            // Upload de photo de profil (optionnel)
            $photo = $form->get('photoProfil')->getData();
            if ($photo) {
                $originalFilename = pathinfo($photo->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$photo->guessExtension();

                try {
                    $photo->move(
                        $this->getParameter('user_photos_directory'),
                        $newFilename
                    );
                    $user->setAvatar($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de la photo.');
                }
            }

            // Persister et sauvegarder en base
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Inscription réussie ! Vous pouvez maintenant vous connecter.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    // ===== AJAX Endpoint pour vérifier username & email =====
    #[Route('/check-field', name: 'check_field', methods: ['GET'])]
    public function checkField(Request $request, UserRepository $userRepository): JsonResponse
    {
        $field = $request->query->get('field');
        $value = trim($request->query->get('value', ''));

        if (!$field || !$value) {
            return new JsonResponse(['valid' => false]);
        }

        if ($field === 'username') {
            $user = $userRepository->findOneBy(['username' => $value]);
            if ($user) {
                $suggestions = [];
                for ($i = 1; $i <= 5; $i++) {
                    $suggestion = $value . rand(10, 99);
                    if (!$userRepository->findOneBy(['username' => $suggestion])) {
                        $suggestions[] = $suggestion;
                    }
                }
                return new JsonResponse([
                    'valid' => false,
                    'message' => 'Ce nom d\'utilisateur est déjà pris.',
                    'suggestions' => $suggestions
                ]);
            }
        }

        if ($field === 'email') {
            $user = $userRepository->findOneBy(['email' => $value]);
            if ($user) {
                return new JsonResponse([
                    'valid' => false,
                    'message' => 'Cette adresse email est déjà utilisée.'
                ]);
            }
        }

        return new JsonResponse(['valid' => true]);
    }
}
