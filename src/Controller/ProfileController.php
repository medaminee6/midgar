<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Artefact;
use App\Entity\Oeuvre;
use App\Form\ProfileType;
use App\Form\ChangePasswordType;
use App\Repository\ArtefactRepository;
use App\Repository\OeuvreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'profile')]
    public function index(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException('Accès refusé');
        }

        // --- Formulaire profil ---
        $profileForm = $this->createForm(ProfileType::class, $user);
        $profileForm->handleRequest($request);

        if ($profileForm->isSubmitted() && $profileForm->isValid()) {
            $avatarFile = $profileForm->get('avatar')->getData();
            if ($avatarFile) {
                $oldAvatar = $user->getAvatar();
                if ($oldAvatar) {
                    $oldPath = $this->getParameter('avatars_directory') . '/' . $oldAvatar;
                    if (file_exists($oldPath))
                        unlink($oldPath);
                }

                $filename = uniqid('avatar_', true) . '.' . $avatarFile->guessExtension();
                try {
                    $avatarFile->move($this->getParameter('avatars_directory'), $filename);
                    $user->setAvatar($filename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'avatar.');
                }
            }

            $em->flush();
            $this->addFlash('success', 'Profil mis à jour');
            return $this->redirectToRoute('profile');
        }

        // --- Formulaire changement mot de passe ---
        $passwordForm = $this->createForm(ChangePasswordType::class);
        $passwordForm->handleRequest($request);

        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            $oldPassword = $passwordForm->get('oldPassword')->getData();
            $newPassword = $passwordForm->get('newPassword')->getData();
            $confirmPassword = $passwordForm->get('confirmPassword')->getData();

            if (!$hasher->isPasswordValid($user, $oldPassword)) {
                $this->addFlash('error', "L'ancien mot de passe est incorrect.");
            } elseif ($newPassword !== $confirmPassword) {
                $this->addFlash('error', 'La confirmation du mot de passe ne correspond pas.');
            } else {
                $user->setPassword($hasher->hashPassword($user, $newPassword));
                $em->flush();
                $this->addFlash('success', 'Mot de passe mis à jour.');
                return $this->redirectToRoute('profile');
            }
        }

        // Load user's artefacts and oeuvres to display in profile
        $artefacts = [];
        $oeuvres = [];
        $currentUser = $this->getUser();
        if ($currentUser instanceof User) {
            /** @var ArtefactRepository $artefactRepo */
            $artefactRepo = $em->getRepository(Artefact::class);
            $artefacts = $artefactRepo->createQueryBuilder('a')
                ->where('a.createdBy = :user')
                ->setParameter('user', $currentUser)
                ->orderBy('a.createdAt', 'DESC')
                ->getQuery()
                ->getResult();

            /** @var OeuvreRepository $oeuvreRepo */
            $oeuvreRepo = $em->getRepository(Oeuvre::class);
            $oeuvres = $oeuvreRepo->createQueryBuilder('o')
                ->where('o.createdBy = :user')
                ->setParameter('user', $currentUser)
                ->orderBy('o.createdAt', 'DESC')
                ->getQuery()
                ->getResult();
        }

        return $this->render('profile/profile.html.twig', [
            'user' => $user,
            'profileForm' => $profileForm->createView(),
            'passwordForm' => $passwordForm->createView(),
            'artefacts' => $artefacts,
            'oeuvres' => $oeuvres,
            'isOwner' => true,
            ]);

        
    }

    #[Route('/profile/delete', name: 'profile_delete', methods: ['POST'])]
    public function delete(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $password = $request->request->get('password');

        if (!$hasher->isPasswordValid($user, $password)) {
            $this->addFlash('error', 'Mot de passe incorrect. Suppression annulée.');
            return $this->redirectToRoute('profile');
        }

        // Supprimer avatar si existant
        if ($user->getAvatar()) {
            $path = $this->getParameter('avatars_directory') . '/' . $user->getAvatar();
            if (file_exists($path))
                unlink($path);
        }

        $em->remove($user);
        $em->flush();

        $this->addFlash('success', 'Votre compte a été supprimé.');
        return $this->redirectToRoute('home');
    }
    #[Route('/profile/{id}', name: 'profile_public', requirements: ['id' => '\d+'])]
public function publicProfile(
    User $user,
    EntityManagerInterface $em
): Response {
    // sécurité minimale
    if (!$user->isVerified()) {
        throw $this->createNotFoundException();
    }

    // charger ses artefacts
    $artefacts = $em->getRepository(Artefact::class)
        ->createQueryBuilder('a')
        ->where('a.createdBy = :user')
        ->setParameter('user', $user)
        ->orderBy('a.createdAt', 'DESC')
        ->getQuery()
        ->getResult();

    // charger ses œuvres
    $oeuvres = $em->getRepository(Oeuvre::class)
        ->createQueryBuilder('o')
        ->where('o.createdBy = :user')
        ->setParameter('user', $user)
        ->orderBy('o.createdAt', 'DESC')
        ->getQuery()
        ->getResult();

    return $this->render('profile/profile.html.twig', [
        'user' => $user,
        'artefacts' => $artefacts,
        'oeuvres' => $oeuvres,
        'profileForm' => null,
        'passwordForm' => null,
        'isOwner' => false,
    ]);
}

}
