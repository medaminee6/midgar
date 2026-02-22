<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class GoogleAuthenticator extends OAuth2Authenticator
{
    public function __construct(
        private ClientRegistry $clientRegistry,
        private EntityManagerInterface $em,
        private UrlGeneratorInterface $urlGenerator
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client) {

                /** @var GoogleUser $googleUser */
                $googleUser = $client->fetchUserFromToken($accessToken);

                $googleId = $googleUser->getId();
                $email    = $googleUser->getEmail();

                $user = $this->em->getRepository(User::class)
                    ->findOneBy(['googleId' => $googleId]);

                if ($user) {
                    return $user;
                }

                // Si un compte existe déjà avec le même email
                $existingUser = $this->em->getRepository(User::class)
                    ->findOneBy(['email' => $email]);

                if ($existingUser) {
                    $existingUser->setGoogleId($googleId);
                    $existingUser->setAuthProvider('google');
                    $this->em->flush();

                    return $existingUser;
                }

                // Création d'un nouveau compte Google
                $user = new User();
                $user->setEmail($email);
                $user->setGoogleId($googleId);
                $user->setAuthProvider('google');
                $user->setPrenom($googleUser->getFirstName() ?? 'Google');
                $user->setNom($googleUser->getLastName() ?? 'User');
                $user->setUsername(
                    strtolower(explode('@', $email)[0]) . rand(1000, 9999)
                );
                $user->setAvatar($googleUser->getAvatar());
                $user->setIsVerified(true);
                $user->setRole('user');

                $this->em->persist($user);
                $this->em->flush();

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): ?RedirectResponse {
        return new RedirectResponse(
            $this->urlGenerator->generate('home')
        );
    }

    public function onAuthenticationFailure(
        Request $request,
        AuthenticationException $exception
    ): RedirectResponse {
        return new RedirectResponse(
            $this->urlGenerator->generate('app_login')
        );
    }
}
