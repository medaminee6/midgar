<?php

namespace App\Controller;

use App\Entity\Commentaire;
use App\Entity\Oeuvre;
use App\Entity\Artefact;
use App\Entity\User;
use App\Repository\CommentaireRepository;
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
        TokenStorageInterface $tokenStorage
    ): Response {
        $user = $tokenStorage->getToken()?->getUser();
        
        if (!$user instanceof User) {
            $this->addFlash('error', 'Vous devez être connecté pour commenter.');
            return $this->redirectToRoute('oeuvre_show', ['id' => $oeuvre->getId()]);
        }

        $contenu = $request->request->get('contenu');
        
        if (!$contenu || trim($contenu) === '') {
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

        $this->addFlash('success', 'Commentaire ajouté avec succès!');
        
        return $this->redirectToRoute('oeuvre_show', ['id' => $oeuvre->getId()]);
    }

    #[Route('/artefact/{id}/commenter', name: 'artefact_commenter', methods: ['POST'])]
    public function commenterArtefact(
        Artefact $artefact,
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        TokenStorageInterface $tokenStorage
    ): Response {
        $user = $tokenStorage->getToken()?->getUser();
        
        if (!$user instanceof User) {
            $this->addFlash('error', 'Vous devez être connecté pour commenter.');
            return $this->redirectToRoute('artefact_show', ['id' => $artefact->getId()]);
        }

        $contenu = $request->request->get('contenu');
        
        if (!$contenu || trim($contenu) === '') {
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

        $this->addFlash('success', 'Commentaire ajouté avec succès!');
        
        return $this->redirectToRoute('artefact_show', ['id' => $artefact->getId()]);
    }

    #[Route('/commentaire/{id}/supprimer', name: 'commentaire_supprimer', methods: ['POST'])]
    public function supprimer(
        Commentaire $commentaire,
        EntityManagerInterface $em,
        TokenStorageInterface $tokenStorage
    ): Response {
        $user = $tokenStorage->getToken()?->getUser();
        
        if (!$user instanceof User) {
            $this->addFlash('error', 'Vous devez être connecté pour supprimer un commentaire.');
            return $this->redirectToRoute('home');
        }

        // Check if user is the author or admin
        if ($commentaire->getUserId() !== $user->getId() && !in_array('ROLE_ADMIN', $user->getRoles())) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer ce commentaire.');
            return $this->redirectToRoute('home');
        }

        $redirectRoute = null;
        if ($commentaire->getOeuvreId()) {
            $redirectRoute = 'oeuvre_show';
        } elseif ($commentaire->getArtefactId()) {
            $redirectRoute = 'artefact_show';
        }

        $em->remove($commentaire);
        $em->flush();

        $this->addFlash('success', 'Commentaire supprimé avec succès!');

        if ($redirectRoute) {
            $params = $commentaire->getOeuvreId() 
                ? ['id' => $commentaire->getOeuvreId()]
                : ['id' => $commentaire->getArtefactId()];
            return $this->redirectToRoute($redirectRoute, $params);
        }

        return $this->redirectToRoute('home');
    }
}
