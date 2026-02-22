<?php

namespace App\EventSubscriber;

use App\Entity\Artefact;
use App\Entity\Oeuvre;
use App\Entity\Personnage;
use App\Entity\Universe;
use App\Service\PostNotificationService;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class ContentCreatedEmailSubscriber implements EventSubscriber
{
    public function __construct(
        private readonly PostNotificationService $notificationService,
    ) {
    }

    public function getSubscribedEvents(): array
    {
        return [Events::postPersist];
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (
            !$entity instanceof Oeuvre
            && !$entity instanceof Artefact
            && !$entity instanceof Personnage
            && !$entity instanceof Universe
        ) {
            return;
        }

        $this->notificationService->notifyContentCreated($entity);
    }
}
