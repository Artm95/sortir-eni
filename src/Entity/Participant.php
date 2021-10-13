<?php

namespace App\Entity;

use App\Repository\ParticipantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=ParticipantRepository::class)
 */
class Participant implements UserInterface
{

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     */
    #[Assert\NotBlank(message: 'L\'email est obligatoire')]
    #[Assert\Email(
        message: 'L\'email n\'est pas valide.',
    )]
    private $email;

    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     */
    /*
    #[Assert\NotBlank(message: 'Le mot de passe est obligatoire')]
    #[Assert\Regex(
        pattern: '^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d\w\W]{8,}$',
        message: 'Le mot de passe doit faire au moins 8 caractères et contenir au moins une lettre et un nombre'
    )]
    */
    private $password;

    /**
     * @ORM\Column(type="string", length=255)
     */
    #[Assert\NotBlank(message: 'Le prénom est obligatoire')]
    #[Assert\Length(
        min: 3,
        max: 50,
        minMessage: 'Le prénom doit faire au moins {{ limit }} caractères',
        maxMessage: 'Le prénom ne peut pas être plus long que {{ limit }} caractères',
    )]
    private $firstName;

    /**
     * @ORM\Column(type="string", length=255)
     */
    #[Assert\NotBlank(message: 'Le nom est obligatoire')]
    #[Assert\Length(
        min: 3,
        max: 50,
        minMessage: 'Le nom doit faire au moins {{ limit }} caractères',
        maxMessage: 'Le nom ne peut pas être plus long que {{ limit }} caractères',
    )]
    private $lastName;

    /**
     * @ORM\Column(type="string", length=20)
     */
    #[Assert\NotBlank(message: 'Le numéro de téléphone est obligatoire')]
    #[Assert\Regex(
        pattern: '/^(?:(?:\+|00)33|0)\s*[1-9](?:[\s.-]*\d{2}){4}$/',
        message: 'Le numéro de téléphone doit suivre le format suivant : XX XX XX XX XX'
        //message: 'Le numéro de téléphone doit suivre un des formats suivant : +33 X XX XX XX XX / XX XX XX XX XX / 0033 X XX XX XX XX'
    )]
    private $phoneNumber;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isAdmin;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isActive;

    /**
     * @ORM\ManyToOne(targetEntity=Campus::class, inversedBy="participants")
     * @ORM\JoinColumn(nullable=false)
     */
    #[Assert\NotBlank(message: 'Le campus est obligatoire')]
    #[Assert\Type(Campus::class)]
    private $campus;

    /**
     * @ORM\OneToMany(targetEntity=Event::class, mappedBy="organizer", orphanRemoval=true)
     */
    private $events;

    /**
     * @ORM\ManyToMany(targetEntity=Event::class, inversedBy="participants")
     */
    private $subscribedToEvents;

    public function __construct()
    {
        $this->events = new ArrayCollection();
        $this->subscribedToEvents = new ArrayCollection();
        $this->isActive = true;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string|null $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        $this->plainPassword = null;
        $this->confirmation = null;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string|null $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string|null $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(string|null $phoneNumber): self
    {
        $phoneNumber = $this->formatPhoneNumber($phoneNumber);
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function getIsAdmin(): ?bool
    {
        return $this->isAdmin;
    }

    public function setIsAdmin(?bool $isAdmin): self
    {
        $this->isAdmin = $isAdmin;

        return $this;
    }

    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

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

    /**
     * @return Collection|Event[]
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function addEvent(Event $event): self
    {
        if (!$this->events->contains($event)) {
            $this->events[] = $event;
            $event->setOrganizer($this);
        }

        return $this;
    }

    public function removeEvent(Event $event): self
    {
        if ($this->events->removeElement($event)) {
            // set the owning side to null (unless already changed)
            if ($event->getOrganizer() === $this) {
                $event->setOrganizer(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Event[]
     */
    public function getSubscribedToEvents(): Collection
    {
        return $this->subscribedToEvents;
    }

    public function addSubscribedToEvent(Event $subscribedToEvent): self
    {
        if (!$this->subscribedToEvents->contains($subscribedToEvent)) {
            $this->subscribedToEvents[] = $subscribedToEvent;
        }

        return $this;
    }

    public function removeSubscribedToEvent(Event $subscribedToEvent): self
    {
        $this->subscribedToEvents->removeElement($subscribedToEvent);

        return $this;
    }

    public function setUserName(?string $userName): self
    {
        $this->userName = $userName;

        return $this;
    }

    private $plainPassword;

    public function setPlainPassword(string $plainPassword)
    {
        $this->plainPassword = $plainPassword;
    }

    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $photo;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $nickname;

    public function isOrganizer(Event $event) : bool
    {
        return $this->events->contains($event);
    }

    public function isParticipant(Event $event) : bool
    {
        return $this->subscribedToEvents->contains($event);
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): self
    {
        $this->photo = $photo;

        return $this;
    }

    public function getNickname(): ?string
    {
        return $this->nickname;
    }

    public function setNickname(?string $nickname): self
    {
        $this->nickname = $nickname;

        return $this;
    }

    /**
     * format a phone french phone number to get it as a 10 digits long string
     * @param string|null $phoneNumber
     * @return string|null
     */
    private function formatPhoneNumber(?string $phoneNumber) : ?string {
        if (!$phoneNumber) {
            return null;
        }
        $formatted = str_replace(' ', '', $phoneNumber);
        if (strlen($formatted) > 10) {
            $split = explode('33', $formatted);
            array_slice($split, 0, 1);
            $formatted = '0'.implode('', $split);
        }
        return $formatted;
    }
}
