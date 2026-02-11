<?php

namespace App\Controller;

use App\Entity\Artefact;
use App\Entity\User;
use App\Repository\ArtefactRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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
        if ($myItems && $currentUser) {
            $artefacts = array_values(array_filter($artefacts, function ($a) use ($currentUser) {
                $owner = $a->getCreatedBy();
                if ($owner instanceof User) {
                    return $owner->getId() === $currentUser->getId();
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
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $errors = [];
            $name = $request->request->get('name');
            $type = $request->request->get('type');
            $universe = $request->request->get('universe');
            $origins = $request->request->get('origins');
            $powers = $request->request->get('powers');
            $rarity = $request->request->get('rarity');

            if (!$name || strlen($name) < 3) {
                $errors[] = "Le nom doit contenir au moins 3 caractères";
            }
            if ($name && !preg_match('/^\p{L}[\p{L}\s\'\-\x{2019}]*$/u', $name)) {
                $errors[] = "Le nom ne doit contenir que des lettres (espaces, tirets et apostrophes autorisés)";
            }
            if (!$type) {
                $errors[] = "Le type est requis";
            }
            if (!$universe || strlen($universe) < 2) {
                $errors[] = "L univers est requis";
            }
            if (!$origins || strlen($origins) < 10) {
                $errors[] = "Les origines doivent contenir au moins 10 caractères";
            }
            if ($origins && !preg_match('/^\p{L}[\p{L}0-9\s\-\.,!?\'\\x{2019}]*$/u', $origins)) {
                $errors[] = "Les origines doivent commencer par une lettre et contenir que des lettres et chiffres";
            }
            if (!$powers || strlen($powers) < 10) {
                $errors[] = "Les pouvoirs doivent contenir au moins 10 caractères";
            }
            if ($powers && !preg_match('/^\p{L}[\p{L}0-9\s\-\.,!?\'\\x{2019}]*$/u', $powers)) {
                $errors[] = "Les pouvoirs doivent commencer par une lettre et contenir que des lettres et chiffres";
            }
            if (!$rarity) {
                $errors[] = "La rareté est requise";
            }
            if (!$request->files->get('image')) {
                $errors[] = "L image est requise";
            } else {
                $validExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                $extension = strtolower(pathinfo($request->files->get('image')->getClientOriginalName(), PATHINFO_EXTENSION));
                if (!in_array($extension, $validExtensions)) {
                    $errors[] = "Les formats autorisés sont: jpg, png, webp, gif";
                }
            }

            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->redirectToRoute('artefact_create');
            }

            $artefact = new Artefact();
            $artefact->setName($name);
            $artefact->setType($type);
            $artefact->setUniverse($universe);
            $artefact->setOrigins($origins);
            $artefact->setPowers($powers);
            $artefact->setRarity($rarity);
            $currentUser = $this->getUser();
            if ($currentUser instanceof User) {
                $artefact->setCreatedBy($currentUser);
            } else {
                $artefact->setCreatedBy(null);
            }

            $imageFile = $request->files->get('image');
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

        return $this->render('artefact/create.html.twig');
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Artefact $artefact): Response
    {
        return $this->render('artefact/show.html.twig', [
            'artefact' => $artefact,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Artefact $artefact, EntityManagerInterface $em): Response
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

        if ($request->isMethod('POST')) {
            $errors = [];
            $name = $request->request->get('name');
            $type = $request->request->get('type');
            $universe = $request->request->get('universe');
            $origins = $request->request->get('origins');
            $powers = $request->request->get('powers');
            $rarity = $request->request->get('rarity');

            if (!$name || strlen($name) < 3) {
                $errors[] = "Le nom doit contenir au moins 3 caractères";
            }
            if ($name && !preg_match('/^[a-zA-ZÀ-ÖØ-öø-ÿ\s\-\']+$/u', $name)) {
                $errors[] = "Le nom ne doit contenir que des lettres (espaces, tirets et apostrophes autorisés)";
            }
            if (!$type) {
                $errors[] = "Le type est requis";
            }
            if (!$universe || strlen($universe) < 2) {
                $errors[] = "L univers est requis";
            }
            if (!$origins || strlen($origins) < 10) {
                $errors[] = "Les origines doivent contenir au moins 10 caractères";
            }
            if (!$powers || strlen($powers) < 10) {
                $errors[] = "Les pouvoirs doivent contenir au moins 10 caractères";
            }
            if (!$rarity) {
                $errors[] = "La rareté est requise";
            }

            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->redirectToRoute('artefact_edit', ['id' => $artefact->getId()]);
            }

            $artefact->setName($name);
            $artefact->setType($type);
            $artefact->setUniverse($universe);
            $artefact->setOrigins($origins);
            $artefact->setPowers($powers);
            $artefact->setRarity($rarity);

            $imageFile = $request->files->get('image');
            if ($imageFile) {
                $validExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                $extension = strtolower(pathinfo($imageFile->getClientOriginalName(), PATHINFO_EXTENSION));
                if (!in_array($extension, $validExtensions)) {
                    $this->addFlash('error', "Les formats autorisés sont: jpg, png, webp, gif");
                    return $this->redirectToRoute('artefact_edit', ['id' => $artefact->getId()]);
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

        return $this->render('artefact/edit.html.twig', [
            'artefact' => $artefact,
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
