<?php

namespace App\Entity;

use App\Repository\LocationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=LocationRepository::class)
 */
class Location
{
    /**
     * @Groups("location")
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @Groups("location")
     * @ORM\Column(type="string", length=255)
     */
    #[Assert\NotBlank(message: 'Le nom du lieu est obligatoire')]
    private $name;

    /**
     * @Groups("location")
     * @ORM\Column(type="string", length=255)
     */
    #[Assert\NotBlank(message: 'Le nom de la rue est obligatoire')]
    private $street;

    /**
     * @Groups("location")
     * @ORM\Column(type="float")
     */
    #[Assert\NotBlank(message: 'L\'atitude est obligatoire et doit être un nombre')]
    #[Assert\Type('float')]
    private $latitude;

    /**
     * @Groups("location")
     * @ORM\Column(type="float")
     */
    #[Assert\NotBlank(message: 'La longitude est obligatoire et doit être un nombre')]
    #[Assert\Type('float')]
    private $longitude;

    /**
     * @ORM\OneToMany(targetEntity=Event::class, mappedBy="location", orphanRemoval=true)
     */
    private $events;

    /**
     * @Groups("location")
     * @ORM\ManyToOne(targetEntity=City::class, inversedBy="locations")
     * @ORM\JoinColumn(nullable=false)
     */
    private $city;

    public function __construct()
    {
        $this->events = new ArrayCollection();
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

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(string $street): self
    {
        $this->street = $street;

        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(float|null $latitude): self
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(float|null $longitude): self
    {
        $this->longitude = $longitude;

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
            $event->setLocation($this);
        }

        return $this;
    }

    public function removeEvent(Event $event): self
    {
        if ($this->events->removeElement($event)) {
            // set the owning side to null (unless already changed)
            if ($event->getLocation() === $this) {
                $event->setLocation(null);
            }
        }

        return $this;
    }

    public function getCity(): ?City
    {
        return $this->city;
    }

    public function setCity(?City $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function __toString(){
        return $this->name . ", " . $this->street . ", " . $this->city->getName() . ", " . $this->city->getZipCode()
            . ", lattitude : " . $this->latitude . ", longitude : " . $this->longitude;
    }
}
