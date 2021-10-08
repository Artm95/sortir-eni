<?php

namespace App\Entity;

use App\Repository\CityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * @ORM\Entity(repositoryClass=CityRepository::class)
 */
class City
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
    #[Assert\NotBlank(message: 'Le nom de la ville est obligatoire')]
    #[Assert\Length(
        min: 3,
        max: 50,
        minMessage: 'Le nom de la ville doit faire au moins {{ limit }} caractères',
        maxMessage: 'Le nom de la ville ne peut pas être plus long que {{ limit }} caractères',
    )]
    private $name;

    /**
     * @ORM\Column(type="string", length=5, unique=true)
     */
    #[Assert\NotBlank(message: 'Le code postal est obligatoire')]
    #[Assert\Length(
        min: 5,
        max: 5,
        minMessage: 'Le code postal doit faire {{ limit }} caractères',
        maxMessage: 'Le code postal doit faire {{ limit }} caractères',
    )]
    private $zipCode;

    /**
     * @ORM\OneToMany(targetEntity=Location::class, mappedBy="city", orphanRemoval=true)
     */
    private $locations;

    public function __construct()
    {
        $this->locations = new ArrayCollection();
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

    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    public function setZipCode(string $zipCode): self
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    /**
     * @return Collection|Location[]
     */
    public function getLocations(): Collection
    {
        return $this->locations;
    }

    public function addLocation(Location $location): self
    {
        if (!$this->locations->contains($location)) {
            $this->locations[] = $location;
            $location->setCity($this);
        }

        return $this;
    }

    public function removeLocation(Location $location): self
    {
        if ($this->locations->removeElement($location)) {
            // set the owning side to null (unless already changed)
            if ($location->getCity() === $this) {
                $location->setCity(null);
            }
        }

        return $this;
    }
}
