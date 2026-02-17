<?php

namespace App\Controller;

use App\Avatar\AvatarGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AvatarController extends AbstractController
{
    
    #[Route('/avatar', name: 'avatar_upload')]
    public function upload(Request $request, AvatarGeneratorInterface $generator): Response
    {
        if ($request->isMethod('POST')) {
            $file = $request->files->get('image');
            $theme = $request->request->get('theme', 'anime');

            if (!$file) {
                $this->addFlash('error', 'Aucune image envoyée');
                return $this->redirectToRoute('avatar_upload');
            }

            $avatarUrl = $generator->generate(
                $file->getPathname(),
                $theme
            );

            return $this->render('avatar/upload.html.twig', [
                'avatar' => $avatarUrl
            ]);
        }

        return $this->render('avatar/upload.html.twig');
    }
}
