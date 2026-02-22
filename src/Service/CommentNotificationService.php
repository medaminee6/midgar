<?php

namespace App\Service;

use App\Entity\Commentaire;
use App\Entity\Oeuvre;
use App\Entity\Artefact;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class CommentNotificationService
{
    public function __construct(
        private MailerInterface $mailer,
        private EntityManagerInterface $entityManager,
        private string $appBaseUrl
    ) {}

    public function notifyCommentCreated(Commentaire $commentaire, User $commentAuthor): void
    {
        $creator = null;
        $creationTitle = '';
        $creationType = '';
        $deleteUrl = '';

        if ($commentaire->getOeuvreId()) {
            $oeuvre = $this->entityManager
                ->getRepository(Oeuvre::class)
                ->find($commentaire->getOeuvreId());

            if ($oeuvre && $oeuvre->getCreatedBy()) {
                $creator = $oeuvre->getCreatedBy();
                $creationTitle = $oeuvre->getTitle();
                $creationType = 'œuvre';
            }
        } elseif ($commentaire->getArtefactId()) {
            $artefact = $this->entityManager
                ->getRepository(Artefact::class)
                ->find($commentaire->getArtefactId());

            if ($artefact && $artefact->getCreatedBy()) {
                $creator = $artefact->getCreatedBy();
                $creationTitle = $artefact->getName();
                $creationType = 'artefact';
            }
        }

        if (!$creator || $creator->getId() === $commentAuthor->getId() || !$creator->getEmail()) {
            return;
        }

        // 🔥 LIEN DIRECT DE SUPPRESSION (GET)
        $deleteUrl = $this->appBaseUrl . '/commentaire/' . $commentaire->getId() . '/supprimer';

        $email = (new TemplatedEmail())
            ->from(new Address('noreply@midgar.com', 'Midgar'))
            ->to(new Address(
                $creator->getEmail(),
                $creator->getPrenom() . ' ' . $creator->getNom()
            ))
            ->subject('Nouveau commentaire sur votre ' . $creationType)
            ->htmlTemplate('emails/comment_notification.html.twig')
            ->context([
                'commentaire' => $commentaire,
                'authorName' => $commentAuthor->getPrenom() . ' ' . $commentAuthor->getNom(),
                'creationTitle' => $creationTitle,
                'creationType' => $creationType,
                'deleteUrl' => $deleteUrl,
                'creatorName' => $creator->getPrenom(),
            ]);

        $this->mailer->send($email);
    }
}
