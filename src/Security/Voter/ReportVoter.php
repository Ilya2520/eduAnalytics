<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Report;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Report access control voter.
 */
class ReportVoter extends Voter
{
    public const VIEW = 'VIEW';
    public const CREATE = 'CREATE';
    public const EDIT = 'EDIT';
    public const DELETE = 'DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::VIEW, self::CREATE, self::EDIT, self::DELETE], true)) {
            return false;
        }

        if ($attribute === self::CREATE) {
            return true; // creation without subject
        }

        return $subject instanceof Report;
    }

    /**
     * @param Report|null $subject
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        // Admins can do anything
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        switch ($attribute) {
            case self::CREATE:
                return in_array('ROLE_USER', $user->getRoles(), true);
            case self::VIEW:
                if (!$subject instanceof Report) {
                    return false;
                }
                $owner = $subject->getRequestedBy();
                return $owner instanceof User && $owner->getId() === $user->getId();
            case self::EDIT:
            case self::DELETE:
                // Only admins (handled above). Non-admins cannot edit/delete reports.
                return false;
        }

        return false;
    }
} 