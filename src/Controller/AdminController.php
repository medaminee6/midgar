<?php
namespace App\Controller;

use App\Entity\User;
use App\Entity\Artefact;
use App\Entity\Oeuvre;
use App\Entity\Commentaire;
use App\Repository\CommentaireRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/users', name: 'admin_users')]
    public function users(Request $request, EntityManagerInterface $em): Response
    {
        $q = $request->query->get('q', '');
        $start = $request->query->get('start_date', null);
        $end = $request->query->get('end_date', null);

        $sort = $request->query->get('sort', 'createdAt'); // valeur par défaut
        $direction = strtoupper($request->query->get('direction', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        $allowedSorts = ['username','lastName','firstName','createdAt'];
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'createdAt';
        }

        $repo = $em->getRepository(User::class);
        $qb = $repo->createQueryBuilder('u');

        if ($q) {
            $qb->andWhere('u.username LIKE :q OR u.email LIKE :q')
               ->setParameter('q', '%' . $q . '%');
        }

        if ($start) {
            try {
                $startDt = new \DateTimeImmutable($start);
                $qb->andWhere('u.createdAt >= :start')->setParameter('start', $startDt->setTime(0,0,0));
            } catch (\Exception $e) {}
        }

        if ($end) {
            try {
                $endDt = new \DateTimeImmutable($end);
                $qb->andWhere('u.createdAt <= :end')->setParameter('end', $endDt->setTime(23,59,59));
            } catch (\Exception $e) {}
        }

        $qb->orderBy('u.' . $sort, $direction);

        $users = $qb->getQuery()->getResult();

        return $this->render('admin/users.html.twig', [
            'users' => $users,
            'search' => $q,
            'start_date' => $start,
            'end_date' => $end,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    #[Route('/users/{id}/delete', name: 'admin_user_delete', methods: ['POST'])]
    public function delete(User $user, EntityManagerInterface $em): Response
    {
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            return $this->redirectToRoute('admin_users');
        }

        $em->remove($user);
        $em->flush();

        $this->addFlash('success', 'Utilisateur supprimé avec succès.');
        return $this->redirectToRoute('admin_users');
    }

    #[Route('/users/{id}/toggle-role', name: 'admin_user_toggle_role', methods: ['POST'])]
    public function toggleRole(User $user, EntityManagerInterface $em): Response
    {
        $user->setRole($user->isAdmin() ? 'user' : 'admin');
        $em->flush();

        $this->addFlash('success', 'Rôle de l’utilisateur mis à jour.');
        return $this->redirectToRoute('admin_users');
    }

    #[Route('/users/{id}/block', name: 'admin_user_block', methods: ['POST'])]
    public function block(User $user, EntityManagerInterface $em): Response
    {
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas vous bloquer.');
            return $this->redirectToRoute('admin_users');
        }

        $user->setIsBlocked(!$user->isBlocked());
        $em->flush();

        $this->addFlash('success', 'Statut de blocage mis à jour.');
        return $this->redirectToRoute('admin_users');
    }

    #[Route('/oeuvres', name: 'admin_oeuvres')]
    public function oeuvreIndex(EntityManagerInterface $em, Request $request): Response
    {
        $search = $request->query->get('q', '');
        
        $repo = $em->getRepository(Oeuvre::class);
        $qb = $repo->createQueryBuilder('o')->orderBy('o.createdAt', 'DESC');
        
        if ($search) {
            $qb->andWhere('o.title LIKE :q OR o.author LIKE :q')
               ->setParameter('q', '%' . $search . '%');
        }
        
        $oeuvrse = $qb->getQuery()->getResult();
        
        // Calculate statistics (based on filtered results)
        $totalOeuvres = count($oeuvrse);
        $oeuvrseParType = [];
        foreach ($oeuvrse as $oeuvre) {
            $type = $oeuvre->getType() ?: 'Inconnu';
            if (!isset($oeuvrseParType[$type])) {
                $oeuvrseParType[$type] = 0;
            }
            $oeuvrseParType[$type]++;
        }
        
        // Fetch comments for each oeuvre
        $commentaireRepo = $em->getRepository(Commentaire::class);
        $userRepo = $em->getRepository(User::class);
        $oeuvreComments = [];
        $oeuvreCommentCounts = [];
        $commentUserNames = [];
        
        foreach ($oeuvrse as $oeuvre) {
            $oeuvreId = $oeuvre->getId();
            $comments = $commentaireRepo->findByOeuvre($oeuvreId);
            $oeuvreComments[$oeuvreId] = $comments;
            $oeuvreCommentCounts[$oeuvreId] = count($comments);
            
            // Fetch user names for each comment
            foreach ($comments as $comment) {
                $userId = $comment->getUserId();
                if ($userId && !isset($commentUserNames[$userId])) {
                    $user = $userRepo->find($userId);
                    if ($user) {
                        $commentUserNames[$userId] = $user->getPrenom() . ' ' . $user->getNom();
                    }
                }
            }
        }
        
        return $this->render('admin/oeuvres.html.twig', [
            'oeuvrse' => $oeuvrse,
            'totalOeuvres' => $totalOeuvres,
            'oeuvrseParType' => $oeuvrseParType,
            'search' => $search,
            'oeuvreComments' => $oeuvreComments,
            'oeuvreCommentCounts' => $oeuvreCommentCounts,
            'commentUserNames' => $commentUserNames,
        ]);
    }

    #[Route('/oeuvres/{id}/delete', name: 'admin_oeuvre_delete', methods: ['POST'])]
    public function deleteOeuvre(Oeuvre $oeuvre, EntityManagerInterface $em): Response
    {
        $em->remove($oeuvre);
        $em->flush();

        $this->addFlash('success', 'Œuvre supprimée avec succès.');
        return $this->redirectToRoute('admin_oeuvres');
    }

    #[Route('/artefacts', name: 'admin_artefacts')]
    public function artefactIndex(EntityManagerInterface $em, Request $request): Response
    {
        $search = $request->query->get('q', '');
        
        $repo = $em->getRepository(Artefact::class);
        $qb = $repo->createQueryBuilder('a')->orderBy('a.createdAt', 'DESC');
        
        if ($search) {
            $qb->andWhere('a.name LIKE :q OR a.universe LIKE :q')
               ->setParameter('q', '%' . $search . '%');
        }
        
        $artefact = $qb->getQuery()->getResult();
        
        // Calculate statistics
        $totalArtefacts = count($artefact);
        $artefactParType = [];
        foreach ($artefact as $a) {
            $type = $a->getType() ?: 'Inconnu';
            if (!isset($artefactParType[$type])) {
                $artefactParType[$type] = 0;
            }
            $artefactParType[$type]++;
        }
        
        // Fetch comments for each artefact
        $commentaireRepo = $em->getRepository(Commentaire::class);
        $userRepo = $em->getRepository(User::class);
        $artefactComments = [];
        $artefactCommentCounts = [];
        $commentUserNames = [];
        
        foreach ($artefact as $a) {
            $artefactId = $a->getId();
            $comments = $commentaireRepo->findByArtefact($artefactId);
            $artefactComments[$artefactId] = $comments;
            $artefactCommentCounts[$artefactId] = count($comments);
            
            // Fetch user names for each comment
            foreach ($comments as $comment) {
                $userId = $comment->getUserId();
                if ($userId && !isset($commentUserNames[$userId])) {
                    $user = $userRepo->find($userId);
                    if ($user) {
                        $commentUserNames[$userId] = $user->getPrenom() . ' ' . $user->getNom();
                    }
                }
            }
        }
        
        return $this->render('admin/artefacts.html.twig', [
            'artefact' => $artefact,
            'totalArtefacts' => $totalArtefacts,
            'artefactParType' => $artefactParType,
            'search' => $search,
            'artefactComments' => $artefactComments,
            'artefactCommentCounts' => $artefactCommentCounts,
            'commentUserNames' => $commentUserNames,
        ]);
    }

    #[Route('/artefacts/{id}/delete', name: 'admin_artefact_delete', methods: ['POST'])]
    public function deleteArtefact(Artefact $artefact, EntityManagerInterface $em): Response
    {
        $em->remove($artefact);
        $em->flush();

        $this->addFlash('success', 'Artefact supprimé avec succès.');
        return $this->redirectToRoute('admin_artefacts');
    }

    #[Route('/comments/{id}/delete', name: 'admin_comment_delete', methods: ['POST'])]
    public function deleteComment(Commentaire $comment, EntityManagerInterface $em): Response
    {
        $em->remove($comment);
        $em->flush();

        $this->addFlash('success', 'Commentaire supprimé avec succès.');
        
        // Redirect back to appropriate page based on comment type
        if ($comment->getOeuvreId()) {
            return $this->redirectToRoute('admin_oeuvres');
        } elseif ($comment->getArtefactId()) {
            return $this->redirectToRoute('admin_artefacts');
        }
        return $this->redirectToRoute('admin_oeuvres');
    }

    #[Route('/comments/oeuvre/{id}/list', name: 'admin_comments_oeuvre', methods: ['GET'])]
    public function listCommentsByOeuvre(Oeuvre $oeuvre, EntityManagerInterface $em): JsonResponse
    {
        $commentaireRepo = $em->getRepository(Commentaire::class);
        $userRepo = $em->getRepository(User::class);
        
        $comments = $commentaireRepo->findByOeuvre($oeuvre->getId());
        
        $commentsData = [];
        foreach ($comments as $comment) {
            $userId = $comment->getUserId();
            $user = $userRepo->find($userId);
            $userName = $user ? $user->getPrenom() . ' ' . $user->getNom() : 'Utilisateur #' . $userId;
            
            $commentsData[] = [
                'id' => $comment->getId(),
                'contenu' => $comment->getContenu(),
                'createdAt' => $comment->getCreatedAt()->format('d/m/Y H:i'),
                'userId' => $userId,
                'userName' => $userName,
            ];
        }
        
        return new JsonResponse([
            'oeuvreId' => $oeuvre->getId(),
            'oeuvreTitle' => $oeuvre->getTitle(),
            'totalComments' => count($commentsData),
            'comments' => $commentsData,
        ]);
    }

    #[Route('/comments/artefact/{id}/list', name: 'admin_comments_artefact', methods: ['GET'])]
    public function listCommentsByArtefact(Artefact $artefact, EntityManagerInterface $em): JsonResponse
    {
        $commentaireRepo = $em->getRepository(Commentaire::class);
        $userRepo = $em->getRepository(User::class);
        
        $comments = $commentaireRepo->findByArtefact($artefact->getId());
        
        $commentsData = [];
        foreach ($comments as $comment) {
            $userId = $comment->getUserId();
            $user = $userRepo->find($userId);
            $userName = $user ? $user->getPrenom() . ' ' . $user->getNom() : 'Utilisateur #' . $userId;
            
            $commentsData[] = [
                'id' => $comment->getId(),
                'contenu' => $comment->getContenu(),
                'createdAt' => $comment->getCreatedAt()->format('d/m/Y H:i'),
                'userId' => $userId,
                'userName' => $userName,
            ];
        }
        
        return new JsonResponse([
            'artefactId' => $artefact->getId(),
            'artefactName' => $artefact->getName(),
            'totalComments' => count($commentsData),
            'comments' => $commentsData,
        ]);
    }

    #[Route('/comments/{id}/delete-ajax', name: 'admin_comment_delete_ajax', methods: ['POST'])]
    public function deleteCommentAjax(Commentaire $comment, EntityManagerInterface $em, Request $request): JsonResponse
    {
        try {
            $commentId = $comment->getId();
            $oeuvreId = $comment->getOeuvreId();
            $artefactId = $comment->getArtefactId();
            
            $em->remove($comment);
            $em->flush();
            
            // Get updated comment count
            $commentaireRepo = $em->getRepository(Commentaire::class);
            $newCount = 0;
            if ($oeuvreId) {
                $newCount = count($commentaireRepo->findByOeuvre($oeuvreId));
            } elseif ($artefactId) {
                $newCount = count($commentaireRepo->findByArtefact($artefactId));
            }
            
            return new JsonResponse([
                'success' => true,
                'message' => 'Commentaire supprimé avec succès',
                'deletedId' => $commentId,
                'newCount' => $newCount,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage(),
            ], 500);
        }
    }
}
