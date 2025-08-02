<?php

namespace App\Entity;

use App\Repository\ScheduleShiftAssignmentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ScheduleShiftAssignmentRepository::class)]
class ScheduleShiftAssignment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Schedule::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Schedule $schedule;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $startTime;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $endTime;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSchedule(): Schedule
    {
        return $this->schedule;
    }

    public function setSchedule(Schedule $schedule): static
    {
        $this->schedule = $schedule;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getStartTime(): \DateTimeInterface
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeInterface $startTime): static
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getEndTime(): \DateTimeInterface
    {
        return $this->endTime;
    }

    public function setEndTime(\DateTimeInterface $endTime): static
    {
        $this->endTime = $endTime;

        return $this;
    }

    /**
     * Get the duration of the shift in hours
     */
    public function getDurationInHours(): float
    {
        $start = $this->startTime;
        $end = $this->endTime;
        
        $diff = $start->diff($end);
        return $diff->h + ($diff->i / 60);
    }



    /**
     * Get formatted time range (e.g., "09:00 - 17:00")
     */
    public function getTimeRange(): string
    {
        return $this->startTime->format('H:i') . ' - ' . $this->endTime->format('H:i');
    }
}