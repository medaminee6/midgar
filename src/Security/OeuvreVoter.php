<?php

namespace App\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class OeuvreVoter extends Voter implements VoterInterface
{
    private const EDIT = 'EDIT';
    private const DELETE = 'DELETE';

    protected function supports(string $attribute, $subject): bool
    {
        if (!in_array($attribute, [self::EDIT, self::DELETE], true)) {
            return false;
        }

        return is_object($subject);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!is_object($user)) {
            return false;
        }

        if (method_exists($subject, 'getCreatedBy')) {
            $owner = $subject->getCreatedBy();
            if (is_object($owner) && method_exists($owner, 'getId') && method_exists($user, 'getId')) {
                return $owner->getId() === $user->getId();
            }
        }

        return false;
    }
}
