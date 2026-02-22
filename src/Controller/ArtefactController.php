<?php

namespace App\Controller;

use App\Entity\Artefact;
use App\Entity\User;
use App\Repository\ArtefactRepository;
use App\Repository\CommentaireRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/artefact', name: 'artefact_')]
class ArtefactController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, ArtefactRepository $repository): Response
    {
        $search = $request->query->get('search', '');
        $type = $request->query->get('type', '');
        $myItems = $request->query->get('my_items', 0);

        $queryBuilder = $repository->createQueryBuilder('a');

        if ($search) {
            $queryBuilder->andWhere('a.name LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($type) {
            $queryBuilder->andWhere('a.type = :type')
                ->setParameter('type', $type);
        }

        $queryBuilder->orderBy('a.type', 'ASC');
        $artefacts = $queryBuilder->getQuery()->getResult();

        // Filtrer les artefacts du current user si demandé
        $currentUser = $this->getUser();
        if ($myItems && $currentUser instanceof User) {
            $currentUserId = $currentUser->getId();
            $artefacts = array_filter($artefacts, fn($a) => $a->getCreatedBy()?->getId() === $currentUserId);
        }

        // Récupération de tous les types disponibles
        $allTypes = $repository->createQueryBuilder('a')
            ->select('DISTINCT a.type')
            ->orderBy('a.type', 'ASC')
            ->getQuery()
            ->getResult();
        $types = array_map(fn($row) => $row['type'], $allTypes);

        return $this->render('artefact/index.html.twig', [
            'artefacts' => $artefacts,
            'search' => $search,
            'type' => $type,
            'types' => $types,
            'myItems' => $myItems,
            'currentUser' => $currentUser,
        ]);
    }

    #[Route('/create', name: 'create', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        $artefact = new Artefact();
        $errors = [];
        $oldValues = [];

        if ($request->isMethod('POST')) {
            $fields = ['name', 'type', 'universe', 'origins', 'powers', 'rarity', 'tag'];
            foreach ($fields as $field) {
                $oldValues[$field] = $request->request->get($field);
            }

            $artefact->setName($oldValues['name'] ?? '');
            $artefact->setType($oldValues['type'] ?? '');
            $artefact->setUniverse($oldValues['universe'] ?? '');
            $artefact->setOrigins($oldValues['origins'] ?? '');
            $artefact->setPowers($oldValues['powers'] ?? '');
            $artefact->setRarity($oldValues['rarity'] ?? '');
            $artefact->setTag($oldValues['tag'] ?? '');

            // Validation Symfony
            $errors = $validator->validate($artefact);

            // Validation image
            $imageFile = $request->files->get('image');
            if (!$imageFile) {
                $errors[] = "L'image est requise";
            } else {
                $validExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                $extension = strtolower(pathinfo($imageFile->getClientOriginalName(), PATHINFO_EXTENSION));
                if (!in_array($extension, $validExtensions)) {
                    $errors[] = "Formats autorisés: jpg, png, webp, gif";
                }
            }

            if (count($errors) === 0) {
                $artefact->setCreatedBy($this->getUser());

                if ($imageFile) {
                    $filename = uniqid() . '.' . $extension;
                    $imageFile->move($this->getParameter('uploads_directory'), $filename);
                    $artefact->setImageUrl('/uploads/' . $filename);
                }

                $em->persist($artefact);
                $em->flush();

                $this->addFlash('success', 'Artefact créé avec succès!');
                return $this->redirectToRoute('artefact_index');
            }
        }

        return $this->render('artefact/create.html.twig', [
            'artefact' => $artefact,
            'errors' => $errors,
            'oldValues' => $oldValues,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Artefact $artefact, CommentaireRepository $commentaireRepo, UserRepository $userRepo): Response
    {
        $commentaires = $commentaireRepo->findByArtefact($artefact->getId());

        $userNames = [];
        foreach ($commentaires as $c) {
            $user = $userRepo->find($c->getUserId());
            $userNames[$c->getUserId()] = $user ? $user->getPrenom() . ' ' . $user->getNom() : 'Utilisateur';
        }

        return $this->render('artefact/show.html.twig', [
            'artefact' => $artefact,
            'commentaires' => $commentaires,
            'userNames' => $userNames,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Artefact $artefact, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        $currentUser = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $owner = $artefact->getCreatedBy();
        $isOwner = $currentUser instanceof User && $owner instanceof User ? $currentUser->getId() === $owner->getId() : false;

        if (!$isOwner && !$isAdmin) {
            $this->addFlash('error', "Vous n'êtes pas autorisé à modifier cet artefact.");
            return $this->redirectToRoute('artefact_show', ['id' => $artefact->getId()]);
        }

        $errors = [];
        $oldValues = [];

        if ($request->isMethod('POST')) {
            $fields = ['name', 'type', 'universe', 'origins', 'powers', 'rarity', 'tag'];
            foreach ($fields as $field) {
                $oldValues[$field] = $request->request->get($field);
            }

            $artefact->setName($oldValues['name'] ?? '');
            $artefact->setType($oldValues['type'] ?? '');
            $artefact->setUniverse($oldValues['universe'] ?? '');
            $artefact->setOrigins($oldValues['origins'] ?? '');
            $artefact->setPowers($oldValues['powers'] ?? '');
            $artefact->setRarity($oldValues['rarity'] ?? '');
            $artefact->setTag($oldValues['tag'] ?? '');

            $errors = $validator->validate($artefact);

            // Image update
            $imageFile = $request->files->get('image');
            if ($imageFile) {
                $validExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                $extension = strtolower(pathinfo($imageFile->getClientOriginalName(), PATHINFO_EXTENSION));
                if (!in_array($extension, $validExtensions)) {
                    $errors[] = "Formats autorisés: jpg, png, webp, gif";
                } else {
                    if ($artefact->getImageUrl()) {
                        $oldFile = $this->getParameter('kernel.project_dir') . '/public' . $artefact->getImageUrl();
                        if (file_exists($oldFile)) unlink($oldFile);
                    }
                    $filename = uniqid() . '.' . $extension;
                    $imageFile->move($this->getParameter('uploads_directory'), $filename);
                    $artefact->setImageUrl('/uploads/' . $filename);
                }
            }

            if (count($errors) === 0) {
                $em->flush();
                $this->addFlash('success', 'Artefact modifié avec succès!');
                return $this->redirectToRoute('artefact_show', ['id' => $artefact->getId()]);
            }
        }

        return $this->render('artefact/edit.html.twig', [
            'artefact' => $artefact,
            'errors' => $errors,
            'oldValues' => $oldValues,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Artefact $artefact, EntityManagerInterface $em): Response
    {
        $currentUser = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $owner = $artefact->getCreatedBy();
        $isOwner = $currentUser instanceof User && $owner instanceof User ? $currentUser->getId() === $owner->getId() : false;

        if (!$isOwner && !$isAdmin) {
            $this->addFlash('error', "Vous n'êtes pas autorisé à supprimer cet artefact.");
            return $this->redirectToRoute('artefact_show', ['id' => $artefact->getId()]);
        }

        if ($artefact->getImageUrl()) {
            $file = $this->getParameter('kernel.project_dir') . '/public' . $artefact->getImageUrl();
            if (file_exists($file)) unlink($file);
        }

        $em->remove($artefact);
        $em->flush();

        $this->addFlash('success', 'Artefact supprimé avec succès!');
        return $this->redirectToRoute('artefact_index');
    }
}
