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
use Symfony\Component\Mime\Address;


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

               // Dans ton ForgotPasswordController, remplace la partie Email par ceci :
$emailMessage = (new Email())
    ->from(new Address('zeinebsgh466@gmail.com', 'Midgar Fantasy'))

    ->to($user->getEmail())
    ->subject('🔮 Réinitialisation de votre mot de passe')
    ->html("
<!DOCTYPE html>
<html lang='fr'>
<head>
<meta charset='UTF-8'>
<title>Réinitialisation mot de passe</title>
<style>
    body {
        background: #0d0f0f;
        font-family: 'Arial', sans-serif;
        color: #E6FFF6;
        margin:0; padding:0;
    }
    .container {
        max-width: 600px;
        margin: 0 auto;
        padding: 30px;
        text-align: center;
        background: linear-gradient(135deg, #1a1f1e 0%, #0d0f0f 100%);
        border-radius: 16px;
        box-shadow: 0 0 40px rgba(24,227,164,0.4);
        animation: fadeIn 1s ease-in-out;
    }
    h1 {
        color: #18E3A4;
        font-size: 28px;
        margin-bottom: 20px;
        text-shadow: 0 0 5px #18E3A4;
    }
    p {
        color: #B0B9B6;
        font-size: 16px;
        margin-bottom: 30px;
    }
    a.button {
        display: inline-block;
        padding: 14px 28px;
        border-radius: 12px;
        background: linear-gradient(135deg, #18E3A4, #13c891);
        color: #000;
        font-weight: bold;
        text-decoration: none;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        box-shadow: 0 4px 15px rgba(24,227,164,0.3);
    }
    a.button:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(24,227,164,0.4);
    }

    @keyframes fadeIn {
        from { opacity:0; transform: translateY(-20px); }
        to { opacity:1; transform: translateY(0); }
    }

    .footer {
        margin-top: 30px;
        color: #B0B9B6;
        font-size: 12px;
    }
</style>
</head>
<body>
<div class='container'>
    <h1>🔮 Réinitialisez votre mot de passe</h1>
    <p>Bonjour,<br>
       Cliquez sur le bouton ci-dessous pour réinitialiser votre mot de passe. <br>
       Ce lien expire dans 30 minutes.</p>
    <a class='button' href='{$resetUrl}'>Réinitialiser le mot de passe</a>
    <div class='footer'>
        Si vous n'avez pas demandé ce mail, ignorez-le.<br>
        &copy; 2026 Midgar Fantasy Platform
    </div>
</div>
</body>
</html>
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
