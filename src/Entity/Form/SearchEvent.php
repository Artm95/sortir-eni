<?php

namespace App\Entity\Form;

use App\Entity\Campus;
use DateTime;
use Symfony\Component\Validator\Constraints as Assert;

class SearchEvent {
    #[Assert\Type(Campus::class)]
    public ?Campus $campus = null;

    #[Assert\Type('string')]
    public ?string $name = null;

    public ?DateTime $from = null;

    public ?DateTime $to = null;

    #[Assert\Type('bool')]
    public ?bool $organized = null;

    #[Assert\Type('bool')]
    public ?bool $subscribed = null;

    #[Assert\Type('bool')]
    public ?bool $notSubscribed = null;

    #[Assert\Type('bool')]
    public ?bool $over = null;

    /**
     * @return string|null
     */
    public function getName(): ?string {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void {
        $this->name = $name;
    }

    /**
     * @return DateTime|null
     */
    public function getFrom(): ?DateTime {
        return $this->from;
    }

    /**
     * @param DateTime $from
     */
    public function setFrom(DateTime $from): void {
        $this->from = $from;
    }

    /**
     * @return DateTime|null
     */
    public function getTo(): ?DateTime {
        return $this->to;
    }

    /**
     * @param DateTime $to
     */
    public function setTo(DateTime $to): void {
        $this->to = $to;
    }

    /**
     * @return bool|null
     */
    public function isOrganized(): ?bool {
        return $this->organized;
    }

    /**
     * @param bool $organized
     */
    public function setOrganized(bool $organized): void {
        $this->organized = $organized;
    }

    /**
     * @return bool|null
     */
    public function isSubscribed(): ?bool {
        return $this->subscribed;
    }

    /**
     * @param bool $subscribed
     */
    public function setSubscribed(bool $subscribed): void {
        $this->subscribed = $subscribed;
    }

    /**
     * @return bool|null
     */
    public function isNotSubscribed(): ?bool {
        return $this->notSubscribed;
    }

    /**
     * @param bool $notSubscribed
     */
    public function setNotSubscribed(bool $notSubscribed): void {
        $this->notSubscribed = $notSubscribed;
    }

    /**
     * @return bool|null
     */
    public function isOver(): ?bool {
        return $this->over;
    }

    /**
     * @param bool $over
     */
    public function setOver(bool $over): void {
        $this->over = $over;
    }

    /**
     * @return Campus|null
     */
    public function getCampus(): ?Campus {
        return $this->campus;
    }

    /**
     * @param Campus $campus
     */
    public function setCampus(Campus $campus): void {
        $this->campus = $campus;
    }
}