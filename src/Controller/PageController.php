<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PageController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        return $this->render('index.html.twig');
    }

    #[Route(
        '/{page}', 
        name: 'page', 
        requirements: ['page' => '[a-z0-9\-\.\/]+']
    )]
    public function show(string $page): Response
    {
        // Exclure les routes qui existent déjà
        $excludedRoutes = ['register', 'login', 'dashboard']; // ajoute toutes les routes fixes ici
        if (in_array($page, $excludedRoutes)) {
            throw $this->createNotFoundException('Cette page n’existe pas ici.');
        }

        $template = 'pages/' . $page . '.html.twig'; // mettre tes templates génériques dans un sous-dossier pages/
        
        if (!file_exists($this->getParameter('kernel.project_dir') . '/templates/' . $template)) {
            // Template inexistant → afficher le 404
            return $this->render('404.html.twig', [
                'requestedPage' => $page
            ], new Response('', Response::HTTP_NOT_FOUND));
        }

        return $this->render($template);
    }
}
