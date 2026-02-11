<?php
namespace App\Controller;

use App\Entity\User;
use App\Entity\Artefact;
use App\Entity\Oeuvre;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
}
