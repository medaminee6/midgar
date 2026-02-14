<?php

namespace App\Controller;

use App\Entity\Oeuvre;
<<<<<<< HEAD
use App\Repository\OeuvreRepository;
=======
use App\Entity\User;
use App\Repository\OeuvreRepository;
use App\Repository\CommentaireRepository;
use App\Repository\UserRepository;
>>>>>>> validation-final-2
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
<<<<<<< HEAD
=======
use Symfony\Component\Validator\Validator\ValidatorInterface;
>>>>>>> validation-final-2

#[Route('/oeuvre', name: 'oeuvre_')]
class OeuvreController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, OeuvreRepository $repository): Response
    {
        $search = $request->query->get('search', '');
        $type = $request->query->get('type', '');
        $myItems = $request->query->get('my_items', 0);
<<<<<<< HEAD
        
        $queryBuilder = $repository->createQueryBuilder('o');
        
        // Filter by current user if "my items" is selected
        if ($myItems) {
            $currentUser = $request->getSession()->get('username') ?? 'anonymous';
            $queryBuilder->where('o.createdBy = :createdBy')
                ->setParameter('createdBy', $currentUser);
        }
        
=======

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

>>>>>>> validation-final-2
        if ($search) {
            if ($myItems) {
                $queryBuilder->andWhere('o.title LIKE :search');
            } else {
                $queryBuilder->where('o.title LIKE :search');
            }
            $queryBuilder->setParameter('search', '%' . $search . '%');
        }
<<<<<<< HEAD
        
=======

>>>>>>> validation-final-2
        if ($type) {
            if ($search || $myItems) {
                $queryBuilder->andWhere('o.type = :type');
            } else {
                $queryBuilder->where('o.type = :type');
            }
            $queryBuilder->setParameter('type', $type);
        }
<<<<<<< HEAD
        
        // Apply sorting by type
        $queryBuilder->orderBy('o.type', 'ASC');
        
        $oeuvres = $queryBuilder->getQuery()->getResult();
        
        // Get all available types
=======

        $queryBuilder->orderBy('o.type', 'ASC');

        $oeuvres = $queryBuilder->getQuery()->getResult();

>>>>>>> validation-final-2
        $allTypes = $repository->createQueryBuilder('o')
            ->select('DISTINCT o.type')
            ->orderBy('o.type', 'ASC')
            ->getQuery()
            ->getResult();
        $types = array_map(fn($row) => $row['type'], $allTypes);
<<<<<<< HEAD
        
        $currentUser = $request->getSession()->get('username');
        
=======

        $currentUser = $request->getSession()->get('username');

>>>>>>> validation-final-2
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
<<<<<<< HEAD
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $errors = [];
=======
    public function create(Request $request, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        $oeuvre = new Oeuvre();
        $errors = [];
        $oldValues = [];

        if ($request->isMethod('POST')) {
>>>>>>> validation-final-2
            $title = $request->request->get('title');
            $type = $request->request->get('type');
            $description = $request->request->get('description');
            $author = $request->request->get('author');
<<<<<<< HEAD
            $author = $request->request->get('author');
            $dateStr = $request->request->get('datePublication');
            
            // Validation
            if (!$title || strlen($title) < 2) {
                $errors[] = 'Le titre doit contenir au moins 2 caractères';
            }
            if ($title && !preg_match('/^\p{L}[\p{L}\s\'\-\x{2019}]*$/u', $title)) {
                $errors[] = 'Le titre ne doit contenir que des lettres (espaces, tirets et apostrophes autorisés)';
            }
            if ($author && !preg_match('/^\p{L}[\p{L}\s\'\-\x{2019}]*$/u', $author)) {
                $errors[] = 'Le nom de l\'auteur ne doit contenir que des lettres (espaces, tirets et apostrophes autorisés)';
            }
            if (!$type) {
                $errors[] = 'Le type est requis';
            }
            if (!$description || strlen($description) < 10) {
                $errors[] = 'La description doit contenir au moins 10 caractères';
            }
            if ($description && !preg_match('/^\p{L}[\p{L}0-9\s\-\.,!?\'\\x{2019}]*$/u', $description)) {
                $errors[] = 'La description doit commencer par une lettre et contenir que des lettres, chiffres et espaces';
            }
            // Accept either an uploaded file or a drawn image (base64)
            $drawnImage = $request->request->get('drawnImage');
            $uploadedImage = $request->files->get('image');
            if (!$uploadedImage && !$drawnImage) {
                $errors[] = 'L\'image est requise (téléchargement ou dessin)';
            }
            if ($uploadedImage) {
                $validExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                $extension = strtolower(pathinfo($uploadedImage->getClientOriginalName(), PATHINFO_EXTENSION));
                if (!in_array($extension, $validExtensions)) {
                    $errors[] = 'Les formats autorisés sont: jpg, png, webp, gif';
                }
            } else if ($drawnImage) {
                if (!preg_match('/^data:image\/(\w+);base64,/', $drawnImage, $m)) {
                    $errors[] = 'Image dessinée invalide';
                } else {
                    $ext = strtolower($m[1]);
                    $ext = $ext === 'jpeg' ? 'jpg' : $ext;
                    $validExtensions = ['jpg','jpeg','png','webp','gif'];
                    if (!in_array($ext, $validExtensions)) {
                        $errors[] = 'Le dessin doit produire une image au format PNG/JPG';
                    }
                }
            }
            
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->redirectToRoute('oeuvre_create');
            }
            
            $oeuvre = new Oeuvre();
            $oeuvre->setTitle($title);
            $oeuvre->setType($type);
            $oeuvre->setDescription($description);
            $oeuvre->setAuthor($author);
            $oeuvre->setCreatedBy($request->getSession()->get('username') ?? 'anonymous');
            
            $dateStr = $request->request->get('datePublication');
            if ($dateStr) {
                $oeuvre->setDatePublication(new \DateTimeImmutable($dateStr));
            }

            // Save uploaded file or the drawn image
            if ($uploadedImage) {
                $originalName = $uploadedImage->getClientOriginalName();
                $extension = pathinfo($originalName, PATHINFO_EXTENSION);
                $filename = uniqid() . '.' . $extension;
                $uploadedImage->move($this->getParameter('uploads_directory'), $filename);
                $oeuvre->setImageUrl('/uploads/' . $filename);
            } elseif ($drawnImage) {
                // data:image/png;base64,....
                if (preg_match('/^data:image\/(\w+);base64,/', $drawnImage, $m)) {
                    $data = substr($drawnImage, strpos($drawnImage, ',') + 1);
                    $data = base64_decode($data);
                    $ext = strtolower($m[1]);
                    $ext = $ext === 'jpeg' ? 'jpg' : $ext;
                    $filename = uniqid() . '.' . $ext;
                    $target = rtrim($this->getParameter('uploads_directory'), '\\/') . DIRECTORY_SEPARATOR . $filename;
                    file_put_contents($target, $data);
                    $oeuvre->setImageUrl('/uploads/' . $filename);
                }
            }

            $em->persist($oeuvre);
            $em->flush();

            $this->addFlash('success', 'Œuvre créée avec succès!');
            return $this->redirectToRoute('oeuvre_index');
        }

        return $this->render('oeuvre/create.html.twig');
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Oeuvre $oeuvre): Response
    {
        return $this->render('oeuvre/show.html.twig', [
            'oeuvre' => $oeuvre,
=======
            $dateStr = $request->request->get('datePublication');

            // Store old values to repopulate form
            $oldValues = [
                'title' => $title,
                'type' => $type,
                'description' => $description,
                'author' => $author,
                'datePublication' => $dateStr,
            ];

            $oeuvre->setTitle($title ?? '');
            $oeuvre->setType($type ?? '');
            $oeuvre->setDescription($description ?? '');
            $oeuvre->setAuthor($author);

            $errors = $validator->validate($oeuvre);

            // Validate image (either uploaded or drawn)
            $drawnImage = $request->request->get('drawnImage');
            $uploadedImage = $request->files->get('image');
            
            if (!$uploadedImage && !$drawnImage) {
                $error = new \Symfony\Component\Validator\ConstraintViolation(
                    "L'image est requise (téléchargement ou dessin)",
                    null,
                    [],
                    $oeuvre,
                    'image',
                    null
                );
                $errors->add($error);
            } else {
                if ($uploadedImage) {
                    $validExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                    $extension = strtolower(pathinfo($uploadedImage->getClientOriginalName(), PATHINFO_EXTENSION));
                    if (!in_array($extension, $validExtensions)) {
                        $error = new \Symfony\Component\Validator\ConstraintViolation(
                            "Les formats autorisés sont: jpg, png, webp, gif",
                            null,
                            [],
                            $oeuvre,
                            'image',
                            null
                        );
                        $errors->add($error);
                    }
                } elseif ($drawnImage) {
                    if (!preg_match('/^data:image\/(\w+);base64,/', $drawnImage, $m)) {
                        $error = new \Symfony\Component\Validator\ConstraintViolation(
                            "Image dessinée invalide",
                            null,
                            [],
                            $oeuvre,
                            'drawnImage',
                            null
                        );
                        $errors->add($error);
                    } else {
                        $ext = strtolower($m[1]);
                        $ext = $ext === 'jpeg' ? 'jpg' : $ext;
                        $validExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                        if (!in_array($ext, $validExtensions)) {
                            $error = new \Symfony\Component\Validator\ConstraintViolation(
                                "Le dessin doit produire une image au format PNG/JPG",
                                null,
                                [],
                                $oeuvre,
                                'drawnImage',
                                null
                            );
                            $errors->add($error);
                        }
                    }
                }
            }

            if (count($errors) === 0) {
                $currentUser = $this->getUser();
                if ($currentUser instanceof User) {
                    $oeuvre->setCreatedBy($currentUser);
                } else {
                    $oeuvre->setCreatedBy(null);
                }

                if ($dateStr) {
                    $oeuvre->setDatePublication(new \DateTimeImmutable($dateStr));
                }

                if ($uploadedImage) {
                    $originalName = $uploadedImage->getClientOriginalName();
                    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
                    $filename = uniqid() . '.' . $extension;
                    $uploadedImage->move($this->getParameter('uploads_directory'), $filename);
                    $oeuvre->setImageUrl('/uploads/' . $filename);
                } elseif ($drawnImage) {
                    if (preg_match('/^data:image\/(\w+);base64,/', $drawnImage, $m)) {
                        $data = substr($drawnImage, strpos($drawnImage, ',') + 1);
                        $data = base64_decode($data);
                        $ext = strtolower($m[1]);
                        $ext = $ext === 'jpeg' ? 'jpg' : $ext;
                        $filename = uniqid() . '.' . $ext;
                        $target = rtrim($this->getParameter('uploads_directory'), '\\/') . DIRECTORY_SEPARATOR . $filename;
                        file_put_contents($target, $data);
                        $oeuvre->setImageUrl('/uploads/' . $filename);
                    }
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
        
        // Fetch user names for comments
        $userNames = [];
        foreach ($commentaires as $commentaire) {
            $user = $userRepo->find($commentaire->getUserId());
            $userNames[$commentaire->getUserId()] = $user ? ($user->getPrenom() . ' ' . $user->getNom()) : 'Utilisateur';
        }
        
        return $this->render('oeuvre/show.html.twig', [
            'oeuvre' => $oeuvre,
            'commentaires' => $commentaires,
            'userNames' => $userNames,
>>>>>>> validation-final-2
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
<<<<<<< HEAD
    public function edit(Request $request, Oeuvre $oeuvre, EntityManagerInterface $em): Response
    {
        $author = null;
        if ($request->isMethod('POST')) {
            $errors = [];
            $title = $request->request->get('title');
            $type = $request->request->get('type');
            $description = $request->request->get('description');
            
            // Validation
            if (!$title || strlen($title) < 2) {
                $errors[] = 'Le titre doit contenir au moins 2 caractères';
            }
            if ($title && !preg_match('/^[a-zA-ZÀ-ÿ\s\-\']+$/u', $title)) {
                $errors[] = 'Le titre ne doit contenir que des lettres (espaces, tirets et apostrophes autorisés)';
            }
            if (!$type) {
                $errors[] = 'Le type est requis';
            }
            if (!$description || strlen($description) < 10) {
                $errors[] = 'La description doit contenir au moins 10 caractères';
            }
            if ($description && !preg_match('/^[a-zA-ZÀ-ÿ][a-zA-ZÀ-ÿ0-9\s\-,.!?\']*$/u', $description)) {
                $errors[] = 'La description doit commencer par une lettre et contenir que des lettres, chiffres et espaces';
            }
            
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->redirectToRoute('oeuvre_edit', ['id' => $oeuvre->getId()]);
            }
            
            $oeuvre->setTitle($title);
            $oeuvre->setType($type);
            $oeuvre->setDescription($description);
            $oeuvre->setAuthor($author);

            $dateStr = $request->request->get('datePublication');
            if ($dateStr) {
                $oeuvre->setDatePublication(new \DateTimeImmutable($dateStr));
            }

            $imageFile = $request->files->get('image');
            if ($imageFile) {
                $validExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                $extension = strtolower(pathinfo($imageFile->getClientOriginalName(), PATHINFO_EXTENSION));
                if (!in_array($extension, $validExtensions)) {
                    $this->addFlash('error', 'Les formats autorisés sont: jpg, png, webp, gif');
                    return $this->redirectToRoute('oeuvre_edit', ['id' => $oeuvre->getId()]);
                }
                if ($oeuvre->getImageUrl()) {
                    $oldFile = $this->getParameter('kernel.project_dir') . '/public' . $oeuvre->getImageUrl();
                    if (file_exists($oldFile)) {
                        unlink($oldFile);
                    }
                }
                $originalName = $imageFile->getClientOriginalName();
                $extension = pathinfo($originalName, PATHINFO_EXTENSION);
                $filename = uniqid() . '.' . $extension;
                $imageFile->move($this->getParameter('uploads_directory'), $filename);
                $oeuvre->setImageUrl('/uploads/' . $filename);
            }

            $em->flush();
            $this->addFlash('success', 'Œuvre modifiée avec succès!');
            return $this->redirectToRoute('oeuvre_show', ['id' => $oeuvre->getId()]);
=======
    public function edit(Request $request, Oeuvre $oeuvre, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        $currentUser = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $owner = $oeuvre->getCreatedBy();
        $isOwner = false;
        if ($currentUser instanceof User) {
            if ($owner instanceof User) {
                $isOwner = $owner->getId() === $currentUser->getId();
            }
        } else {
            $sessionUser = $request->getSession()->get('username') ?? null;
            $isOwner = $owner === $sessionUser;
        }
        if (!$isOwner && !$isAdmin) {
            $this->addFlash('error', "Vous n etes pas autorisé à modifier cette œuvre.");
            return $this->redirectToRoute('oeuvre_show', ['id' => $oeuvre->getId()]);
        }

        $errors = [];
        $oldValues = [];

        if ($request->isMethod('POST')) {
            $title = $request->request->get('title');
            $type = $request->request->get('type');
            $description = $request->request->get('description');

            // Store old values to repopulate form
            $oldValues = [
                'title' => $title,
                'type' => $type,
                'description' => $description,
            ];

            $oeuvre->setTitle($title ?? '');
            $oeuvre->setType($type ?? '');
            $oeuvre->setDescription($description ?? '');

            $errors = $validator->validate($oeuvre);

            if (count($errors) === 0) {
                $dateStr = $request->request->get('datePublication');
                if ($dateStr) {
                    $oeuvre->setDatePublication(new \DateTimeImmutable($dateStr));
                }

                $imageFile = $request->files->get('image');
                if ($imageFile) {
                    $validExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                    $extension = strtolower(pathinfo($imageFile->getClientOriginalName(), PATHINFO_EXTENSION));
                    if (!in_array($extension, $validExtensions)) {
                        $this->addFlash('error', "Les formats autorisés sont: jpg, png, webp, gif");
                        return $this->render('oeuvre/edit.html.twig', [
                            'oeuvre' => $oeuvre,
                            'errors' => $errors,
                            'oldValues' => $oldValues,
                        ]);
                    }
                    if ($oeuvre->getImageUrl()) {
                        $oldFile = $this->getParameter('kernel.project_dir') . '/public' . $oeuvre->getImageUrl();
                        if (file_exists($oldFile)) {
                            unlink($oldFile);
                        }
                    }
                    $originalName = $imageFile->getClientOriginalName();
                    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
                    $filename = uniqid() . '.' . $extension;
                    $imageFile->move($this->getParameter('uploads_directory'), $filename);
                    $oeuvre->setImageUrl('/uploads/' . $filename);
                }

                $em->flush();
                $this->addFlash('success', 'Œuvre modifiée avec succès!');
                return $this->redirectToRoute('oeuvre_show', ['id' => $oeuvre->getId()]);
            }
>>>>>>> validation-final-2
        }

        return $this->render('oeuvre/edit.html.twig', [
            'oeuvre' => $oeuvre,
<<<<<<< HEAD
=======
            'errors' => $errors,
            'oldValues' => $oldValues,
>>>>>>> validation-final-2
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Oeuvre $oeuvre, EntityManagerInterface $em): Response
    {
<<<<<<< HEAD
=======
        $currentUser = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $owner = $oeuvre->getCreatedBy();
        $isOwner = false;
        if ($currentUser instanceof User) {
            if ($owner instanceof User) {
                $isOwner = $owner->getId() === $currentUser->getId();
            }
        } else {
            $sessionUser = $request->getSession()->get('username') ?? null;
            $isOwner = $owner === $sessionUser;
        }
        if (!$isOwner && !$isAdmin) {
            $this->addFlash('error', "Vous n etes pas autorisé à supprimer cette œuvre.");
            return $this->redirectToRoute('oeuvre_show', ['id' => $oeuvre->getId()]);
        }

>>>>>>> validation-final-2
        if ($oeuvre->getImageUrl()) {
            $file = $this->getParameter('kernel.project_dir') . '/public' . $oeuvre->getImageUrl();
            if (file_exists($file)) {
                unlink($file);
            }
        }

        $em->remove($oeuvre);
        $em->flush();
        $this->addFlash('success', 'Œuvre supprimée avec succès!');

        return $this->redirectToRoute('oeuvre_index');
    }
}
