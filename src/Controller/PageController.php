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

    #[Route('/{page}', name: 'page', requirements: ['page' => '[a-z0-9\-\.\/]+'], priority: -100)]
    public function show(string $page): Response
    {
        $template = $page . '.html.twig';

        $twig = $this->container->get('twig');
        if (!$twig->getLoader()->exists($template)) {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
        }

        return $this->render($template);
    }
}
