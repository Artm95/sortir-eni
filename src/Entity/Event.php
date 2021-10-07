<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=EventRepository::class)
 */
class Event
{

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    #[Assert\NotBlank(message: 'Le nom est obligatoire')]
    #[Assert\Length(
        min: 10,
        max: 15,
        minMessage: 'Le nom doit faire au moins {{ limit }} caractères',
        maxMessage: 'Le nom ne peut pas être plus long que {{ limit }} caractères',
    )]
    private $name;

    /**
     * @ORM\Column(type="datetime")
     */
    #[Assert\NotBlank(message: 'La date est obligatoire')]
    #[Assert\Type(\DateTime::class)]
    #[Assert\GreaterThanOrEqual(
        value: 'today',
        message: 'La date de la sortie ne peut pas être inférieur à celle du jour'
    )]
    private $startDate;

    /**
     * @ORM\Column(type="integer")
     */
    #[Assert\NotBlank(message: 'La durée est obligatoire')]
    #[Assert\Positive(message: 'La durée doit être positive')]
    private $duration;

    /**
     * @ORM\Column(type="date")
     */
    #[Assert\NotBlank(message: 'La date de fin d\'inscription est obligatoire')]
    #[Assert\Type(\DateTime::class)]
    #[Assert\LessThanOrEqual(
        propertyPath: 'startDate',
        message: 'La date de fin des inscriptions ne peut pas être supérieur à celle de la sortie'
    )]
    #[Assert\GreaterThanOrEqual(
        value: 'today',
        message: 'La date de fin des inscriptions ne peut pas être inférieur à celle du jour'
    )]
    private $signUpDeadline;

    /**
     * @ORM\Column(type="integer")
     */
    #[Assert\NotBlank]
    #[Assert\Positive(message: 'Le nombre de participants doit être positif')]
    private $maxParticipants;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    #[Assert\Type('string')]
    private $infos;

    /**
     * @ORM\ManyToOne(targetEntity=Participant::class, inversedBy="events")
     * @ORM\JoinColumn(nullable=false)
     */
    private $organizer;

    /**
     * @ORM\ManyToOne(targetEntity=Campus::class, inversedBy="events")
     * @ORM\JoinColumn(nullable=false)
     */
    #[Assert\NotBlank]
    #[Assert\Type(Campus::class)]
    private $campus;

    /**
     * @ORM\ManyToOne(targetEntity=State::class, inversedBy="events")
     * @ORM\JoinColumn(nullable=false)
     */
    private $state;

    /**
     * @ORM\ManyToOne(targetEntity=Location::class, inversedBy="events")
     * @ORM\JoinColumn(nullable=false)
     */
    #[Assert\NotBlank]
    #[Assert\Type(Location::class)]
    private $location;

    /**
     * @ORM\ManyToMany(targetEntity=Participant::class, mappedBy="subscribedToEvents")
     */
    private $participants;

    public function __construct()
    {
        $this->participants = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): self
    {
        $this->duration = $duration;

        return $this;
    }

    public function getSignUpDeadline(): ?\DateTimeInterface
    {
        return $this->signUpDeadline;
    }

    public function setSignUpDeadline(\DateTimeInterface $signUpDeadline): self
    {
        $this->signUpDeadline = $signUpDeadline;

        return $this;
    }

    public function getMaxParticipants(): ?int
    {
        return $this->maxParticipants;
    }

    public function setMaxParticipants(int $maxParticipants): self
    {
        $this->maxParticipants = $maxParticipants;

        return $this;
    }

    public function getInfos(): ?string
    {
        return $this->infos;
    }

    public function setInfos(?string $infos): self
    {
        $this->infos = $infos;

        return $this;
    }

    public function getOrganizer(): ?Participant
    {
        return $this->organizer;
    }

    public function setOrganizer(?Participant $organizer): self
    {
        $this->organizer = $organizer;

        return $this;
    }

    public function getCampus(): ?Campus
    {
        return $this->campus;
    }

    public function setCampus(?Campus $campus): self
    {
        $this->campus = $campus;

        return $this;
    }

    public function getState(): ?State
    {
        return $this->state;
    }

    public function setState(?State $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function setLocation(?Location $location): self
    {
        $this->location = $location;

        return $this;
    }

    /**
     * @return Collection|Participant[]
     */
    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    public function addParticipant(Participant $participant): self
    {
        if (!$this->participants->contains($participant)) {
            $this->participants[] = $participant;
            $participant->addSubscribedToEvent($this);
        }

        return $this;
    }

    public function removeParticipant(Participant $participant): self
    {
        if ($this->participants->removeElement($participant)) {
            $participant->removeSubscribedToEvent($this);
        }

        return $this;
    }
}
