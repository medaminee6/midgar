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

    #[Route('/{page}', name: 'page', requirements: ['page' => '(?!shop)([a-z0-9\-\.\/]+)'])]
    public function show(string $page): Response
    {
        $template = $page . '.html.twig';
        
        try {
            return $this->render($template);
        } catch (\Exception $e) {
            return $this->render('404.html.twig', [
                'requestedPage' => $page
            ], new Response('', Response::HTTP_NOT_FOUND));
        }
    }
}
