<?php
namespace App\Entity;

use App\Repository\GroupeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GroupeRepository::class)]
class Groupe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $nom = null;

    /**
     * @var Collection<int, Participant>
     */
    #[ORM\ManyToMany(targetEntity: Participant::class, inversedBy: 'groupes')]
    #[ORM\JoinTable(name: 'groupe_participant')]
    private Collection $participants;

    #[ORM\ManyToOne(targetEntity: Participant::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Participant $chefDeGroupe = null;

    public function __construct()
    {
        $this->participants = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    /**
     * @return Collection<int, Participant>
     */
    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    public function addParticipant(Participant $participant): self
    {
        if (!$this->participants->contains($participant)) {
            $this->participants->add($participant);
            $participant->addGroupe($this); // Synchronize relationship on both sides
        }
        return $this;
    }

    public function removeParticipant(Participant $participant): self
    {
        //on retire le participant du groupe
        $this->participants->removeElement($participant);
        return $this;
    }

    public function getChefDeGroupe(): ?Participant
    {
        return $this->chefDeGroupe;
    }

    public function setChefDeGroupe(?Participant $chefDeGroupe): self
    {
        $this->chefDeGroupe = $chefDeGroupe;

        return $this;
    }
}
