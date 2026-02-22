<?php

namespace App\Service;

use App\Repository\UserPreferenceRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class PostNotificationService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly UserPreferenceRepository $userPreferenceRepository,
        private readonly LoggerInterface $logger,
        private readonly string $fromEmail,
    ) {
    }

    /**
     * Sends notification emails to users whose preferences match the given content tag.
     * This method must never throw (it logs failures instead).
     */
    public function notifyContentCreated(object $entity): void
    {
        try {
            if (!method_exists($entity, 'getTag')) {
                return;
            }

            $tag = trim((string) $entity->getTag());
            if ($tag === '') {
                return;
            }

            $recipients = $this->userPreferenceRepository->findRecipientEmailsByTag($tag);
            if ($recipients === []) {
                return;
            }

            [$typeLabel, $title] = $this->extractTypeAndTitle($entity);

            $safeRecipients = [];
            foreach ($recipients as $email) {
                if ($this->isValidEmail($email)) {
                    $safeRecipients[] = $email;
                }
            }

            $safeRecipients = array_values(array_unique($safeRecipients));
            if ($safeRecipients === []) {
                return;
            }

            $subject = sprintf('Nouveau contenu %s (%s)', $typeLabel, $tag);
            $textBody = sprintf(
                "Un nouveau contenu a été créé.\n\nType: %s\nTitre: %s\nTag: %s\n",
                $typeLabel,
                $title,
                $tag
            );

            foreach ($safeRecipients as $toEmail) {
                try {
                    $email = (new Email())
                        ->from($this->fromEmail)
                        ->to($toEmail)
                        ->subject($subject)
                        ->text($textBody);

                    $this->mailer->send($email);
                } catch (\Throwable $e) {
                    $this->logger->error('Failed to send content notification email', [
                        'to' => $toEmail,
                        'tag' => $tag,
                        'entity_class' => $entity::class,
                        'exception' => $e,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            $this->logger->error('Content notification crashed (suppressed)', [
                'entity_class' => $entity::class,
                'exception' => $e,
            ]);
        }
    }

    /**
     * @return array{0:string,1:string}
     */
    private function extractTypeAndTitle(object $entity): array
    {
        $typeLabel = (new \ReflectionClass($entity))->getShortName();
        $title = '';

        if (method_exists($entity, 'getTitle')) {
            $title = (string) $entity->getTitle();
        } elseif (method_exists($entity, 'getName')) {
            $title = (string) $entity->getName();
        } elseif (method_exists($entity, 'getId')) {
            $title = '#' . (string) $entity->getId();
        }

        $title = trim($title);
        if ($title === '') {
            $title = '(sans titre)';
        }

        return [$typeLabel, $title];
    }

    private function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}
