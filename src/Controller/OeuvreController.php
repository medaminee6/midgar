<?php

namespace App\Controller;

use App\Entity\Oeuvre;
use App\Entity\User;
use App\Repository\OeuvreRepository;
use App\Repository\CommentaireRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/oeuvre', name: 'oeuvre_')]
class OeuvreController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, OeuvreRepository $repository): Response
    {
        $search = $request->query->get('search', '');
        $type = $request->query->get('type', '');
        $myItems = $request->query->get('my_items', 0);

        $queryBuilder = $repository->createQueryBuilder('o');

        // Filter by current user if "my items" is selected
        if ($myItems) {
            $currentUser = $this->getUser();
            if ($currentUser instanceof User) {
                $queryBuilder->where('o.createdBy = :createdBy')
                    ->setParameter('createdBy', $currentUser);
            } else {
                $sessionUser = $request->getSession()->get('username') ?? 'anonymous';
                $queryBuilder->where('o.createdBy = :createdBy')
                    ->setParameter('createdBy', $sessionUser);
            }
        }

        if ($search) {
            if ($myItems) {
                $queryBuilder->andWhere('o.title LIKE :search');
            } else {
                $queryBuilder->where('o.title LIKE :search');
            }
            $queryBuilder->setParameter('search', '%' . $search . '%');
        }

        if ($type) {
            if ($search || $myItems) {
                $queryBuilder->andWhere('o.type = :type');
            } else {
                $queryBuilder->where('o.type = :type');
            }
            $queryBuilder->setParameter('type', $type);
        }

        $queryBuilder->orderBy('o.type', 'ASC');
        $oeuvres = $queryBuilder->getQuery()->getResult();

        $allTypes = $repository->createQueryBuilder('o')
            ->select('DISTINCT o.type')
            ->orderBy('o.type', 'ASC')
            ->getQuery()
            ->getResult();
        $types = array_map(fn($row) => $row['type'], $allTypes);

        $currentUser = $request->getSession()->get('username');

        return $this->render('oeuvre/index.html.twig', [
            'oeuvres' => $oeuvres,
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
        $oeuvre = new Oeuvre();
        $errors = [];
        $oldValues = [];

        if ($request->isMethod('POST')) {
            $title = $request->request->get('title');
            $type = $request->request->get('type');
            $description = $request->request->get('description');
            $author = $request->request->get('author');
            $dateStr = $request->request->get('datePublication');
            $tag = $request->request->get('tag');

            $oldValues = [
                'title' => $title,
                'type' => $type,
                'description' => $description,
                'author' => $author,
                'datePublication' => $dateStr,
                'tag' => $tag,
            ];

            $oeuvre->setTitle($title ?? '');
            $oeuvre->setType($type ?? '');
            $oeuvre->setDescription($description ?? '');
            $oeuvre->setAuthor($author);
            $oeuvre->setTag($tag ?? '');

            $errors = $validator->validate($oeuvre);

            $drawnImage = $request->request->get('drawnImage');
            $uploadedImage = $request->files->get('image');

            if (!$uploadedImage && !$drawnImage) {
                $errors[] = "L'image est requise (téléchargement ou dessin)";
            } elseif ($uploadedImage) {
                $validExtensions = ['jpg','jpeg','png','webp','gif'];
                $extension = strtolower(pathinfo($uploadedImage->getClientOriginalName(), PATHINFO_EXTENSION));
                if (!in_array($extension, $validExtensions)) {
                    $errors[] = 'Les formats autorisés sont: jpg, png, webp, gif';
                }
            } elseif ($drawnImage) {
                if (!preg_match('/^data:image\/(\w+);base64,/', $drawnImage, $m)) {
                    $errors[] = 'Image dessinée invalide';
                }
            }

            if (count($errors) === 0) {
                $currentUser = $this->getUser();
                $oeuvre->setCreatedBy($currentUser instanceof User ? $currentUser : null);

                if ($dateStr) {
                    $oeuvre->setDatePublication(new \DateTimeImmutable($dateStr));
                }

                if ($uploadedImage) {
                    $filename = uniqid() . '.' . pathinfo($uploadedImage->getClientOriginalName(), PATHINFO_EXTENSION);
                    $uploadedImage->move($this->getParameter('uploads_directory'), $filename);
                    $oeuvre->setImageUrl('/uploads/' . $filename);
                } elseif ($drawnImage) {
                    $data = substr($drawnImage, strpos($drawnImage, ',') + 1);
                    $data = base64_decode($data);
                    $ext = strtolower($m[1]) === 'jpeg' ? 'jpg' : strtolower($m[1]);
                    $filename = uniqid() . '.' . $ext;
                    file_put_contents($this->getParameter('uploads_directory') . '/' . $filename, $data);
                    $oeuvre->setImageUrl('/uploads/' . $filename);
                }

                $em->persist($oeuvre);
                $em->flush();

                $this->addFlash('success', 'Œuvre créée avec succès!');
                return $this->redirectToRoute('oeuvre_index');
            }
        }

        return $this->render('oeuvre/create.html.twig', [
            'oeuvre' => $oeuvre,
            'errors' => $errors,
            'oldValues' => $oldValues,
            'currentUser' => $this->getUser(),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Oeuvre $oeuvre, CommentaireRepository $commentaireRepo, UserRepository $userRepo): Response
    {
        $commentaires = $commentaireRepo->findByOeuvre($oeuvre->getId());

        $userNames = [];
        foreach ($commentaires as $commentaire) {
            $user = $userRepo->find($commentaire->getUserId());
            $userNames[$commentaire->getUserId()] = $user ? ($user->getPrenom() . ' ' . $user->getNom()) : 'Utilisateur';
        }

        return $this->render('oeuvre/show.html.twig', [
            'oeuvre' => $oeuvre,
            'commentaires' => $commentaires,
            'userNames' => $userNames,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Oeuvre $oeuvre, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        $currentUser = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $owner = $oeuvre->getCreatedBy();
        $isOwner = false;

        if ($currentUser instanceof User && $owner instanceof User) {
            $isOwner = $owner->getId() === $currentUser->getId();
        } else {
            $sessionUser = $request->getSession()->get('username') ?? null;
            $isOwner = $owner === $sessionUser;
        }

        if (!$isOwner && !$isAdmin) {
            $this->addFlash('error', "Vous n'êtes pas autorisé à modifier cette œuvre.");
            return $this->redirectToRoute('oeuvre_show', ['id' => $oeuvre->getId()]);
        }

        $errors = [];
        $oldValues = [];

        if ($request->isMethod('POST')) {
            $title = $request->request->get('title');
            $type = $request->request->get('type');
            $description = $request->request->get('description');
            $tag = $request->request->get('tag');

            $oldValues = ['title'=>$title,'type'=>$type,'description'=>$description,'tag'=>$tag];

            $oeuvre->setTitle($title ?? '');
            $oeuvre->setType($type ?? '');
            $oeuvre->setDescription($description ?? '');
            $oeuvre->setTag($tag ?? '');

            $errors = $validator->validate($oeuvre);

            if (count($errors) === 0) {
                $dateStr = $request->request->get('datePublication');
                if ($dateStr) {
                    $oeuvre->setDatePublication(new \DateTimeImmutable($dateStr));
                }

                $imageFile = $request->files->get('image');
                if ($imageFile) {
                    $ext = strtolower(pathinfo($imageFile->getClientOriginalName(), PATHINFO_EXTENSION));
                    $validExtensions = ['jpg','jpeg','png','webp','gif'];
                    if (!in_array($ext, $validExtensions)) {
                        $this->addFlash('error', "Les formats autorisés sont: jpg, png, webp, gif");
                        return $this->render('oeuvre/edit.html.twig', ['oeuvre'=>$oeuvre,'errors'=>$errors,'oldValues'=>$oldValues]);
                    }
                    if ($oeuvre->getImageUrl()) {
                        $oldFile = $this->getParameter('kernel.project_dir') . '/public' . $oeuvre->getImageUrl();
                        if (file_exists($oldFile)) unlink($oldFile);
                    }
                    $filename = uniqid() . '.' . $ext;
                    $imageFile->move($this->getParameter('uploads_directory'), $filename);
                    $oeuvre->setImageUrl('/uploads/' . $filename);
                }

                $em->flush();
                $this->addFlash('success', 'Œuvre modifiée avec succès!');
                return $this->redirectToRoute('oeuvre_show', ['id' => $oeuvre->getId()]);
            }
        }

        return $this->render('oeuvre/edit.html.twig', [
            'oeuvre' => $oeuvre,
            'errors' => $errors,
            'oldValues' => $oldValues,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Oeuvre $oeuvre, EntityManagerInterface $em): Response
    {
        $currentUser = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $owner = $oeuvre->getCreatedBy();
        $isOwner = false;

        if ($currentUser instanceof User && $owner instanceof User) {
            $isOwner = $owner->getId() === $currentUser->getId();
        } else {
            $sessionUser = $request->getSession()->get('username') ?? null;
            $isOwner = $owner === $sessionUser;
        }

        if (!$isOwner && !$isAdmin) {
            $this->addFlash('error', "Vous n'êtes pas autorisé à supprimer cette œuvre.");
            return $this->redirectToRoute('oeuvre_show', ['id' => $oeuvre->getId()]);
        }

        if ($oeuvre->getImageUrl()) {
            $file = $this->getParameter('kernel.project_dir') . '/public' . $oeuvre->getImageUrl();
            if (file_exists($file)) unlink($file);
        }

        $em->remove($oeuvre);
        $em->flush();
        $this->addFlash('success', 'Œuvre supprimée avec succès!');

        return $this->redirectToRoute('oeuvre_index');
    }
}
