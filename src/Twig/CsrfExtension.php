<?php

namespace App\Twig;

use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CsrfExtension extends AbstractExtension
{
    public function __construct(
        private CsrfTokenManagerInterface $csrfTokenManager
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('csrf_token', [$this, 'csrfToken']),
        ];
    }

    public function csrfToken(string $intention = ''): string
    {
        return $this->csrfTokenManager->getToken($intention)->getValue();
    }
}
