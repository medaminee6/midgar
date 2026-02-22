<?php

namespace App\Controller;

use App\Entity\Commentaire;
use App\Entity\Oeuvre;
use App\Entity\Artefact;
use App\Entity\User;
use App\Service\CommentNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class CommentaireController extends AbstractController
{
    #[Route('/oeuvre/{id}/commenter', name: 'oeuvre_commenter', methods: ['POST'])]
    public function commenterOeuvre(
        Oeuvre $oeuvre,
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        TokenStorageInterface $tokenStorage,
        CommentNotificationService $notificationService
    ): Response {
        $user = $tokenStorage->getToken()?->getUser();

        if (!$user instanceof User) {
            $this->addFlash('error', 'Vous devez être connecté pour commenter.');
            return $this->redirectToRoute('oeuvre_show', ['id' => $oeuvre->getId()]);
        }

        $contenu = trim($request->request->get('contenu'));

        if ($contenu === '') {
            $this->addFlash('error', 'Le commentaire ne peut pas être vide.');
            return $this->redirectToRoute('oeuvre_show', ['id' => $oeuvre->getId()]);
        }

        $commentaire = new Commentaire();
        $commentaire->setContenu($contenu);
        $commentaire->setUserId($user->getId());
        $commentaire->setOeuvreId($oeuvre->getId());

        $errors = $validator->validate($commentaire);
        if (count($errors) > 0) {
            $this->addFlash('error', $errors[0]->getMessage());
            return $this->redirectToRoute('oeuvre_show', ['id' => $oeuvre->getId()]);
        }

        $em->persist($commentaire);
        $em->flush();

        $notificationService->notifyCommentCreated($commentaire, $user);

        $this->addFlash('success', 'Commentaire ajouté avec succès !');
        return $this->redirectToRoute('oeuvre_show', ['id' => $oeuvre->getId()]);
    }

    #[Route('/artefact/{id}/commenter', name: 'artefact_commenter', methods: ['POST'])]
    public function commenterArtefact(
        Artefact $artefact,
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        TokenStorageInterface $tokenStorage,
        CommentNotificationService $notificationService
    ): Response {
        $user = $tokenStorage->getToken()?->getUser();

        if (!$user instanceof User) {
            $this->addFlash('error', 'Vous devez être connecté pour commenter.');
            return $this->redirectToRoute('artefact_show', ['id' => $artefact->getId()]);
        }

        $contenu = trim($request->request->get('contenu'));

        if ($contenu === '') {
            $this->addFlash('error', 'Le commentaire ne peut pas être vide.');
            return $this->redirectToRoute('artefact_show', ['id' => $artefact->getId()]);
        }

        $commentaire = new Commentaire();
        $commentaire->setContenu($contenu);
        $commentaire->setUserId($user->getId());
        $commentaire->setArtefactId($artefact->getId());

        $errors = $validator->validate($commentaire);
        if (count($errors) > 0) {
            $this->addFlash('error', $errors[0]->getMessage());
            return $this->redirectToRoute('artefact_show', ['id' => $artefact->getId()]);
        }

        $em->persist($commentaire);
        $em->flush();

        $notificationService->notifyCommentCreated($commentaire, $user);

        $this->addFlash('success', 'Commentaire ajouté avec succès !');
        return $this->redirectToRoute('artefact_show', ['id' => $artefact->getId()]);
    }

    // 🔥 ICI LA CORRECTION IMPORTANTE
    #[Route('/commentaire/{id}/supprimer', name: 'commentaire_supprimer', methods: ['GET','POST'])]
    public function supprimer(
        Commentaire $commentaire,
        EntityManagerInterface $em,
        TokenStorageInterface $tokenStorage
    ): Response {
        $user = $tokenStorage->getToken()?->getUser();

        if (!$user instanceof User) {
            $this->addFlash('error', 'Connexion requise.');
            return $this->redirectToRoute('home');
        }

        // Autorisations
        $isAuthor = $commentaire->getUserId() === $user->getId();
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles());
        $isCreator = false;

        if ($commentaire->getOeuvreId()) {
            $oeuvre = $em->getRepository(Oeuvre::class)->find($commentaire->getOeuvreId());
            $isCreator = $oeuvre && $oeuvre->getCreatedBy()?->getId() === $user->getId();
        } elseif ($commentaire->getArtefactId()) {
            $artefact = $em->getRepository(Artefact::class)->find($commentaire->getArtefactId());
            $isCreator = $artefact && $artefact->getCreatedBy()?->getId() === $user->getId();
        }

        if (!$isAuthor && !$isAdmin && !$isCreator) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer ce commentaire.');
            return $this->redirectToRoute('home');
        }

        $redirectRoute = $commentaire->getOeuvreId() ? 'oeuvre_show' : 'artefact_show';
        $redirectId = $commentaire->getOeuvreId() ?? $commentaire->getArtefactId();

        $em->remove($commentaire);
        $em->flush();

        $this->addFlash('success', 'Commentaire supprimé avec succès !');

        return $this->redirectToRoute($redirectRoute, ['id' => $redirectId]);
    }
}
