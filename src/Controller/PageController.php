<?php

namespace App\Controller;

use App\Repository\ProduitRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PageController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        return $this->render('index.html.twig');
    }

    #[Route('/discover', name: 'discover')]
    public function discover(): Response
    {
        return $this->render('discover.html.twig');
    }

    #[Route('/admin/users', name: 'admin_users', methods: ['GET'])]
    public function adminUsers(): Response
    {
        return $this->render('admin/users.html.twig');
    }

    #[Route('/admin/produits', name: 'admin_produits', methods: ['GET'])]
    public function adminProduits(ProduitRepository $produitRepo): Response
    {
        $produits = $produitRepo->findAll();

        return $this->render('admin/produits.html.twig', [
            'produits' => $produits,
        ]);
    }

    /**
     * Catch-all static pages
     * EXCLUDES admin, quiz, shop, universes, preferences, personnages routes
     */
    #[Route(
        '/{page}',
        name: 'page',
        requirements: [
            'page' => '(?!admin|quiz|shop|universes|preferences|personnages).*'
        ]
    )]
    public function show(string $page): Response
    {
        $template = $page . '.html.twig';

        try {
            return $this->render($template);
        } catch (\Exception $e) {
            return new Response(
                'Page not found: ' . htmlspecialchars($page),
                Response::HTTP_NOT_FOUND
            );
        }
    }
}
