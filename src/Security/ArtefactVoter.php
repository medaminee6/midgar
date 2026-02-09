<?php

namespace App\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class ArtefactVoter extends Voter implements VoterInterface
{
    private const EDIT = 'EDIT';
    private const DELETE = 'DELETE';

    protected function supports(string $attribute, $subject): bool
    {
        if (!in_array($attribute, [self::EDIT, self::DELETE], true)) {
            return false;
        }

        // subject can be any object representing an artefact
        return is_object($subject);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!is_object($user)) {
            return false;
        }

        // If subject has getCreatedBy, compare
        if (method_exists($subject, 'getCreatedBy')) {
            $owner = $subject->getCreatedBy();
            if (is_object($owner) && method_exists($owner, 'getId') && method_exists($user, 'getId')) {
                return $owner->getId() === $user->getId();
            }
        }

        return false;
    }
}
