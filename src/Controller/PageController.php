<?php

namespace App\Controller;

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

    #[Route('/{page}', name: 'page', requirements: ['page' => '(?!admin/(quiz-questions|quiz-reponses)|quiz).*[a-z0-9\-\.\/]+'])]
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
