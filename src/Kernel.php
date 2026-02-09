<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

// Ajoute les bundles
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use SymfonyCasts\Bundle\VerifyEmail\SymfonyCastsVerifyEmailBundle;
use KnpU\OAuth2ClientBundle\KnpUOAuth2ClientBundle;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        $bundles = [
            new FrameworkBundle(),
            new TwigBundle(),
            new DoctrineBundle(),
            new DoctrineMigrationsBundle(),
            new SecurityBundle(),
            new SymfonyCastsVerifyEmailBundle(),

            // <-- AJOUTE CE BUNDLE
            new KnpUOAuth2ClientBundle(),
        ];

        foreach ($bundles as $bundle) {
            yield $bundle;
        }
    }
}
