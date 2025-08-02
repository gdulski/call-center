<?php

namespace App\Entity;

use App\Repository\ScheduleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ScheduleRepository::class)]
class Schedule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, enumType: ScheduleStatus::class)]
    private ScheduleStatus $status;

    #[ORM\Column(type: 'date')]
    private \DateTimeInterface $weekStartDate;

    #[ORM\ManyToOne(targetEntity: QueueType::class)]
    #[ORM\JoinColumn(nullable: false)]
    private QueueType $queueType;

    #[ORM\OneToMany(mappedBy: 'schedule', targetEntity: ScheduleShiftAssignment::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $shiftAssignments;

    public function __construct()
    {
        $this->shiftAssignments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): ScheduleStatus
    {
        return $this->status;
    }

    public function setStatus(ScheduleStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getWeekStartDate(): \DateTimeInterface
    {
        return $this->weekStartDate;
    }

    public function setWeekStartDate(\DateTimeInterface $weekStartDate): static
    {
        $this->weekStartDate = $weekStartDate;

        return $this;
    }

    public function getQueueType(): QueueType
    {
        return $this->queueType;
    }

    public function setQueueType(QueueType $queueType): static
    {
        $this->queueType = $queueType;

        return $this;
    }

    /**
     * Get the end date of the week (6 days after start date)
     */
    public function getWeekEndDate(): \DateTimeInterface
    {
        return (clone $this->weekStartDate)->modify('+6 days');
    }

    /**
     * Get week identifier in format YYYY-WW
     */
    public function getWeekIdentifier(): string
    {
        return $this->weekStartDate->format('o-W');
    }

    /**
     * @return Collection<int, ScheduleShiftAssignment>
     */
    public function getShiftAssignments(): Collection
    {
        return $this->shiftAssignments;
    }

    public function addShiftAssignment(ScheduleShiftAssignment $shiftAssignment): static
    {
        if (!$this->shiftAssignments->contains($shiftAssignment)) {
            $this->shiftAssignments->add($shiftAssignment);
            $shiftAssignment->setSchedule($this);
        }

        return $this;
    }

    public function removeShiftAssignment(ScheduleShiftAssignment $shiftAssignment): static
    {
        $this->shiftAssignments->removeElement($shiftAssignment);

        return $this;
    }



    /**
     * Get total hours assigned in this schedule
     */
    public function getTotalAssignedHours(): float
    {
        $total = 0;
        foreach ($this->shiftAssignments as $assignment) {
            $total += $assignment->getDurationInHours();
        }
        return $total;
    }
}