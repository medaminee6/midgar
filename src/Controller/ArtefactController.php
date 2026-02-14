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

        $currentUser = $this->getUser();
        if ($myItems && $currentUser instanceof User) {
            $currentUserId = $currentUser->getId();
            $artefacts = array_values(array_filter($artefacts, function ($a) use ($currentUserId) {
                $owner = $a->getCreatedBy();
                if ($owner instanceof User) {
                    return $owner->getId() === $currentUserId;
                }
                return false;
            }));
        }

        $allTypes = $repository->createQueryBuilder('a')
            ->select('DISTINCT a.type')
            ->orderBy('a.type', 'ASC')
            ->getQuery()
            ->getResult();
        $types = array_map(fn($row) => $row['type'], $allTypes);

        $sessionUser = $request->getSession()->get('username') ?? null;
        $currentUserIdentifier = $currentUser ? (method_exists($currentUser, 'getUserIdentifier') ? $currentUser->getUserIdentifier() : null) : $sessionUser;

        return $this->render('artefact/index.html.twig', [
            'artefacts' => $artefacts,
            'search' => $search,
            'type' => $type,
            'types' => $types,
            'myItems' => $myItems,
            'currentUser' => $currentUserIdentifier,
        ]);
    }

    #[Route('/create', name: 'create', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        $artefact = new Artefact();
        $errors = [];
        $oldValues = [];

        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $type = $request->request->get('type');
            $universe = $request->request->get('universe');
            $origins = $request->request->get('origins');
            $powers = $request->request->get('powers');
            $rarity = $request->request->get('rarity');

            // Store old values to repopulate form
            $oldValues = [
                'name' => $name,
                'type' => $type,
                'universe' => $universe,
                'origins' => $origins,
                'powers' => $powers,
                'rarity' => $rarity,
            ];

            $artefact->setName($name ?? '');
            $artefact->setType($type ?? '');
            $artefact->setUniverse($universe ?? '');
            $artefact->setOrigins($origins ?? '');
            $artefact->setPowers($powers ?? '');
            $artefact->setRarity($rarity ?? '');

            $errors = $validator->validate($artefact);

            // Validate image
            $imageFile = $request->files->get('image');
            if (!$imageFile) {
                $error = new \Symfony\Component\Validator\ConstraintViolation(
                    "L'image est requise",
                    null,
                    [],
                    $artefact,
                    'image',
                    null
                );
                $errors->add($error);
            } else {
                $validExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                $extension = strtolower(pathinfo($imageFile->getClientOriginalName(), PATHINFO_EXTENSION));
                if (!in_array($extension, $validExtensions)) {
                    $error = new \Symfony\Component\Validator\ConstraintViolation(
                        "Les formats autorisés sont: jpg, png, webp, gif",
                        null,
                        [],
                        $artefact,
                        'image',
                        null
                    );
                    $errors->add($error);
                }
            }

            if (count($errors) === 0) {
                $currentUser = $this->getUser();
                if ($currentUser instanceof User) {
                    $artefact->setCreatedBy($currentUser);
                } else {
                    $artefact->setCreatedBy(null);
                }

                if ($imageFile) {
                    $originalName = $imageFile->getClientOriginalName();
                    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
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
        
        // Fetch user names for comments
        $userNames = [];
        foreach ($commentaires as $commentaire) {
            $user = $userRepo->find($commentaire->getUserId());
            $userNames[$commentaire->getUserId()] = $user ? ($user->getPrenom() . ' ' . $user->getNom()) : 'Utilisateur';
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
        $isOwner = false;
        if ($currentUser instanceof User) {
            if ($owner instanceof User) {
                $isOwner = $owner->getId() === $currentUser->getId();
            } else {
                $identifier = method_exists($currentUser, 'getUserIdentifier') ? $currentUser->getUserIdentifier() : null;
                $isOwner = $owner === $identifier;
            }
        } else {
            $sessionUser = $request->getSession()->get('username') ?? null;
            $isOwner = $owner === $sessionUser;
        }
        if (!$isOwner && !$isAdmin) {
            $this->addFlash('error', "Vous n etes pas autorisé à modifier cet artefact.");
            return $this->redirectToRoute('artefact_show', ['id' => $artefact->getId()]);
        }

        $errors = [];
        $oldValues = [];

        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $type = $request->request->get('type');
            $universe = $request->request->get('universe');
            $origins = $request->request->get('origins');
            $powers = $request->request->get('powers');
            $rarity = $request->request->get('rarity');

            // Store old values to repopulate form
            $oldValues = [
                'name' => $name,
                'type' => $type,
                'universe' => $universe,
                'origins' => $origins,
                'powers' => $powers,
                'rarity' => $rarity,
            ];

            $artefact->setName($name ?? '');
            $artefact->setType($type ?? '');
            $artefact->setUniverse($universe ?? '');
            $artefact->setOrigins($origins ?? '');
            $artefact->setPowers($powers ?? '');
            $artefact->setRarity($rarity ?? '');

            $errors = $validator->validate($artefact);

            if (count($errors) === 0) {
                $imageFile = $request->files->get('image');
                if ($imageFile) {
                    $validExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                    $extension = strtolower(pathinfo($imageFile->getClientOriginalName(), PATHINFO_EXTENSION));
                    if (!in_array($extension, $validExtensions)) {
                        $this->addFlash('error', "Les formats autorisés sont: jpg, png, webp, gif");
                        return $this->render('artefact/edit.html.twig', [
                            'artefact' => $artefact,
                            'errors' => $errors,
                            'oldValues' => $oldValues,
                        ]);
                    }
                    if ($artefact->getImageUrl()) {
                        $oldFile = $this->getParameter('kernel.project_dir') . '/public' . $artefact->getImageUrl();
                        if (file_exists($oldFile)) {
                            unlink($oldFile);
                        }
                    }
                    $originalName = $imageFile->getClientOriginalName();
                    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
                    $filename = uniqid() . '.' . $extension;
                    $imageFile->move($this->getParameter('uploads_directory'), $filename);
                    $artefact->setImageUrl('/uploads/' . $filename);
                }

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
        $isOwner = false;
        if ($currentUser instanceof User) {
            if ($owner instanceof User) {
                $isOwner = $owner->getId() === $currentUser->getId();
            } else {
                $identifier = method_exists($currentUser, 'getUserIdentifier') ? $currentUser->getUserIdentifier() : null;
                $isOwner = $owner === $identifier;
            }
        } else {
            $sessionUser = $request->getSession()->get('username') ?? null;
            $isOwner = $owner === $sessionUser;
        }
        if (!$isOwner && !$isAdmin) {
            $this->addFlash('error', "Vous n etes pas autorisé à supprimer cet artefact.");
            return $this->redirectToRoute('artefact_show', ['id' => $artefact->getId()]);
        }

        if ($artefact->getImageUrl()) {
            $file = $this->getParameter('kernel.project_dir') . '/public' . $artefact->getImageUrl();
            if (file_exists($file)) {
                unlink($file);
            }
        }

        $em->remove($artefact);
        $em->flush();
        $this->addFlash('success', 'Artefact supprimé avec succès!');

        return $this->redirectToRoute('artefact_index');
    }
}
