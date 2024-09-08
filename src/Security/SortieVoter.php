<?php

// src/Security/PostVoter.php
namespace App\Security;

use App\Entity\Sortie;
use App\Entity\Participant;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SortieVoter extends Voter
{

    // these strings are just invented: you can use anything
    const DELETE = 'delete';
    const EDIT = 'edit';
    const INSCRIPTION = 'inscription';
    const DESINSCRIPTION = 'desinscription';
    public function __construct(
        private Security $security,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        // if the attribute isn't one we support, return false
        if (!in_array($attribute, [self::DELETE, self::EDIT, self::INSCRIPTION, self::DESINSCRIPTION])) {
            return false;
        }

        // only vote on `Sortie` objects
        if (!$subject instanceof Sortie) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof Participant) {
            // the user must be logged in; if not, deny access
            return false;
        }

        // you know $subject is a Sortie object, thanks to `supports()`
        /** @var Sortie $sortie */
        $sortie = $subject;

        return match($attribute) {
            self::DELETE => $this->canDelete($sortie, $user),
            self::EDIT => $this->canEdit($sortie, $user),
            self::INSCRIPTION=>$this->canInscription($sortie, $user),
            self::DESINSCRIPTION=>$this->canDesinscription($sortie, $user),
            default => throw new \LogicException('This code should not be reached!')
        };
    }

    private function canDelete(Sortie $sortie, Participant $user): bool
    {
        // if they can edit, they can't delete
        if ($this->security->isGranted('ROLE_ADMINISTRATEUR')) {
            if ($sortie->getEtat()->getId()==2 || $sortie->getEtat()->getId()==3) {
                return true;
            } else
                return false;
        }
        if ($this->canEdit($sortie, $user)) {
            return false;
        } else {
            if ($sortie->getEtat()->getId()==2 || $sortie->getEtat()->getId()==3) {
                return $user === $sortie->getOrganisateur();
            } else {
                return false;
            }
        }
    }

    private function canEdit(Sortie $sortie, Participant $user): bool
    {
        // this assumes that the Sortie object has a `getOrganisteur()` method
        if ($sortie->getEtat()->getId()==1) {
            return $user === $sortie->getOrganisateur();
        } else {
            return false;
        }

    }

    private function canInscription(Sortie $sortie, Participant $user): bool
    {
        $tabInscrit=$sortie->getParticipantsInscrits();
        if ($sortie->getEtat()->getId()===2 && !$tabInscrit->contains($user)) {
                return $user !== $sortie->getOrganisateur();
        } else {
            return false;
        }

    }
    private function canDesinscription(Sortie $sortie, Participant $user): bool
    {
        $tabInscrit=$sortie->getParticipantsInscrits();
        if ($sortie->getEtat()->getId()===2 && $tabInscrit->contains($user)) {
            return $user !== $sortie->getOrganisateur();
        } else {
            return false;
        }

    }
}
