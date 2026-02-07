<?php
namespace App\Controller;

use App\Entity\User;
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
        $repo = $em->getRepository(User::class);

        $qb = $repo->createQueryBuilder('u');

        if ($q) {
            $qb->where('u.username LIKE :q OR u.email LIKE :q')
               ->setParameter('q', '%'.$q.'%');
        }

        $users = $qb->getQuery()->getResult();

        return $this->render('admin/users.html.twig', [
            'users' => $users,
            'search' => $q,
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
