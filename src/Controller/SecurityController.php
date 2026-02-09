<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;


class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Si l'utilisateur est déjà connecté, on le redirige
        if ($this->getUser()) {
            return $this->redirectToRoute('home'); // ou vers une route par défaut après login
        }

        // Récupère l'erreur de connexion si elle existe
        $error = $authenticationUtils->getLastAuthenticationError();
        // Dernier nom d'utilisateur saisi
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error
        ]);
    }
    

    

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Cette méthode sera interceptée par le firewall, donc elle peut rester vide
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
