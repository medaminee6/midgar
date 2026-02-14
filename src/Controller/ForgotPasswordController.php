<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ForgotPasswordType;
use App\Form\ResetPasswordType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ForgotPasswordController extends AbstractController
{
    #[Route('/forgot-password', name: 'forgot_password')]
    public function forgot(
        Request $request,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): Response {
        $form = $this->createForm(ForgotPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();

            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

            if ($user && $user->hasLocalPassword()) {
                $token = bin2hex(random_bytes(32));

                $user->setResetToken($token);
                $user->setResetTokenExpiresAt(
                    (new \DateTime())->modify('+30 minutes')
                );

                $em->flush();

                $resetUrl = $this->generateUrl(
                    'reset_password',
                    ['token' => $token],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );

                $emailMessage = (new Email())
                    ->from('no-reply@midgar.com')
                    ->to($user->getEmail())
                    ->subject('Réinitialisation de votre mot de passe')
                    ->html("
                        <p>Bonjour,</p>
                        <p>Cliquez sur le lien suivant pour réinitialiser votre mot de passe :</p>
                        <p><a href='{$resetUrl}'>Réinitialiser</a></p>
                        <p>Ce lien expire dans 30 minutes.</p>
                    ");

                $mailer->send($emailMessage);
            }

            $this->addFlash(
                'success',
                'Si un compte existe, un email a été envoyé.'
            );

            return $this->redirectToRoute('forgot_password');
        }

        return $this->render('security/forgot_password.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/reset-password/{token}', name: 'reset_password')]
    public function reset(
        string $token,
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): Response {
        $user = $em->getRepository(User::class)->findOneBy([
            'resetToken' => $token
        ]);

        if (
            !$user ||
            !$user->getResetTokenExpiresAt() ||
            $user->getResetTokenExpiresAt() < new \DateTime()
        ) {
            throw $this->createNotFoundException('Lien invalide ou expiré');
        }

        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $password = $form->get('password')->getData();

            $user->setPassword(
                $hasher->hashPassword($user, $password)
            );

            $user->setResetToken(null);
            $user->setResetTokenExpiresAt(null);

            $em->flush();

            $this->addFlash('success', 'Mot de passe mis à jour');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/reset_password.html.twig', [
            'form' => $form,
        ]);
    }
}
